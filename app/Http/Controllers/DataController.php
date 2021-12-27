<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Redis;
    use App\Models\OwnerJob;
    use App\Models\Task;
    use Carbon\Carbon;


    use App\Traits\DataTrait;
    use App\Traits\KafkaConnect;

    class DataController extends Controller{
        use DataTrait;
        use KafkaConnect;
        
        function sendResult(Request $request){
            $this->validate($request,[
                'job_id'=>'required|exists:jobs,id',// job_id should be in 'job' table in a record named 'id'
                'data'=>'required',
            ]);

            $this->receiveResult($request);

            $job_id=$request->job_id;
            $nextData = $this->getData($job_id);
            $hasNewTask = 0 ;
                
            if($nextData){
                $hasNewTask = 1;
            }
                return ['hasNewTask'=>$hasNewTask,'nextData'=>$nextData];
            
        }
    
        public function receiveResult( $request){
            $owner_job = OwnerJob::findOrFail($request->data['owner_job_id']);
            if($owner_job->status == 'done'){
                return ;
            } 
            $task = Task::with('job')->findOrFail($request->data['task_id']);

            $rules = [
                // 'result' => 'required', //it should have 'result' key in request
                // 'result.*' => 'nullable', // result should not be empty
                'data' => 'required',
                'data.task_id' =>'required|exists:tasks,id',
                'data.owner_job_id' => 'required|exists:owner_jobs,id',
                'job_id' => 'required|exists:jobs,id',
            ];
        
            if($task->type !== 'map'){
//                $rules['result.*']='required';
                $rules['result']='required';
            
            }

            $this->validate($request,$rules);

            // request = result+data+job_id


            $service_path = '\\App\\Services\\'.ucfirst($owner_job->job->name).'ParsingPattern';
            if($task->type === 'map'){
                if(method_exists($service_path,'receiveMapResult')){
                    app($service_path)->receiveMapResult($request,$task);
                }
                else{
                    $this-> receiveMapResult($request,$task);
                }

            
            }else if($task->type === 'reduce'){
                // store result in file
                if(method_exists($service_path,'receiveReduceResult')){
                    app($service_path)->receiveReduceResult($request,$task);
                }
                else{
                    $this-> receiveReduceResult($request,$task);
                }
            }else {
                throw new \Exception('task type is not valid! type: '.$task->type);
            }

        $device_id = \Cookie::get('device-id');
        if(!$device_id){
            throw new \Exception ('device id is not valid!');
        }
        $key = \Auth::user()->id.'-'.$device_id; //worker_id-device_id
        // time of recieving result
        $current_time = strtotime('now');

        $sentTaskInfo = 'sentTaskInfo-'.$request->job_id;
        $taskCountValue = Redis::hGet($sentTaskInfo,$key);
        if($taskCountValue == null){
            throw new \Exception('task count is not valid for current result! key : '.$key);
        }
        $taskCountValue = json_decode($taskCountValue,true);
        // proccessing time of client
        $proccessingDurationTime = $current_time - $taskCountValue['time'];

        $recievedResultInfo = 'recievedResultInfo-'.$request->job_id;
        $value = Redis::hGet($recievedResultInfo,$key);
        
        if($value == null){
            $value =  ['time'=>$proccessingDurationTime,'count'=>1];
        }
        else{
            $value = json_decode($value, true);
            $value['count'] = intVal($value['count']) + 1;
            $value['time'] = intVal($value['time']) + $proccessingDurationTime;
        }
        Redis::hSet($recievedResultInfo,$key,json_encode($value));

    }

    protected function receiveMapResult($request,$task)
    {

        $results = explode('&',trim($request->result,'&'));
            
        if(count($results)>0){
            // check key exists in redis-> if not exist put it away
            $exists_status=Redis::hExists('pendingMapData_'.$request->job_id, $request->data['index']);
            if($exists_status){ 

                $topic=$task->job->name.'-reduce';
                
                $this->initConnector('produce',$topic);

                // check if recieved result is not proper (key,value) => we should generate a proper result
//                $setrvice_path = '\\App\\Services\\'.ucfirst($task->job->name).'ParsingPattern';
//                $method_exists=method_exists($service_path,'generateProperMapResult');
                try{
                    foreach($results as $result){

                        $data=explode('|',$result);
                        if(count($data) == 2){
                            $key = $data[0];
                            //$value = $data[1];
                            $this->produce($result,null,$key);
                        }

                    }
//                    while ($this->producer->getOutQLen() > 0) {
//                        $this->producer->poll(1);
//                    }
                   for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
                       $result = $this->producer->flush(10000);
                       if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                           break;
                       }
                       if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
                           throw new \RuntimeException('Was unable to flush, messages might be lost!');
                       }
                   }


                }
                catch (\Exception $exception){
//                    throw $exception;
//                    dd($exception);
                    die('Error : '.$exception->getMessage()) ;
                }
//                die('test after produce');
                
                Redis::hDel('pendingMapData_'.$request->job_id, $request->data['index']);
                // $count=Cache::get('MapDataCount_'.$request->job_id);
                // Cache::put('MapDataCount_'.$request->job_id,$count-1,60000);
                // throw new \Exception ('there is map data. exist status: '.$exists_status.'result count: '.count($results).'topic name: '.$topic);
            }
            else{
                // throw new \Exception ('there is no pending map data. exist status: '.$exists_status);
                // put results away -> it means data has gotten by another client and recieved data from that client and redis was cleared
                // so if this result save there will be a duplicate
                
            }
        }
        else{
            Redis::hDel('pendingMapData_'.$request->job_id, $request->data['index']);
        }
    }

    protected function receiveReduceResult($request,$task){
        //validate data
        $result= $request->result;

        foreach($result as $key=>$value){

            if(Redis::hExists('pendingReduceData_'.$request->job_id, $key)){

                Redis::hSet('resultReduce_'.$request->job_id, $key,$value);
            
                //delete key from pending group
                Redis::hDel('pendingReduceData_'.$request->job_id,$key);
            }
            
            
            
        }

    }
        
}



