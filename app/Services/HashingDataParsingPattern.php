<?php

namespace App\Services;
use App\Models\Task;
use App\Traits\DataTrait;
use App\Traits\KafkaConnect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HashingDataParsingPattern
{
    use KafkaConnect;
    use DataTrait;

    public function createFiles($request, $ownerJob)
    {
        $page = '';
        $line_count = 100;
        //get file content
        // decomposition pattern for wordCount
        $contents = file_get_contents($request->file('data_file')->getRealPath());
        $lines = preg_split('/\n|\r\n?/', $contents);

        if ($request->data_type === 'file') {
            $lines = array_filter($lines, function ($value) {
                $value = trim($value, ',');
                $value = trim($value);
                return strlen($value) > 0;
            });
            $page_number = 0;
            $counter = 1;
            $file_line_count = count($lines);
            foreach ($lines as $index => $line) {
                //store lines to file
                $page .= $line . "\n";
                if ($counter > $line_count || $index == $file_line_count - 1) {
                    $url = 'data/' . $request->input('name') . $ownerJob->id . '/' . $page_number . '.txt';
                    Storage::disk('public')->put($url, $page);
                    $page_number++;
                    $counter = 1;
                    $page = '';
                } else {
                    $counter++;
                }

            }
            return $page_number;
        } elseif ($request->data_type === 'link_file') {
            $lines = array_filter($lines, function ($value) {
                $value = trim($value);
                return strlen($value) > 0;
            });
            return count($lines);

        }
        return 0;
    }

    public function receiveMapResult($request,$task)
    {

        $results = $request->result;

        if($results){
            // check key exists in redis-> if not kexist put it away
            $exists_status=Redis::hExists('pendingMapData_'.$request->job_id, $request->data['index']);
            if($exists_status){

                $results= $request->data['index'] .' : '.$results."\n";
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
