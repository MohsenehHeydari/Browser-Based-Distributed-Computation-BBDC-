<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Facades\Redis;
    use Illuminate\Support\Facades\Cache;

    use App\Models\Data;
    use App\Models\Device;
    use App\Models\OwnerJob;
    use App\Models\Job;
    use App\Models\ProcessLog;
    use App\Models\IntermediateResult;
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
    
        public function receiveResult( $request)
    {   $owner_job = OwnerJob::findOrFail($request->data['owner_job_id']);
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
           $rules['result.*']='required';
           $rules['result']='required';
            
        }

        $this->validate($request,$rules);

        // request = result+data+job_id
        //validate data
       
        
        if($task->type === 'map'){
            $this-> receiveMapResult($request,$task);
            
        }else if($task->type === 'reduce'){
            // store result in file
            $this-> receiveReduceResult($request,$task);
        }else {
            throw new \Exception('task type is not valid! type: '.$task->type);
        }

        $device_id = \Cookie::get('device-id');
        if(!$device_id){
            throw new \Exception ('device id is not valid!');
        }
        $owner_job_id = $request->data['owner_job_id'];
        $key = \Auth::user()->id.'-'.$device_id; //worker_id-device_id
        // time of recieving result
        $current_time = strtotime('now');

        $sentTaskInfo = 'sentTaskInfo-'.$owner_job_id;
        $taskCountValue = Redis::hGet($sentTaskInfo,$key);
        if($taskCountValue == null){
            throw new \Exception('task count is not valid for current result! key : '.$key);
        }
        $taskCountValue = json_decode($taskCountValue,true);
        // proccessing time of client
        $proccessingDurationTime = $current_time - $taskCountValue['time'];

        $recievedResultInfo = 'recievedResultInfo-'.$owner_job_id;
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
        $results = $request->result;
            
        if(count($results)>0){
            // check key exists in redis
            $exists_status=Redis::hExists('pendingMapData_'.$request->job_id, $request->data['index']);
            if($exists_status){ 
                
                $topic=$task->job->name.'-reduce';
                
                $this->initConnector('produce',$topic);

                // check if recieved result is not proper (key,value) => we should generate a proper result
                // in finding primes we recieved an array of arrays as a result ([[2,1],[3,1],[5,1]]) 
                $service_path = '\\App\\Services\\'.ucfirst($task->job->name).'ParsingPattern';
                $method_exists=method_exists($service_path,'generateProperMapResult');
                foreach($results as $key=>$value){

                    if($method_exists){
                        $temp_result=app($service_path)->generateProperMapResult($key,$value);
                        $key=$temp_result['key'];
                        $value=$temp_result['value'];
                    }

                    $partition=$this->getHash($key,4);
                    $data=json_encode(['key'=>$key,'value'=>$value]);
                    $this->produce($data,$partition);
                    // dd($data,$partition);
                }
                Redis::hDel('pendingMapData_'.$request->job_id, $request->data['index']);
                // $count=Cache::get('MapDataCount_'.$request->job_id);
                // Cache::put('MapDataCount_'.$request->job_id,$count-1,600);
                // throw new \Exception ('there is map data. exist status: '.$exists_status.'result count: '.count($results).'topic name: '.$topic);
            }
            else{
                // throw new \Exception ('there is no pending map data. exist status: '.$exists_status);
                // put results away -> it means data has gotten by another client and recieved data from that client and redis was cleared
                // so if this result save there will be a duplicate
                
            }

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



    