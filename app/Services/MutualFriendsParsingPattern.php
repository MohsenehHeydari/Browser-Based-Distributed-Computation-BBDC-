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
             $index = 1;
            foreach ($lines as $line) {
                $url = 'data/' . $request->input('name') . $ownerJob->id . '/' . $index . '.txt';
                Storage::disk('public')->put($url, $line);

                $index++;
            }
            return $index;
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

        for($partition = 0; $partition < 4; $partition++){
            $result_data= [];
            $this->initConnector('consume',$topic);
            $all_result=$this->cousumeAllMessage($partition);
            foreach($all_result as $index=>$result){

                $key = $result['key'];

                if(!isset($result_data[$key])){
                    $result_data[$key]=$result['value'];
                }
                else{
                    $result_data[$key] .= ','.$result['value'];
                }

            }
            foreach($result_data as $key=>$value){
                
                Redis::hSet('resultReduce_'.$owner_job->job_id,$key,$value);
        
            }
        }

        return $this->getPendingData($owner_job);
    }
}