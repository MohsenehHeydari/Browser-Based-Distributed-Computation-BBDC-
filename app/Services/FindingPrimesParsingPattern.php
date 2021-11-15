<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;
use App\Traits\KafkaConnect;
use App\Traits\DataTrait;
use Illuminate\Support\Facades\Redis;
    
class FindingPrimesParsingPattern {
    use KafkaConnect;
    use DataTrait;
    public function createFiles($request, $ownerJob){
        //get file content
        // decomposition pattern for wordCount
       
    
       

        if($request->data_type === 'file'){
            $contents = trim(file_get_contents($request->file('data_file')->getRealPath()));
        }
        if($request->data_type === 'data_value'){
            $contents = $request->data_value;
        }
        elseif($request->data_type === 'link_file'){
           throw new Exception('input is not valid!');

        }

        //1000000

       // 2-100-10000000
       $number=intval($contents);
        if($number <= 1000){
            $range_length = 100;
        }
        else if($number <= 100000){
           $range_length = 50;
        }
        else if($number <= 1000000){
            $range_length = 45;
        }
        else if($number <= 10000000){
            $range_length = 40;
        }
        else if($number <= 100000000){
            $range_length = 30;
        }
        else if($number <= 100000000){
            $range_length = 25;
        }

        $index = 1;
        for($i=2 ; $i<=$number; $i+=$range_length){
            
            $max = $i+$range_length-1;
            if($max > $number){
                $max = $number;
            }
            $result = $i.'-'.$max.'-'.$number ;
            $url = 'data/' . $request->input('name') . $ownerJob->id . '/' . $index . '.txt';
                Storage::disk('public')->put($url, $result);

                $index++;
        }
        return $index;
    }
    public function generateProperMapResult($key, $value){ // key= index value = array of numbers like [2,1]
        $key = $value[0];
        $value = $value[1];
        return ['key'=>$key,'value'=>$value];
    }
    public function getReducingData($owner_job){
        $topic=$owner_job->job->name.'-reduce';
        $result_count= [];
        $final_result="";
        for($partition = 0; $partition < 4; $partition++){
            $this->initConnector('consume',$topic);
            $all_result = [];
            while(count($all_result) == 0 && $partition < $this->reduce_partition_count){
                $all_result=$this->cousumeAllMessage($partition);
               
                foreach($all_result as $index=>$result){

                    $key = $result['key'];
                    $value = $result['value'];
                    if(!isset($result_count[$key])){
                        $result_count[$key]=$value;
                    }else{
                        $result_count[$key]=$result_count[$key]+1;
                    }

                }

               
            }
        }
        foreach($result_count as $key=>$value){
            if($value === 1){
                Redis::hSet('resultReduce_'.$owner_job->job_id,$key,$value);
            }
        }

        return $this->getPendingData($owner_job);

    }
}