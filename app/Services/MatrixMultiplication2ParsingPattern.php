<?php
namespace App\Services;
use App\Models\Task;
use App\Traits\DataTrait;
use App\Traits\KafkaConnect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
// use IIlluminate\Validation\ValidationException;

class MatrixMultiplication2ParsingPattern{
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

        $second_matrix_string_data=$this->getSecondMatrixStringData($second_matrix_data,1);
        foreach ($second_matrix_string_data as $chunk_index=>$matrix_string_datum){
            $string_result=$matrix_string_datum;
            $url = 'data/' . $request->input('name') . $ownerJob->id . '/second/' .($chunk_index+1). '.txt';
            Storage::disk('public')->put($url, $string_result);
        }
        foreach($first_matrix_data as $row_index=>$row){
            $row_data=($row_index +1).':';
            $row_data.=implode(',',$row);
            $row_data.="\n";
            foreach ($second_matrix_string_data as $chunk_index=>$matrix_string_datum){
                $second_url = url('data/' . $request->input('name') . $ownerJob->id . '/second/' .($chunk_index+1). '.txt');
                $string_result=$row_data.$second_url;
                $url = 'data/' . $request->input('name') . $ownerJob->id . '/' . $row_index .'-'.($chunk_index+1). '.txt';
                Storage::disk('public')->put($url, $string_result);
                $urls[]=$url;
            }




        }

        return $first_matrix_row_count+$second_matrix_column_count;
    }

    protected function getSecondMatrixStringData($matrix,$section_number){
        $inverse_matrix=[];
        foreach ($matrix as $row_index=>$row){
            foreach ($row as $column_index=>$data){
                $inverse_matrix[$column_index][$row_index]=$data;
            }
        }

        $column_count=count($inverse_matrix);
        $chunk_length=intval($column_count/$section_number);
        if($chunk_length*$section_number<$column_count){
            $chunk_length+=1;
        }

        $chunk_matrix=array_chunk($inverse_matrix,$chunk_length);
        $column_index=0;
        $result=[];
        foreach ($chunk_matrix as $chunk_index=>$chunk_data){
            $string_data="";
            foreach ($chunk_data as $column_data){
                $string_data.=($column_index +1).':';
                $string_data.=implode(',',$column_data);
                $string_data.="\n";
                $column_index++;
            }
            $result[]=$string_data;

        }

        return $result;

    }

    public function receiveMapResult($request,$task)
    {

        $results = $request->result;

        if($results){
            // check key exists in redis-> if not kexist put it away
            $exists_status=Redis::hExists('pendingMapData_'.$request->job_id, $request->data['index']);
            if($exists_status){

                Redis::hDel('pendingMapData_'.$request->job_id, $request->data['index']);
                Redis::hSet('resultReduce_'.$request->job_id, $request->data['index'],$results);
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

    public function getReducingData($owner_job){
        $total_result = Redis::hGetAll('resultReduce_' . $owner_job->job_id);
        $result_collection = collect($total_result)->sortKeys();
        $string_result = '';
        foreach ($result_collection as  $value) {
            $string_result .=  $value . "\n";
        }
        return $this->getPendingData($owner_job,null,null,$string_result);

    }
}
