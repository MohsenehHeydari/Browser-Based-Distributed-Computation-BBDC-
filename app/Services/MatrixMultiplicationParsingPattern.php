<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
// use IIlluminate\Validation\ValidationException;

class MatrixMultiplicationParsingPattern{

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

        $index = 1;
        $first_matrix_data = [];
        $second_matrix_data = [];
        $row_count = 0;
        $column_count = 0;
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

            if(trim($line) == 'x'){// trim delete additional spaces
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
        // table_name, row_number, total_row_count : cell[1],cell[2],cell[3]
        // A,1,1,4:1,2,3

        foreach($first_matrix_data as $row_index=>$row){

            // $string_data = 'A,'.($row_index +1).','.$first_matrix_row_count.':'.implode(',',$row);//implode trun array to string
            // $url = 'data/' . $request->input('name') . $ownerJob->id . '/A-' . $row_index . '.txt';
            // Storage::disk('public')->put($url, $string_data);
            // $urls[]=$url;

           
            foreach($row as $column_index=>$column){
                $string_data = 'A,'.($row_index +1);
                $string_data .=','.($column_index+1).','.$first_matrix_row_count.':'.$column;
                $url = 'data/' . $request->input('name') . $ownerJob->id . '/A-' . $row_index.'-'.$column_index . '.txt';
                Storage::disk('public')->put($url, $string_data);
                $urls[]=$url;
            }
        }
        foreach($second_matrix_data as $row_index=>$row){

            // $string_data = 'B,'.($row_index +1).','.$second_matrix_column_count.':'.implode(',',$row);//implode trun array to string
            // $url = 'data/' . $request->input('name') . $ownerJob->id . '/B-' . $row_index . '.txt';
            // Storage::disk('public')->put($url, $string_data);
            // $urls[]=$url;

           
            foreach($row as $column_index=>$column){
                $string_data = 'B,'.($row_index +1);
                $string_data .=','.($column_index+1).','.$second_matrix_column_count.':'.$column;
                $url = 'data/' . $request->input('name') . $ownerJob->id . '/B-' . $row_index.'-'.$column_index . '.txt';
                Storage::disk('public')->put($url, $string_data);
                $urls[]=$url;
            }
        }
        // dd($urls);
        return $urls;
    }
}