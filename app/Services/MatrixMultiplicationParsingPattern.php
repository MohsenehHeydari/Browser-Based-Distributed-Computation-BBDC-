<?php
namespace App\Services;
use App\Models\Task;
use App\Traits\DataTrait;
use App\Traits\KafkaConnect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
// use IIlluminate\Validation\ValidationException;

class MatrixMultiplicationParsingPattern{
    use KafkaConnect;
    use DataTrait;
    private $reduce_partition_count=4;

    public function createFiles($request, $ownerJob){
        //get file content
        // decomposition pattern for wordCount
        if($request->data_type === 'file'){
           $contents = file_get_contents($request->file('data_file')->getRealPath());  
        }else{
           $contents = stream_get_contents(fopen($request->data_link, "rb"));
            // dd($contents);
        }
       
        $lines = preg_split('/\n|\r\n?/', $contents);
        $lines = array_filter($lines, function ($value) {
            return strlen($value) > 0;
        });

        $first_matrix_data = [];
        $second_matrix_data = [];

        $current_matrix = 'A';
    
        // 1,2,3=>[1,2,3]
        // 4,5,6
        // x   
        // 7,8,9
        // 10,11,12
        // 13,14,15

        $first_matrix_row_count = 0;
        $first_matrix_column_count = null;
        $second_matrix_row_count = 0;
        $second_matrix_column_count = null;

        $urls=[];
        foreach ($lines as $line) {
            //store lines to file
            // $url = 'data/' . $request->input('name') . $ownerJob->id . '/' . $index . '.txt';
            // Storage::disk('public')->put($url, $line);

            // $index++;

            if(trim($line) == 'x'){// trim deletes additional spaces
                $current_matrix = 'B';
                continue;
            }
            $data= explode(',',trim($line,',')); // trim delete , 
            $data_count = count($data);
                
            if($data_count == 0){
                throw new \Exception('row is empty for matrix: '.$current_matrix);
                // throw ValidationException::withMessages([
                //     'data_file' => 'row is empty for matrix: '.$current_matrix
                // ]);
            }

            if($current_matrix == 'A'){
                
                if($first_matrix_column_count === null){
                    $first_matrix_column_count=$data_count;
                }elseif($first_matrix_column_count !== $data_count){
                    throw new \Exception('first matrix data is not valid!');
                    // throw ValidationException::withMessages([
                    //     'data_file' => 'first matrix data is not valid!'
                    // ]);
                }
                
                $first_matrix_data[] =$data;
                $first_matrix_row_count++;

            }
            if($current_matrix == 'B'){
                // throw new \Exception(' test exception');
                if($second_matrix_column_count === null){
                    $second_matrix_column_count= $data_count;
                }elseif($second_matrix_column_count !== $data_count){
                    throw new \Exception('second matrix data is not valid!');
                    // throw ValidationException::withMessages([
                    //     'data_file' => 'second matrix data is not valid!'
                    // ]);
                }

                $second_matrix_data[] =$data;
                $second_matrix_row_count++;
            }
        }
        if($first_matrix_column_count !== $second_matrix_row_count){
            throw new \Exception(' multiplication is not possible!');
            // throw ValidationException::withMessages([
            //     'data_file' => 'multiplication is not valid'
            // ]);
        }
        if(count($first_matrix_data) == 0){
            throw new \Exception('there is no data for first matrix');
            // throw ValidationException::withMessages([
            //     'data_file' => 'there is no data for first matrix'
            // ]);
        }
        if(count($second_matrix_data) == 0){
            throw new \Exception('there is no data for second matrix');
            // throw ValidationException::withMessages([
            //     'data_file' => 'there is no data for second matrix'
            // ]);
        }
        // dd($first_matrix_data, $first_matrix_row_count,$first_matrix_column_count,$second_matrix_data, $second_matrix_row_count, $second_matrix_column_count);
        // table_name, row_number,column_number, total_row_count : cell[1],cell[2],cell[3]
        // A,1,1,4:1,2,3

        foreach($first_matrix_data as $row_index=>$row){

            // $string_data = 'A,'.($row_index +1).','.$first_matrix_row_count.':'.implode(',',$row);//implode trun array to string
            // $url = 'data/' . $request->input('name') . $ownerJob->id . '/A-' . $row_index . '.txt';
            // Storage::disk('public')->put($url, $string_data);
            // $urls[]=$url;


            $row_data="";
            foreach($row as $column_index=>$column){
                $string_data = 'A,'.($row_index +1);
                $string_data .=','.$first_matrix_row_count.':'.$column;
                if($first_matrix_row_count>$column_index+1){
                    $string_data.="\n";
                }
                $row_data.=$string_data;
            }
            $url = 'data/' . $request->input('name') . $ownerJob->id . '/A-' . $row_index . '.txt';
            Storage::disk('public')->put($url, $row_data);
            $urls[]=$url;
        }
        foreach($second_matrix_data as $row_index=>$row){

            // $string_data = 'B,'.($row_index +1).','.$second_matrix_column_count.':'.implode(',',$row);//implode trun array to string
            // $url = 'data/' . $request->input('name') . $ownerJob->id . '/B-' . $row_index . '.txt';
            // Storage::disk('public')->put($url, $string_data);
            // $urls[]=$url;

            $row_data="";
            foreach($row as $column_index=>$column){
                $string_data = 'B,'.($row_index +1);
                $string_data .=','.$second_matrix_column_count.':'.$column;
                if($second_matrix_column_count>$column_index+1){
                    $string_data.="\n";
                }
                $row_data.=$string_data;
            }
            $url = 'data/' . $request->input('name') . $ownerJob->id . '/B-' . $row_index . '.txt';
            Storage::disk('public')->put($url, $row_data);
            $urls[]=$url;
        }
        // dd($urls);
        return count($urls);
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
