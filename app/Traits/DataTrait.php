<?php

namespace App\Traits;
use Carbon\Carbon;

use \App\Models\OwnerJob;
use \App\Models\Task;
use \App\Models\ProcessLog;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


trait DataTrait{
    use KafkaConnect;

    private $reduce_partition_count=4;

    // public function getData($job_id){

    //     $data = \DB::table('owner_jobs')
    //     ->join('data','owner_jobs.id', '=', 'data.owner_job_id')
    //     ->orderBy('expire_date', 'asc')
    //     ->orderBy('data.id', 'asc')
    //     ->where('owner_jobs.job_id',$job_id)
    //     ->where('data.status','init')
    //     ->orwhere(function($query){
    //         $date = Carbon::now()->subMinutes(2);
    //         $query->where('data.status','pending')
    //         ->where('data.updated_at', '<',$date);
    //     }) 
    //     ->select(['data.id','data.task_id','data.url','data.owner_job_id'])
    //     ->first();

    //     // throw new \Exception('before send respons : '.$job_id);
    //     return $data;
    // }


    public function getData($job_id)
    {   //1. check owner job if there is any
        //2. check status of owner job
        //3. if status = init produce data url to map topic and first data to redis and client
        //4. else if status = mapping consume data from kafka and in last data check redis to get data
        //5. else if status = reducing check redis first then consume data from reduce topic
        $owner_job = OwnerJob::where('job_id',$job_id)
        // prioritize owner job with expire date and id
        ->with('job')
        ->orderBy('expire_date', 'asc')
        ->whereIn('status',['init','mapping','reducing'])
        ->orderBy('id', 'asc')->first();

        $data = null;
        // dd($owner_job);
        if($owner_job){
            $service_path = '\\App\\Services\\'.ucfirst($owner_job->job->name).'ParsingPattern';
            $reduce_method_exists=method_exists($service_path,'getReducingData');

            switch ($owner_job->status){
                case 'init': 
                    $data =  $this->getInitData($owner_job);
                    break;
                case 'mapping': 
                    $data = $this->getMappingData($owner_job);
                    break;
                case 'reducing':
                    if($reduce_method_exists){
                        $data = app($service_path)->getReducingData($owner_job);
                    }
                    else{
                        $data = $this->getReducingData($owner_job);
                    }
                    break;
                // case 'pending': 
                //     $data =  $this->getInitData($owner_job);
                //     break;
                default:
                    break;

            }
            
            // save sent task count and time (base on second) base of worker id in redis 
            $sentTaskInfo = 'sentTaskInfo-'.$owner_job->id;
            $device_id = \Cookie::get('device-id');
            $key = \Auth::user()->id.'-'.$device_id;
            $value = Redis::hGet($sentTaskInfo,$key);
            if(!$device_id){
                throw new \Exception ('device id has not set!');
            }
            if($value == null){
                $value = ['time'=>strtotime('now'),'count'=>1]; //time of sending current task
            }
            else{
                $value = json_decode($value,true);
                $value['count'] = intVal($value['count']) + 1;
                $value['time'] = strtotime('now');
            }
            Redis::hSet($sentTaskInfo,$key,json_encode($value));
        }


        return $data;
        
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

            //get array of files from disk
            //if data_links is null get files from storage
            if($owner_job->data_links === null || $owner_job->data_links === ''){
                $files = Storage::disk('public')->files('/data/'.$owner_job->name.$owner_job->id);
                // dd($files,'stoage','/data/'.$owner_job->name.$owner_job->id);
            }
            else{
                $files = preg_split('/\n|\r\n?/', $owner_job->data_links);
                $files = array_filter($files, function ($value) {
                    $value = trim($value);
                    return strlen($value) > 0;
                });
            }
            // dd($files);
            if(count($files)>0){
                $first=[];

                $topic=$owner_job->job->name.'-map';
                $this->initConnector('produce',$topic);

                foreach($files as $index=>$file){

                    if($owner_job->data_links === null){
                       $file='/'.$file; 
                    }
                
                    $data=[
                        'url'=>$file,
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

                Cache::put('MapDataCount_'.$owner_job->job_id,count($files)-1,600);

               
                $process_log_info['description'].= 'mapping process is started at '.Carbon::now();
                
                Cache::put('start_ownerJob_date_'.$owner_job->job_id,Carbon::now(),600);

                $owner_job->status='mapping';
                $owner_job->process_log = json_encode($process_log_info); 
                $owner_job->save();
                // return $first
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

    public function getMappingData($owner_job){
        
        //2.if MapdataCount > 0     
                // get data from kafka map topic
                //send data to redis and client
        //3. else check redis: if exist in redis:
                                                // send data from redis to client
                            // else: update owner job status 
                                    // reducing : call getReducingData 
        $process_log_info=$owner_job->process_log;
        if($process_log_info === null){
            $process_log_info=[];
        }else{
            $process_log_info=json_decode($process_log_info, true);
        }
        if(!isset($process_log_info['description'])){
            $process_log_info['description']= ''; 
        }

        $count = Cache::get('MapDataCount_'.$owner_job->job_id);

        $service_path = '\\App\\Services\\'.ucfirst($owner_job->job->name).'ParsingPattern';
        $reduce_method_exists=method_exists($service_path,'getReducingData');

        if($count > 0){
            
            $topic=$owner_job->job->name.'-map';
            $this->initConnector('consume',$topic);
            $key_cache = $topic.'-last_offset';
            $data = $this->consume(0,$key_cache);

            if($data !== null){
                  // MapDataCount - 1
                    $count = Cache::put('MapDataCount_'.$owner_job->job_id,$count-1,600);

                    Redis::hSet('pendingMapData_'.$owner_job->job_id, $data['index'], json_encode($data));
                    return $data;
            }
            else{
                $count = Cache::put('MapDataCount_'.$owner_job->job_id,0,600);
                Cache::forget($key_cache);
                $allValues = Redis::hVals('pendingMapData_'.$owner_job->job_id);
                if(count($allValues) > 0){
                    return json_decode($allValues[0],true); // transform to array
                }else{
                    $process_log_info['description'].= ' - mapping is finished and reducing has started. date: '.Carbon::now();
                    $owner_job->status = 'reducing';
                    $owner_job->process_log = json_encode($process_log_info);
                    $owner_job->save();
                    if($reduce_method_exists){
                        return app($service_path)->getReducingData($owner_job);
                    }
                    return $this->getReducingData($owner_job);
                }
            }
          
            

        }else {
            $allValues = Redis::hVals('pendingMapData_'.$owner_job->job_id);
            if(count($allValues) > 0){
                return json_decode($allValues[0],true);
            }else{
                $process_log_info['description'].= ' - mapping is finished and reducing has started. date: '.Carbon::now(); 
                $owner_job->status = 'reducing';
                $owner_job->process_log = json_encode($process_log_info); 
                $owner_job->save();
                if($reduce_method_exists){
                    return app($service_path)->getReducingData($owner_job);
                }
                return $this->getReducingData($owner_job);
            }
            

        }
    }

    public function getReducingData($owner_job){
        //1. check waitingReduceData-job_id in redis
        //      if there is any data, pop first data (key , array of values) and push it in pendindReduceData-job_id
        //              and sent it to client
        //      else if there is data in kafka reduce-topic in next partition, consume them all
        //             and loop in data of kafka -> send first one to pending group
        //                                       -> send others to waiting group
        //2. check pending group
        //      if there is any data sent it to client
        //      else change owner job status to done
        //3.  create file of final result
       
        $waiting_group='waitingReduceData_'.$owner_job->job_id;
        $pending_group='pendingReduceData_'.$owner_job->job_id;
        $currentConsumePartition='currentConsumePartition_'.$owner_job->job_id;
        $keys=Redis::hKeys($waiting_group);
        if(count($keys) > 0){
            $key=$keys[0];
            $data = Redis::hGet($waiting_group,$key);

            Redis::hDel($waiting_group,$key);
            Redis::hSet($pending_group,$key,$data);
            return json_decode($data,true);
        }else{


            $topic=$owner_job->job->name.'-reduce';
            // $consume_count_key =  $topic.'-consume-count';
            // $consume_count = Cache::get($consume_count_key);
            // if($consume_count === null){
            //     $consume_count = 0;
            // }
            $task=Task::where('type','reduce')->where('job_id',$owner_job->job_id)->first();

           
            //check current partition
            $partition=Cache::get($currentConsumePartition);
            if($partition == null){
                $partition=0;
            }

           
            if($partition < $this->reduce_partition_count){
                
                $this->initConnector('consume',$topic);
                $all_result = [];
                while(count($all_result) == 0 && $partition < $this->reduce_partition_count){
                    $all_result=$this->cousumeAllMessage($partition);
                    // $consume_count+=1;
                    // Cache::put($consume_count_key,$consume_count,600);
                    $partition++;
                }
                // if($partition >= $this->reduce_partition_count){
                //     $partition = 0;
                // }
                Cache::put($currentConsumePartition,$partition);
                if(count($all_result) == 0){
                    return $this->getPendingData($owner_job,$pending_group,$currentConsumePartition);
                }
                $pending_result=[];
                $waiting_result=[];
                foreach($all_result as $index=>$result){
                    $key=$result['key'];
                    $value=$result['value'];
                    if($index == 0){
                        $pending_result['key']=$key;
                        $pending_result['value']=$value;
                        $pending_result['task_id']=$task->id;
                        $pending_result['owner_job_id']=$owner_job->id;
                    }else{
                        if($pending_result['key'] === $key){
                            $pending_result['value'].=','.$value;
                        }else{
                             if(!isset($waiting_result[$key])){
                                $waiting_result[$key]=[
                                    'key'=>$key,
                                    'value'=>$value,
                                    'task_id'=>$task->id,
                                    'owner_job_id'=>$owner_job->id
                                ];
                                
                             }else{
                                $waiting_result[$key]['value'].=','.$value;
                             }
                         }
                    }
                }
                // dd($pending_result,'pending result');
                Redis::hSet($pending_group,$pending_result['key'],json_encode($pending_result));
                foreach($waiting_result as $key=>$result){
                    Redis::hSet($waiting_group,$key,json_encode($result));
                }

                return $pending_result;
            }else{
                // Cache::put($currentConsumePartition,0);
                return $this->getPendingData($owner_job,$pending_group,$currentConsumePartition);
            }

        }

    }
    
    //last step check pending data
    public function getPendingData($owner_job,$pending_group=null,$currentConsumePartition=null){
        $process_log_info=$owner_job->process_log;
        if($process_log_info === null){
            $process_log_info=[];
        }else{
            $process_log_info=json_decode($process_log_info, true);
        }
        if(!isset($process_log_info['description'])){
            $process_log_info['description']= ''; 
        }

        $keys=[];
        if($pending_group !== null){
             $keys=Redis::hKeys($pending_group);
        }
       
                if(count($keys)>0){
                    $key=$keys[0];
                    return  json_decode(Redis::hGet($pending_group,$key),true);
                }else{

                    if($owner_job->status == 'done'){
                        return $this->getData($owner_job->job_id);
                    }

                    try{
                        // $owner_job->status='pending';
                        // $owner_job->save();

                        //save final result to file
                        $total_result = Redis::hGetAll('resultReduce_'.$owner_job->job_id);
                        if($total_result === null){
                            
                            $owner_job->status='done';
                            $owner_job->save();
                            return $this->getData($owner_job->job_id);
                        }

                        $result_collection=collect($total_result)->sortKeys();
                        $string_result = '';
                        foreach($result_collection as $key=>$value){
                            $string_result .= $key. ' : ' .$value. "\n"; 
                        }
                        $final_result_path= 'results/'.$owner_job->job->name.'/'.$owner_job->name.'-'.$owner_job->id.'.txt'; //creating url 
                        Storage::disk('public')->put($final_result_path, $string_result);//store data in file
                        $owner_job->status='done';
                        $owner_job->final_result_url = '/'.$final_result_path;

                        $process_log_info['description'].= ' - owner job process has completed at: '. Carbon::now();
                        
                        $start_ownerJob_date=Cache::get('start_ownerJob_date_'.$owner_job->job_id);
                        
                        $process_log_info['total_ownerJob_duration'] = $start_ownerJob_date->floatDiffInSeconds(Carbon::now()); 
                        
                        $bandwith = 'client_occupied_bandwith_size_'.$owner_job->job_id;
                        $process_log_info['client_occupied_bandwidth'] = Cache::get($bandwith);
                        // $process_log_info['client_occupied_bandwidth'] .= ' bytes';

                        $post_request_count = 'request_count_'.$owner_job->job_id;
                        $process_log_info['request_count'] = Cache::get($post_request_count);

                        $response_data_count = 'response_count_'.$owner_job->job_id;
                        $process_log_info['response_count'] = Cache::get($response_data_count);

                        $server_process_duration_time = 'server_process_duration_time_'.$owner_job->job_id;
                        $process_log_info['total_server_process']=Cache::get($server_process_duration_time);

                        $server_process_duration_time_detail = Cache::get('server_process_duration_time_detail_'.$owner_job->job_id);
                        $process_log_info['server_process_duration_time_detail']=$server_process_duration_time_detail;

                        $process_log_info['total_request_size'] = $process_log_info['request_count']*1348;
                        $process_log_info['total_response_size'] = $process_log_info['response_count']*1200;
                        $process_log_info['total_size'] = $process_log_info['total_request_size']+$process_log_info['total_response_size']+$process_log_info['client_occupied_bandwidth'];

                        // $topic=$owner_job->job->name.'-reduce';
                        // $consume_count_key =  $topic.'-consume-count';
                        // $consume_count = Cache::get($consume_count_key);
                        // $process_log_info['consume_count_reduce'] = $consume_count;

                        $owner_job->process_log = json_encode($process_log_info); 
                        $owner_job->save();
                        if($currentConsumePartition !== null){
                             Cache::put($currentConsumePartition,0);
                        }
                       

                        $this->logProcess($owner_job);

                        $this->reset($owner_job);

                        return $this->getData($owner_job->job_id);
                    }
                    catch(\Exception $exp){
                        $owner_job->status='failed';
                        $owner_job->save();
                        return $this->getData($owner_job->job_id);
                    }
                    
                }
    }

    public function redisDeleteAll($group){
        $keys = Redis::hKeys($group);
        foreach($keys as $key){
            Redis::hDel($group,$key);
        }
    }

    public function reset($owner_job){
        $waiting_group='waitingReduceData_'.$owner_job->job_id;
        $this->redisDeleteAll($waiting_group);
        $pending_group='pendingReduceData_'.$owner_job->job_id;
        $this->redisDeleteAll($pending_group);
        $pending_map_data='pendingMapData_'.$owner_job->job_id;
        $this->redisDeleteAll($pending_map_data);
        $pending_reduce_data='pendingReduceData_'.$owner_job->job_id;
        $this->redisDeleteAll($pending_reduce_data);
        $result_reduce='resultReduce_'.$owner_job->job_id;
        $this->redisDeleteAll($result_reduce);
        $sentTaskInfo = 'sentTaskInfo-'.$owner_job->id;
        $this->redisDeleteAll($sentTaskInfo);
        $recievedResultInfo = 'recievedResultInfo-'.$owner_job->id;
        $this->redisDeleteAll($recievedResultInfo);

        $map_data_count='MapDataCount_'.$owner_job->job_id;
        Cache::forget($map_data_count);
        $currentConsumePartition='currentConsumePartition_'.$owner_job->job_id;
        Cache::forget($currentConsumePartition);
        $start_ownerJob_date = 'start_ownerJob_date_'.$owner_job->job_id;
        Cache::forget($start_ownerJob_date);
        $bandwith = 'client_occupied_bandwith_size_'.$owner_job->job_id;
        Cache::forget($bandwith);
        $post_request_count = 'request_count_'.$owner_job->job_id;
        Cache::forget($post_request_count);
        $response_data_count = 'response_count_'.$owner_job->job_id;
        Cache::forget($response_data_count);
        $server_process_duration_time = 'server_process_duration_time_'.$owner_job->job_id;
        Cache::forget($server_process_duration_time);
        Cache::forget('server_process_duration_time_detail_'.$owner_job->job_id);
        $topic=$owner_job->job->name.'-map';
        Cache::forget($topic.'-last_offset');
        // $topic=$owner_job->job->name.'-reduce';
        // $consume_count_key =  $topic.'-consume-count';
        // Cache::forget($consume_count_key);

        // Storage::disk('public')->deleteDirectory('/data/'.$owner_job->name.$owner_job->id);

    }
    
    public function logProcess($owner_job){
        
        $sentTaskInfo = Redis::hGetAll('sentTaskInfo-'.$owner_job->id);
        $recievedResultInfo = Redis::hGetAll('recievedResultInfo-'.$owner_job->id);
        
        foreach($sentTaskInfo as $key=>$taskInfo){ // key = worker_id + device_id && taskInfo = time and count of sent task
            
            [$worker_id,$device_id] = explode('-',$key);
            $process_log = ProcessLog::where([
                'worker_id' => $worker_id,
                'device_id' => $device_id,
                'owner_job_id' => $owner_job->id])->first();


            if($process_log == null){

                $taskInfo = json_decode($taskInfo,true);

                if(isset($recievedResultInfo[$key])){

                    $resultInfo = json_decode($recievedResultInfo[$key],true); // array of time and count
                    $successPercent = ($resultInfo['count']/$taskInfo['count'])*100;
                    $avgProcessingDurationTime = $resultInfo['time']/$resultInfo['count']; //avg = processingDurationTime/resultCounts
                    $resultCount = $resultInfo['count'];
                }
                else{
                    $successPercent = 0;
                    $avgProcessingDurationTime = 0;
                    $resultCount = 0;
                }

                ProcessLog::updateOrCreate(
                    [
                        'worker_id' => $worker_id,
                        'device_id' => $device_id,
                        'owner_job_id' => $owner_job->id
                    ],
                    [
                    //check if client can not do any task (it might client has stopped in one task) 
                    'result_count' => $resultCount,
                    'task_count'=> $taskInfo['count'],
                    'success_percent' => $successPercent,
                    'avg_processing_duration' => $avgProcessingDurationTime,
                    ]);
            }
        }    
    }
}