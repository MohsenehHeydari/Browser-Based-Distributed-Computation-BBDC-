<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
// use App\Traits\DataTrait;
use App\Models\OwnerJob;
use App\Services\DataTraitService;

class BandwidthAssessment
{   
    // use DataTrait;  
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //before controller
        $job_id=0;
    
        $route_name = $request->route()->getName();
        
        if($route_name === 'getTask'){

            $job_id = $request->route()->parameters['id'];
            
        } else if($route_name === 'sendResult'){

            $job_id=$request->job_id;

        }
        
        $request_count = Cache::get('request_count_'.$job_id);
        $request_count++;
        Cache::put('request_count_'.$job_id,$request_count,600);
        
        $server_process_duration_time = Cache::get('server_process_duration_time_'.$job_id);
        $recieve_request_time = Carbon::now();
        
        // dd('routes', $request->route());
        $bandwith = 'client_occupied_bandwith_size_'.$job_id;
        $bandwidth_size = Cache::get($bandwith);
        // dd($bandwidth_size, 'bandwidth in cache');
        // assess bandwith when recieve data from worker (maybe result)
        // request->all is all data(result) sent by worker in POST request
        $request_data=$request->all();
        $request_size=0;
        //request data is the result of map/reduce task has been sent from worker
        if(count($request_data)>0){
            # `strlen` returns number of chars in a string. Each char is 1 byte.
            # So to get size in bits, multiply `strlen` results by 8. Divide by
            # 1024 for KB or KiB. Divide by 1000 for kB.
            $serialized_data = json_encode($request_data);
            $request_size = strlen($serialized_data);
            $bandwidth_size += $request_size;
            Cache::put($bandwith,$bandwidth_size,600);
        }
        // else if count($data) = 0 it means there is no data so it's not necessory to transform to json and assess size    

        //assess bandwith when return data to worker(maybe a task info)
        $response = $next($request);

        $response_start_time = Carbon::now();

        //response data is the input data need to process has been sent from server to worker 
        $response_data=$response->getData();
        $serialized_data = json_encode($response_data);
        $response_size = strlen($serialized_data);
        $req_and_res_duration = $recieve_request_time->floatDiffInSeconds(Carbon::now()); // time between request from client till response from server per request

       
        // dd($size * 8,'bwa middleware',$response->getData()->data, $serialized_data);

        $owner_job_id = Cache::get('ownerJobFinished-'.$job_id);
        if($owner_job_id === null){
            $response_count = Cache::get('response_count_'.$job_id);
            $response_count++;
            Cache::put('response_count_'.$job_id,$response_count,600);
    
            $bandwidth_size += $response_size;
            Cache::put($bandwith,$bandwidth_size,600);
           
            $server_process_duration_time+=$req_and_res_duration;
            Cache::put('server_process_duration_time_'.$job_id,$server_process_duration_time,600); 
    
            $server_process_duration_time_detail = Cache::get('server_process_duration_time_detail_'.$job_id)??'';//if it was null set '';
            $server_process_duration_time_detail .= $req_and_res_duration.',';
            Cache::put('server_process_duration_time_detail_'.$job_id,$server_process_duration_time_detail,600);
        }
        
       
        if($owner_job_id !== null){
            $owner_job = OwnerJob::findOrFail($owner_job_id);
            // dd($owner_job_id, $owner_job->status);
            if($owner_job->status === 'done'){

                $data_trait_service = new DataTraitService();

                $process_log_info = $data_trait_service->getOwnerJobProcessLog($owner_job);
                
                $data_trait_service->logProcess($owner_job);
                $data_trait_service->reset($owner_job);
                // dd($process_log_info);
                $process_log_info['total_server_process'] = $process_log_info['total_server_process']+$req_and_res_duration;
                $process_log_info['response_count'] = $process_log_info['response_count']+1;
                $process_log_info['total_ocuupied_bandwidth'] = $process_log_info['total_ocuupied_bandwidth']+$response_size;
                $process_log_info['server_process_duration_time_detail'] = $process_log_info['server_process_duration_time_detail'].$req_and_res_duration;
                $process_log_info['total_ownerJob_duration'] = $process_log_info['total_ownerJob_duration'] + $response_start_time->floatDiffInSeconds(Carbon::now());

                $owner_job->process_log = json_encode($process_log_info); 
                $owner_job->save();
                
            }
            else{
                
                throw new \Exception('owner job status is not done!');
            }
        }
        

        return $response;
    }
}
