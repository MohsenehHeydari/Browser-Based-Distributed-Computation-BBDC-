<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Traits\KafkaConnect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\OwnerJob;
use App\Models\ProcessLog;
use Carbon\Carbon;

class TestController extends Controller
{
    use KafkaConnect;
    public function index($f = null)
    {
        if ($f == null) {
            $f = 'test';
        }
        if (method_exists($this, $f)) {
            return $this->$f();
        } else {
            throw new \Exception("method {$f} does not exist");
        }
    }
    
    public function test(){
        // $line = '256,256,';
        // $data= explode(',',trim($line,',')); // trim delete , 
        // dd(\Cookie::get('device-id'));
        // dd(Redis::hGetAll('sentTaskInfo-9'));
        // dd(Redis::hVals('pendingMapData_4'));
        dd(phpinfo());
    }
    


    public function reset()
    {
        $jobs=Job::get();
        foreach ($jobs as $job){
            $waiting_group = 'waitingReduceData_'.$job->id;
            $this->redisDeleteAll($waiting_group);
            $pending_group = 'pendingReduceData_'.$job->id;
            $this->redisDeleteAll($pending_group);
            $pending_map_data = 'pendingMapData_'.$job->id;
            $this->redisDeleteAll($pending_map_data);
            $pending_reduce_data = 'pendingReduceData_'.$job->id;
            $this->redisDeleteAll($pending_reduce_data);
            $result_reduce = 'resultReduce_'.$job->id;
            $this->redisDeleteAll($result_reduce);
            $sentTaskInfo = 'sentTaskInfo-'.$job->id;
            $this->redisDeleteAll($sentTaskInfo);
            $receivedResultInfo = 'receivedResultInfo-'.$job->id;
            $this->redisDeleteAll($receivedResultInfo);

            $map_data_count = 'MapDataCount_'.$job->id;
            Cache::forget($map_data_count);
            $currentConsumePartition = 'currentConsumePartition_'.$job->id;
            Cache::forget($currentConsumePartition);
            $start_ownerJob_date = 'start_ownerJob_date_'.$job->id;
            Cache::forget($start_ownerJob_date);
            $bandwith = 'client_occupied_bandwith_size_'.$job->id;
            Cache::forget($bandwith);
            $post_request_count = 'request_count_'.$job->id;
            Cache::forget($post_request_count);
            $response_data_count = 'response_count_'.$job->id;
            Cache::forget($response_data_count);
            $server_process_duration_time = 'server_process_duration_time_'.$job->id;
            Cache::forget($server_process_duration_time);
            Cache::forget('server_process_duration_time_detail_'.$job->id);
            $topic=$job->name.'-map';
            $key_cache = $topic.'-last_offset';
            Cache::forget($key_cache);
            $topic=$job->name.'-map';
            Cache::forget( $topic.'-last_offset');
            Cache::forget('ownerJobFinished-'.$job->id);

            $partitions_count=4;
            $this->initConnector('consume', $job->name.'-reduce');
            for ($i=0;$i<$partitions_count;$i++){
                $this->cousumeAllMessage($i,false);
            }


            $this->initConnector('consume', $job->name.'-map');
            $this->cousumeAllMessage(0,false);

            // $logs=ProcessLog::get()->pluck('id')->toArray();
            // ProcessLog::destroy($logs);

        }
        $owner_jobs = OwnerJob::get();
        foreach($owner_jobs as $owner_job){
            if($owner_job){
                // $owner_job->status = 'init';
                // $owner_job->process_log = '';
                // $owner_job->save();
            }
            Cache::forget('logStatus-'.$owner_job->id);
        }
        
        dd('reset');
    }


    public function redisDeleteAll($group)
    {
        $keys = Redis::hKeys($group);
        foreach ($keys as $key) {
            Redis::hDel($group, $key);
        }
    }


