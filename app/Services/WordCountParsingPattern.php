<?php

namespace App\Services;
use App\Models\Task;
use App\Traits\DataTrait;
use App\Traits\KafkaConnect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class WordCountParsingPattern {
    use KafkaConnect;
    use DataTrait;
    private $reduce_partition_count=4;
    
    public function createFiles($request, $ownerJob){
        $page = '';
        $line_count = 400; 
        //get file content
        // decomposition pattern for wordCount
        $contents = file_get_contents($request->file('data_file')->getRealPath());
        $lines = preg_split('/\n|\r\n?/', $contents);
       

        if($request->data_type === 'file'){
            $lines = array_filter($lines, function ($value) {
                $value = preg_replace('/([~!@#$%^&*()_+=`{}\[\]\|\\\:;\'<>",.\/? -])+/i'," ",$value);
                $value = trim($value);
                return strlen($value) > 0;
            });
            $page_number = 0;
            $counter = 1;
            $file_line_count = count($lines);
            foreach ($lines as $index=>$line) {
                // $line=str_replace(['{','}',',',';','[',']','?',':','.','$','_','-','(',')','"',"'",'/'],' ',$line);
                // $line = preg_replace('/([~!@#$%^&*()_+=`{}\[\]\|\\\:;\'<>",.\/? -])+/i'," ",$line);
                // $line= trim($line);
                //store lines to file
                $page .= $line."\n";
                if($counter > $line_count || $index == $file_line_count-1){
                    $url = 'data/' . $request->input('name') . $ownerJob->id . '/' . $page_number . '.txt';
                    Storage::disk('public')->put($url, $page);
                    $page_number++;
                    $counter = 1;
                    $page = '';
                }else{
                    $counter++;
                } 
                
            }
            return $page_number;
        }
        elseif($request->data_type === 'link_file'){
            $lines = array_filter($lines, function ($value) {
                $value = trim($value);
                return strlen($value) > 0;
            });
            return count($lines);

        }
    }

    public function getReducingData($owner_job){


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
            $task=Task::where('type','reduce')->where('job_id',$owner_job->job_id)->first();


            //check current partition
            $partition=Cache::get($currentConsumePartition);
            if($partition == null){
                $partition=0;
            }


            if($partition < $this->reduce_partition_count){

                $this->initConnector('consume',$topic);
                $all_result=[];
                while($partition < $this->reduce_partition_count && count($all_result) ==0){
                    $all_result=$this->cousumeAllMessage($partition);
                    $partition++;
                }
                Cache::put($currentConsumePartition,$partition,60000);
                if(count($all_result) == 0){
                    return $this->getPendingData($owner_job,$pending_group,$currentConsumePartition);
                }
                $pending_result=[];
                $waiting_result=[];

                $reduce_data=[];
                $result_count=100;

                foreach($all_result as $index=>$result){
                    $key=$result['key'];
                    $value=$result['value'];
                    if(!isset($reduce_data[$key])){
                        $reduce_data[$key]=[
                            'key'=>$key,
                            'value'=>$value,
                            'task_id'=>$task->id,
                            'owner_job_id'=>$owner_job->id
                        ];

                    }else{
                        $reduce_data[$key]['value'].=','.$value;
                    }
                }

                foreach (array_chunk($reduce_data, $result_count) as $index=>$chunk_data){
                    $keys=[];
                    $values=[];
                    foreach ($chunk_data as $result){
                        $key=$result['key'];
                        $value=$result['value'];

                        $keys[]=$key;
                        $values[]=$value;
                    }


                    $key=implode('|',$keys);
                    $value=implode('|',$values);
                    if($index == 0){
                        $pending_result['key']=$key;
                        $pending_result['value']=$value;
                        $pending_result['task_id']=$task->id;
                        $pending_result['owner_job_id']=$owner_job->id;
                    }else{
                        $waiting_result[$key]=[
                            'key'=>$key,
                            'value'=>$value,
                            'task_id'=>$task->id,
                            'owner_job_id'=>$owner_job->id
                        ];
                    }
                }
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

    function formatFinalResult($total_result){
        $string_result="";
        foreach($total_result as $complex_key=>$complex_value){

            $keys=explode('|',$complex_key);
            $values=explode('|',$complex_value);
            foreach ($keys as $index=>$key){
                $string_result .= $key. ' : ' .$values[$index]. "\n";
            }

        }
        return $string_result;
    }
}
