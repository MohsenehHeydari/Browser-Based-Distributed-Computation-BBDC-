<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use App\Traits\KafkaConnect;
use App\Traits\DataTrait;
use \App\Models\Task;
use Carbon\Carbon;


    
class FindingPrimesParsingPattern {
    use KafkaConnect;
    use DataTrait;
    public function createFiles($request, $owner_job){
        //get file content
        // decomposition pattern for primes
        // dd($request);
        $contents = $owner_job->data_value;
        if($contents == null){
            if($request->data_type === 'file'){
                $contents = trim(file_get_contents($request->file('data_file')->getRealPath()));
                $owner_job->data_value = $contents;
                $owner_job->save();
            }
            if($request->data_type === 'data_value'){
                $contents = $request->data_value;
            }
            elseif($request->data_type === 'link_file'){
                throw new \Exception('input is not valid!');
            }
        }

       // 2-100-10000000
       $number=intval($contents);
        if($number <= 1000){
            $range_length = 50;
        }
        else if($number <= 10000){
           $range_length = 100;
        }
        else if($number <= 100000){
           $range_length = 5000;
        }
        else if($number <= 1000000){
            $range_length = 5000;
        }
        else if($number <= 10000000){
            $range_length = 50000;
        }
        else if($number <= 100000000){
            $range_length = 100000;
        }
        else {
            $range_length = 500000;
        }
        $ranges = [];
        $index = 1;
        $counter=0;
        for($i=2 ; $i<=$number; $i+=$range_length){
            $counter++;
            $max = $i+$range_length-1;
            if($max > $number){
                $max = $number;
            }
            $result = $i.'-'.$max.'-'.$number ;

            $ranges[] = $result; 

            $index++;
        }

        $map_values = 'map_values-'.$owner_job->job_id;
        Cache::put($map_values,$ranges,60000);

        return $index;
    }
    public function generateProperMapResult($key, $value){ // key= index value = array of numbers like [2,1]
        $key = $value[0];
        $value = $value[1];
        return ['key'=>$key,'value'=>$value];
    }
    public function getReducingData($owner_job){
        $topic=$owner_job->job->name.'-reduce';

        for($partition = 0; $partition < 4; $partition++){
            $result_count= [];
            $this->initConnector('consume',$topic);
            $all_result=$this->cousumeAllMessage($partition);
            foreach($all_result as $index=>$result){

                $key = $result['key'];

                if(!isset($result_count[$key])){
                    $result_count[$key]=1;
                }
                else{
                    $result_count[$key]=$result_count[$key]+1;
                }

            }
            foreach($result_count as $key=>$value){
                if($value === 1){
                    Redis::hSet('resultReduce_'.$owner_job->job_id,$key,$value);
                }
            }
        }


        return $this->getPendingData($owner_job);

    }

    public function getInitData($owner_job){
        
        //1.insert data to kafka 
        //2.insert first data to redis
        //3.send first data to client
        //4.update owner job status
        $task=Task::where('type','map')->where('job_id',$owner_job->job_id)->first();

        $process_log_info=$owner_job->process_log;
        if($process_log_info === null){
            $process_log_info=[];
        }else{
            $process_log_info=json_decode($process_log_info, true);
        }
        if(!isset($process_log_info['description'])){
            $process_log_info['description']= ''; 
        }
        if($task){

            $map_values = 'map_values-'.$owner_job->job_id;
            $data_values = Cache::get($map_values);

            if($data_values === null){

                $this->createFiles(request(), $owner_job);
                $data_values = Cache::get($map_values);

            }

            Cache::forget($map_values);

            if(count($data_values)>0){
                $first=[];

                $topic=$owner_job->job->name.'-map';
                $this->initConnector('produce',$topic);

                foreach($data_values as $index=>$value){

                    $data=[
                        'value'=>$value,
                        'task_id'=>$task->id,
                        'owner_job_id'=>$owner_job->id,
                        'job_id'=>$owner_job->job_id,
                        'index'=>$index
                    ];

                    if($index === 0){
                        $first=$data;
                    }else{
                        $this->produce($data);
                    }
                }

                //save first to redis
                Redis::hSet('pendingMapData_'.$owner_job->job_id, $first['index'], json_encode($first)); //index is the same as data id in DB

                Cache::put('MapDataCount_'.$owner_job->job_id,count($data_values)-1,60000);

               
                $process_log_info['description'].= 'mapping process is started at '.Carbon::now();
                
                Cache::put('start_ownerJob_date_'.$owner_job->job_id,Carbon::now(),60000);

                $owner_job->status='mapping';
                $owner_job->process_log = json_encode($process_log_info); 
                $owner_job->save();
//                dd($data_values);
                return $first;
                
            }else{
                //change status to failed and add description reason of failing
                $process_log_info['description'].=' - process is failed because there is no data file to process. date: '.Carbon::now();
                $owner_job->status = "failed";
                $owner_job->process_log = json_encode($process_log_info); 
                $owner_job->save();
                //look for new owner job
                return $this->getData($owner_job->job_id);
            }

        }
        else{
            $process_log_info['description'].=' - there is no map task to start processing. date: '.Carbon::now(); 
            $owner_job->status = "failed";
            $owner_job->process_log = json_encode($process_log_info);
            $owner_job->save();
        }

    }
}