    public function getBestDevice()
    {
           $owner_job = OwnerJob::findOrFail(2);
        // choose best device among online users which choose this job and are idle
        $online_users = json_decode(Redis::get('online_users'), true);
        if($online_users){
            $available_devices = [];
            foreach ($online_users as $online_user) {
                if (intval($online_user['job_id']) == $owner_job->job_id && $online_user['working_status'] == false) {
                    $available_devices[] = $online_user['device_id'];
                }
            }
            if(count($available_devices) > 0) {
                $data = \DB::table('process_logs')
                    ->join('devices', 'devices.id', '=', 'process_logs.device_id')
                    ->whereIn('device_id', $available_devices)
                    ->select([
                        \DB::raw('sum(result_count) as total_result_count'),
                        \DB::raw('AVG(success_percent) as avg_success_percent'),
                        \DB::raw('AVG(avg_processing_duration) as avg_processing_duration'), //speed of doing task
                        'device_id',
                        'devices.CPU',
                        'devices.RAM',
                        'devices.battery',
                    ])
                    ->groupBy('device_id', 'devices.CPU', 'devices.RAM', 'devices.battery')
                    ->groupBy('device_id')
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
                    if ($d->avg_processing_duration > $max_proccessing_duration) {
                        $max_proccessing_duration = $d->avg_processing_duration;
                    }
                    if ($d->avg_processing_duration < $min_proccessing_duration || $min_proccessing_duration == null) {
                        $min_proccessing_duration = $d->avg_processing_duration;
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
                    $temp_current_proccessing_time = $ceiling - $d->avg_processing_duration;
                    // rank of proccessing time=(current_proccessing_time / max of proccessing_time ) *25
                    $rank += ($temp_current_proccessing_time / $temp_max_proccessing_duration) * 25;

                    $data[$index]->rank = $rank;
                }
                $max_socket_count = 2;
                $socket_index=0;
                $device_socket_index=0;
                $best_sockets = [];
                $max_best_sockets_count = 10;
                // if there is no device with history of doing this job and it is the first time to do this job

                if (count($data) > 0) {
                    $data = collect($data)->sortByDesc('rank');
                    $grouped_online_users = collect($online_users)->groupBy('device_id');
                    // check if there is one device with multi socket connection(maybe it opens more than one tab in browser)
                //    dd($data);
                    foreach ($data as $d) { //data is sorted base on rank
                        $device_socket_index = 0;
                        $online_user = $grouped_online_users[$d->device_id]; //online_user = items of data(best devices)
                        if ($socket_index < $max_best_sockets_count) {
                            foreach ($online_user as $user) {
                                if ($device_socket_index < $max_socket_count) {
                                    $best_sockets[] = $user['socket_id'];
                                    $socket_index++;
                                    $device_socket_index++;
                                }else{
                                    $device_socket_index=0;
                                    break;
                                }
                            }
                        }else{
                            break;
                        }
                    }
                }
                //add other devices to best_sockets if best_sockets is not full
                if(count($best_sockets) < $max_best_sockets_count){
                    foreach($online_users as $online_user){
                        if(intval($online_user['job_id']) == $owner_job->job_id && $online_user['working_status'] == false){
                            if ($socket_index < $max_best_sockets_count) {
                                if(!in_array($online_user['socket_id'],$best_sockets)){//check duplications of socket id
                                    $best_sockets[] = $online_user['socket_id'];
                                    $socket_index++;
                                }
                                }else{
                                    break;
                                }
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

    public function rankingTest()
    {
        $owner_job = OwnerJob::find(1);
        $online_users = json_decode(Redis::get('online_users'), true);
        $available_devices = [];
        foreach ($online_users as $online_user) {
            if ($online_user['job_id'] == $owner_job->job_id && $online_user['working_status'] == false) {
                $available_devices[] = $online_user['device_id'];
            }
        }
        if (count($available_devices) > 0) {
            $data = \DB::table('process_logs')
                ->join('devices', 'devices.id', '=', 'process_logs.device_id')
                ->whereIn('process_logs.device_id', $available_devices)
                ->select([
                    \DB::raw('sum(result_count) as total_result_count'), //depends on min and max
                    \DB::raw('AVG(success_percent) as avg_success_percent'),
                    \DB::raw('AVG(avg_processing_duration) as avg_processing_duration'), //depends on min and max
                    'device_id',
                    'devices.CPU',
                    'devices.RAM',
                    'devices.battery',
                ])
                ->groupBy('device_id', 'devices.CPU', 'devices.RAM', 'devices.battery')
                ->get();

            $max_result_count = 0;
            $min_result_count = 0;
            $max_proccessing_duration = 0;
            $min_proccessing_duration = null;

            foreach ($data as $index => $d) {
                if ($d->total_result_count > $max_result_count) {
                    $max_result_count = $d->total_result_count;
                }
                // if($d->total_result_count < $min_result_count || $min_result_count == null){
                //     $min_result_count = $d->total_result_count;
                // }
                if ($d->avg_processing_duration > $max_proccessing_duration) {
                    $max_proccessing_duration = $d->avg_processing_duration;
                }
                if ($d->avg_processing_duration < $min_proccessing_duration || $min_proccessing_duration == null) {
                    $min_proccessing_duration = $d->avg_processing_duration;
                }
            }

            $ceiling = $max_proccessing_duration + ($max_proccessing_duration * 10) / 100;
            $temp_max_proccessing_duration = $ceiling - $min_proccessing_duration;

            $best_device_rank = 0;
            $best_device = null;
            foreach ($data as $index => $d) {
                // $d->device_id;
                $rank = 0;
                $rank += (7 * $d->CPU) / 100;
                $rank += (7 * $d->RAM) / 100;
                $rank += (7 * $d->battery) / 100;
                $rank += (29 * $d->avg_success_percent) / 100;

                // rank of result count =(current_device_result_count / max of result_count of all devices) * 25(max rank of total result)
                $rank += ((($d->total_result_count)) / $max_result_count) * 25;

                // rank of proccessing time=(current_proccessing_time / max of proccessing_time of all devices) *25
                $temp_current_proccessing_time = $ceiling - $d->avg_processing_duration;
                $rank += ($temp_current_proccessing_time / $temp_max_proccessing_duration) * 25;

                if ($rank > $best_device_rank) {
                    $best_device_rank = $rank;
                    $best_device = $d;
                }
                $data[$index]->rank = $rank;
            }
            dd($online_users, $data);
            if ($best_device == null) {
                $best_device_id = $available_devices[0];
            } else {
                $best_device_id = $best_device->device_id;
            }
            $best_sockets = [];
            foreach ($online_users as $online_user) {
                if ($online_user['device_id'] == $best_device_id) {
                    $best_sockets[] = $online_user['socket_id'];
                }
            }
        } else {
            $best_sockets = [];
        }

        dd($online_users, $best_sockets);
    }

    
    public function testProducer()
    {
        $topic = 'test';        
        $this->initConnector('produce',$topic);
        $start_produce = Carbon::now();
        for ($i = 0; $i < 2000000; $i++) {

            $key = 'text-' . random_int(1, 5);
            $value = random_int(1, 2);
            $data=json_encode(['key'=>$key,'value'=>$value]);
            $partition=null;

            //  $partition=$this->getHash($key,4);
            //  $key=null;

            $this->produce($data,$partition,$key);
        }
        
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $this->producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }
        $end_produce = $start_produce->floatDiffInSeconds(Carbon::now());
        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }
        dd($end_produce);
    }

    public function testConsumer()
    {
        $conf = new \RdKafka\Conf();

        // Set the group id. This is required when storing offsets on the broker
        $conf->set('group.id', 'myConsumerGroup');

        $rk = new \RdKafka\Consumer($conf);
        $rk->addBrokers("localhost:9092");

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.commit.interval.ms', 100);

        // Set the offset store method to 'file'
        $topicConf->set('offset.store.method', 'broker');

        // Alternatively, set the offset store method to 'none'
        // $topicConf->set('offset.store.method', 'none');

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'earliest': start from the beginning
        $topicConf->set('auto.offset.reset', 'earliest');

        $topic = $rk->newTopic("test", $topicConf);

        // Start consuming partition 0
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        $message = $topic->consume(0, 120 * 10000);
        dd($message);
        $i = 0;
        while ($i < 2) {
            $message = $topic->consume(0, 120 * 10000);
            $i++;
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    // var_dump($message);
                    dd($message);
                    echo "</br>";
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    public function  getHash($key, $partitions_count = 3)
    {
        $hash = 0;

        for ($i = 0; $i < strlen($key); $i++) {
            $hash += (ord($key[$i]) * $i);
        }
        return $hash % $partitions_count;
    }
}
