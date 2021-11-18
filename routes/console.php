<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('redis-subscribe', function () {
    Redis::subscribe(['a channel'], function ($message) {
        $this->comment($message);
    });
});

Artisan::command('testConsumer', function () {
    $this->comment('is consuming ....');

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
    $job_id=1;
    
    for($i=0; $i<4; $i++){
        $result = [];
        $first_of_consume = Carbon::now();
        // Start consuming partition 0
        $topic->consumeStart($i, RD_KAFKA_OFFSET_STORED);
        
        $this->comment('partition:'.$i);
        // $j=0;
        $partition_end_status=false;
        
        //if there is no message to consume in partition 0, consume next partition
        while (!$partition_end_status) {
            $before_consume = Carbon::now();
            // $this->comment('counter:'.$j++);
            $message = $topic->consume($i,1100);//consume(patition,timeout: maximum time to wait for message)

            // $callbalck($message);

            if($message){
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        // var_dump($message);
                        $objectMessage = json_decode($message->payload);
                        $this->comment('message is:'.$message->payload.'-time is:'.$before_consume->floatDiffInSeconds(Carbon::now()));
                        //check if the partition is empty and this message is first one
                        if(count($result) == 0){
                            $result[$objectMessage->key]['value'] = [$objectMessage->value];
                            $result[$objectMessage->key]['status'] = 'pending';
                        }else {
                            if(isset($result[$objectMessage->key])){
                                $result[$objectMessage->key]['value'][] = $objectMessage->value; 
                            }
                            else{
                                $result[$objectMessage->key]['value'] = [$objectMessage->value];
                                $result[$objectMessage->key]['status'] = 'init';
                            }
                        }
                        // Cache::tags('reduceData')->put($objectMessage->key, $result[$objectMessage->key]['value'], 120);
                        if($result[$objectMessage->key]['status'] == 'pending'){
                            Redis::hSet('pendingReduceData_'.$job_id, $objectMessage->key, json_encode($result[$objectMessage->key]['value']) );
                        }
                        else{
                            Redis::hSet('initReduceData_'.$job_id, $objectMessage->key, json_encode($result[$objectMessage->key]['value']) );
                        }
                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        $this->comment("No more messages; will wait for more");
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        $this->comment("Timed out");
                        break;
                    default:
                        throw new \Exception($message->errstr(), $message->err);
                        break;
                }
            }else{
                $this->comment('message waiting-time is: '.$before_consume->floatDiffInSeconds(Carbon::now()));
                $partition_end_status=true;
            }
            
        }
        $this->comment('partition consume duration:'.$first_of_consume->floatDiffInSeconds(Carbon::now()));
        // $this->comment(json_encode($result));
    }

    // $this->comment('-----------------------');
    // $this->comment('messages with pending status: '.json_encode(Redis::hGetAll('pendingReduceData_'.$job_id)));
    // $this->comment('-----------------------');
    // $this->comment('messages with init status: '.json_encode(Redis::hGetAll('initReduceData_'.$job_id)));
    // $this->comment('-----------------------');
    // $this->comment('value for text-1 key: '.json_encode(Redis::hGet('initReduceData_'.$job_id,'text-4')));
    // Redis::hDel('initReduceData_'.$job_id,'text-4');
    // $this->comment('value for text-4 deleted');
    // $this->comment('value for text-4 key: '.json_encode(Redis::hGet('initReduceData_'.$job_id,'text-4')));
    // $this->comment(json_encode(Cache::tags('reduceData')->get()));
    
    //close connection

});


Artisan::command('testSingleConsumer', function () {
  
    $this->comment('is consuming ....');

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
    
    $job_id=1;

    $last_partition=Cache::get('last_partition')===null?0:Cache::get('last_partition');
    $last_offset=Cache::get('last_offset')===null?RD_KAFKA_OFFSET_STORED:Cache::get('last_offset');
    $result = [];
       
        // Start consuming partition 0
        $topic->consumeStart($last_partition, $last_offset);
        
        $this->comment('partition:'.$last_partition);
        $j=0;
        $partition_end_status=false;
        
        //if there is no message to consume in partition 0, consume next partition
        while (!$partition_end_status) {
            // $this->comment('counter:'.$j++);
            $first_of_consume = Carbon::now();
            $message = $topic->consume($last_partition,1100);//consume(patition,timeout: maximum time to wait for message per partition)
           
            // $callbalck($message);

            if($message){
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        // var_dump($message);
                        $objectMessage = json_decode($message->payload);
                        $this->comment('message is:'.$message->payload.'-time is:'.$first_of_consume->floatDiffInSeconds(Carbon::now()));
                        $this->comment('offset is:'.$message->offset);
                        Cache::put('last_offset',$message->offset+1,60000);
                        return;
                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        $this->comment("No more messages; will wait for more");
                        $this->comment('message waiting-time is: '.$first_of_consume->floatDiffInSeconds(Carbon::now()));
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        $this->comment("Timed out");
                        break;
                    default:
                        throw new \Exception($message->errstr(), $message->err);
                        break;
                }
            }else{
                $this->comment('message waiting-time is: '.$first_of_consume->floatDiffInSeconds(Carbon::now()));
                if($last_partition >= 3){
                    $last_partition = -1;
                }
                Cache::put('last_partition',++$last_partition,60000);
                Cache::put('last_offset',RD_KAFKA_OFFSET_STORED,60000);
                $partition_end_status=true;
            }
            
        }
        $this->comment('partition consume duration:'.$first_of_consume->floatDiffInSeconds(Carbon::now()));
        // $this->comment(json_encode($result));

    // $this->comment('-----------------------');
    // $this->comment('messages with pending status: '.json_encode(Redis::hGetAll('pendingReduceData_'.$job_id)));
    // $this->comment('-----------------------');
    // $this->comment('messages with init status: '.json_encode(Redis::hGetAll('initReduceData_'.$job_id)));
    // $this->comment('-----------------------');
    // $this->comment('value for text-1 key: '.json_encode(Redis::hGet('initReduceData_'.$job_id,'text-4')));
    // Redis::hDel('initReduceData_'.$job_id,'text-4');
    // $this->comment('value for text-4 deleted');
    // $this->comment('value for text-4 key: '.json_encode(Redis::hGet('initReduceData_'.$job_id,'text-4')));
    // $this->comment(json_encode(Cache::tags('reduceData')->get()));
    
    //close connection

});


Artisan::command('testProducer', function () {
        
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', 'localhost:9092');

        //If you need to produce exactly once and want to keep the original produce order, uncomment the line below
        //$conf->set('enable.idempotence', 'true');

        $producer = new \RdKafka\Producer($conf);

        $topic = $producer->newTopic("test");
        // produce 10 messages
        for ($i = 0; $i < 10; $i++) {

            $key='text-'.$i;
            $value=random_int(1,10);
            // partitioning: by using getHash function messages with same key will go to one partiotion
            $partition=getHash($key,4);
            $message =  json_encode(['key'=>$key,'value'=>$value]);
            // produce(partiotion,0/RD_KAFKA_MSG_F_BLOCK,message payload)
            //RD_KAFKA_PARTITION_UA = unassigned partition
            //RD_KAFKA_MSG_F_BLOCK = block produce on full queue
            $topic->produce($partition, 0, $message); 
            $producer->poll(0);
            $this->comment($message);
        }

        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                break;
            }
        }

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }

});

function  getHash($key,$partitions_count=3) {
    $hash = 0;

    for ($i = 0; $i < strlen($key); $i++) {
        $hash += (ord($key[$i]) * $i);
    }
    return $hash % $partitions_count;
}

