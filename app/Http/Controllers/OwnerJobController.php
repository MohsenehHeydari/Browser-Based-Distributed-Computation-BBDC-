<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use App\Models\OwnerJob;
use App\Models\Job;


class OwnerJobController extends Controller
{
    public function create(Request $request)
    {
        $rules=[
            'name' => 'required|unique:owner_jobs,name', //unique at owner-jobs table in field name
            'expire_date' => 'required', // check format of date (year/month/date)
            'job_id' => 'required|exists:jobs,id',
            'data_type'=>'required|in:file,link,link_file,data_value'
        ];

        if($request->data_type === 'file' || $request->data_type === 'link_file'){
            // $rules['data_file']="required_without:data_link|max:1000|mimes:txt, text"; // check mime type && size

            $rules['data_file']="required_without:data_link|max:1000000"; // check mime type && size
        }
        if($request->data_type === 'link'){
            $rules['data_link']='required';
        }
        if($request->data_type === 'data_value'){
            $rules['data_value']='required';
        }

        $this->validate($request, $rules);

        $data_links = null;
        if($request->data_type === 'link_file'){
            $data_links = file_get_contents($request->file('data_file')->getRealPath());
        }

        $ownerJob=\DB::transaction(function()use($request,$data_links){
            $date = explode('/', $request->expire_date);
            $date = Carbon::create($date[0], $date[1], $date[2]);
            $ownerJob = OwnerJob::create([
                'name' => $request->input('name'),
                'data_value' => $request->input('data_value'),
                'expire_date' => $date,
                'owner_id' => \Auth::user()->id,
                'status' => 'init',
                'job_id' => $request->job_id,
                'data_count' => 0,
                'data_links' => $data_links
            ]);
            
            // choose a service dynamically
            $job = Job::find($request->job_id);
            $path = '\\App\\Services\\'.ucfirst($job->name).'ParsingPattern';
            $data_count = app($path)->createFiles($request, $ownerJob); // app method create an instance of $path
            // another way to use defined service:
                // $service = new $path();
                // $files = $service->createFiles($request, $ownerJob);
            $ownerJob->data_count = $data_count;
            $ownerJob->save();

            return $ownerJob;

       
        });
        // $best_devices = json_encode($this->getBestDevice($ownerJob));
        // $redis_connection = Redis::connection();
        // $redis_connection->publish('newJob', $best_devices);
        
    }

    public function list()
    {

        $user = \Auth::user();
        $owner_jobs = OwnerJob::where('owner_id', $user->id)->get();
        return ['ownerJobList' => $owner_jobs];
    }

    public function delete($id)
    {
        $user = \Auth::user();
        $owner_job = OwnerJob::where('owner_id', $user->id)->findOrFail($id); // user can just delete his own devices
        $owner_job->delete();
        // Device::destroy($id);
        return ['message' => 'owner job' . $id . ' has deleted.'];
    }

    public function getBestDevice($owner_job)
    {
        // $owner_job = OwnerJob::find(1);
        // choose best device among online users which choose this job and are idle
        $online_users = json_decode(Redis::get('online_users'), true);
        if($online_users){
            $available_devices = [];
            foreach ($online_users as $online_user) {
                if ($online_user['job_id'] == $owner_job->job_id && $online_user['working_status'] == false) {
                    $available_devices[] = $online_user['device_id'];
                }
            }
            if(count($available_devices) > 0) {
                $data = \DB::table('process_logs')
                    ->join('devices', 'devices.id', '=', 'process_logs.device_id')
                    ->whereIn('process_logs.device_id', $available_devices)
                    ->select([
                        \DB::raw('sum(result_count) as total_result_count'),
                        \DB::raw('AVG(success_percent) as avg_success_percent'),
                        \DB::raw('AVG(avg_proccessing_duration) as avg_proccessing_duration'), //speed of doing task
                        'device_id',
                        'devices.CPU',
                        'devices.RAM',
                        'devices.battery',
                    ])
                    ->groupBy('device_id', 'devices.CPU', 'devices.RAM', 'devices.battery')
                    ->get();

                $max_result_count = 0; //for a device which has max result count
                $min_result_count = 0; //always is zero
                $max_proccessing_duration = 0; //for a device which are slowest
                $min_proccessing_duration = null;

                foreach ($data as $index => $d) {
                    if ($d->total_result_count > $max_result_count) {
                        $max_result_count = $d->total_result_count;
                    }
                    // if($d->total_result_count < $min_result_count || $min_result_count == null){
                    //     $min_result_count = $d->total_result_count;
                    // }
                    if ($d->avg_proccessing_duration > $max_proccessing_duration) {
                        $max_proccessing_duration = $d->avg_proccessing_duration;
                    }
                    if ($d->avg_proccessing_duration < $min_proccessing_duration || $min_proccessing_duration == null) {
                        $min_proccessing_duration = $d->avg_proccessing_duration;
                    }
                }
                // defining a ceiling amount to rank slowest device fairly
                $ceiling = $max_proccessing_duration + ($max_proccessing_duration * 10) / 100; // ceiling = max + 10%
                //max_processing_duration time has min rank so we reverse the range
                $temp_max_proccessing_duration = $ceiling - $min_proccessing_duration;

                foreach ($data as $index => $d) {

                    $rank = 0;
                    $rank += (7 * $d->CPU) / 100; // cpu has 7 out of 100 score
                    $rank += (7 * $d->RAM) / 100; // ram has 7/100 score
                    $rank += (7 * $d->battery) / 100; // battery has 7/100 score
                    $rank += (29 * $d->avg_success_percent) / 100; // success_percent has 29/100 score

                    // rank of result count =(current_device_result_count / max of result_count) * 25
                    $rank += ((($d->total_result_count)) / $max_result_count) * 25; // total result count has 25/100 score

                    // we reverse the current device processing duration
                    $temp_current_proccessing_time = $ceiling - $d->avg_proccessing_duration;
                    // rank of proccessing time=(current_proccessing_time / max of proccessing_time ) *25
                    $rank += ($temp_current_proccessing_time / $temp_max_proccessing_duration) * 25;

                    $data[$index]->rank = $rank;
                }

                $max_socket_count = 2;
                $socket_index = 0;
                $best_sockets = [];
                // if there is no device with history of doing this job and it is the first time to do this job
                if (count($data) > 0) {
                    $data = collect($data)->sortByDesc('rank');
                    $grouped_online_users = collect($online_users)->groupBy('device_id');
                    // check if there is one device with multi socket connection(maybe it opens more than one tab in browser)
                    foreach ($data as $d) { //data is sorted base on rank
                        $online_user = $grouped_online_users[$d->device_id]; //online_user = items of data(best devices)
                        if ($socket_index < $max_socket_count) {
                            foreach ($online_user as $user) {
                                if ($socket_index < $max_socket_count) {
                                    $best_sockets[] = $user['socket_id'];
                                    $socket_index++;
                                }else{
                                    break;
                                }
                            }
                        }else{
                            break;
                        }
                    }
                }
                if(count($best_sockets) < $max_socket_count){
                    foreach($online_users as $online_user){
                        if ($socket_index < $max_socket_count) {
                            if(!in_array($online_user['socket_id'],$best_sockets)){//check duplications of socket id
                                $best_sockets[] = $online_user['socket_id'];
                                $socket_index++;
                            }
                        }else{
                            break;
                        }
                    }
                }
            }else{
                $best_sockets = [];
            }

            return $best_sockets;
        }

        return [];

    }

    public function getJobs(){
        $jobs = Job::get();
        return ['jobs' => $jobs];
    }
}
