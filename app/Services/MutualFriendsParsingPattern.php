<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use App\Traits\DataTrait;
use App\Traits\KafkaConnect;


class MutualFriendsParsingPattern {
    use DataTrait;
    use KafkaConnect;
    public function createFiles($request, $ownerJob){
        $page = '';
        $line_count = 10; 
        //get file content
        // decomposition pattern for wordCount
        $contents = file_get_contents($request->file('data_file')->getRealPath());
        $lines = preg_split('/\n|\r\n?/', $contents);
       
        if($request->data_type === 'file'){
            $lines = array_filter($lines, function ($value) {
                $value = trim($value,',');
                $value = trim($value);
                return strlen($value) > 0;
            });
            $page_number = 0;
            $counter = 1;
            $file_line_count = count($lines);
             foreach ($lines as $index=>$line) {
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

        $topic=$owner_job->job->name.'-reduce';
        $final_result = '';
        for($partition = 0; $partition < 4; $partition++){
            $result_data= [];
            $this->initConnector('consume',$topic);
            $all_result=$this->cousumeAllMessage($partition);
            foreach($all_result as $index=>$result){
                $result=explode('|',$result);
                $key = $result[0];
                $value = $result[1];

                if(!isset($result_data[$key])){
                    $result_data[$key]=$value;
                    $final_result .= "\n".$key.':'.$value;

                }
                else{
                    // $result_data[$key] .= ','.$value;
                    $final_result .= ','.$value;
                }

            }
            
            
            // foreach($result_data as $key=>$value){
            //     $final_result .= $key.':'.$value."\n";    
            //     // Redis::hSet('resultReduce_'.$owner_job->job_id,$key,$value);
        
            // }
        }

        return $this->getPendingData($owner_job,null,null,$final_result);
    }
}