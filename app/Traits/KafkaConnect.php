<?php
namespace App\Traits;
use Illuminate\Support\Facades\Cache;

/**
 * 
 */
trait KafkaConnect
{
    private $topic =null;
    private $producer=null;
    private $consumer=null;

    public function initConnector($type, $topic, $hosts = 'localhost:9092'){
         
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', $hosts);

        //If you need to produce exactly once and want to keep the original produce order, uncomment the line below
        //$conf->set('enable.idempotence', 'true');

        if($type === 'produce'){
            $conf->set('partitioner','consistent');
            $this->producer = new \RdKafka\Producer($conf);
            $this->topic = $this->producer->newTopic($topic);
        }else if($type === 'consume'){

            $conf->set('group.id', 'myConsumerGroup');

            $this->consumer = new \RdKafka\Consumer($conf);
            $this->consumer->addBrokers($hosts);

            $topicConf = new \RdKafka\TopicConf();
            $topicConf->set('auto.commit.interval.ms', 100);
            $topicConf->set('offset.store.method', 'broker');
            $topicConf->set('auto.offset.reset', 'earliest');

            $this->topic = $this->consumer->newTopic($topic, $topicConf);
        }
        
    }
    public function produce($message,$partition=0,$key=null){
        if($partition == null){
            $partition = RD_KAFKA_PARTITION_UA;
        }
        if(is_array($message)){
            $message=json_encode($message);
        }
        $this->topic->produce($partition, 0, $message, $key); 
        $this->producer->poll(0);
        // $this->($message);
        
    }

    public function consume($partition,$key_cache = null)
    {
        $last_offset = RD_KAFKA_OFFSET_STORED;
        if($key_cache !== null){
            $last_offset = Cache::get($key_cache);
            if($last_offset == null){
                $last_offset = RD_KAFKA_OFFSET_STORED;
            }
        }
        $this->topic->consumeStart($partition,$last_offset);
        
        $message = $this->topic->consume($partition,1500);

        if($message){
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    // var_dump($message);
                    $result = json_decode($message->payload,true);
                    if($key_cache !== null){
                         Cache::put($key_cache,$message->offset+1,60000);
                    }
                    return $result;
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    throw new \Exception ("No more messages; will wait for more");
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    throw new \Exception ("Time out!");
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    public function cousumeAllMessage($partition,$return_result=true){
       
        $result = [];
       
        $this->topic->consumeStart($partition, RD_KAFKA_OFFSET_STORED);

        $partition_end_status=false;
        
        while (!$partition_end_status) {
            
            $message = $this->topic->consume($partition,1500);
    
            if($message){
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        if($return_result){
                            $data=explode('|',$message->payload);
                            if(count($data) === 2){

                                $arrayMessage= ['key'=>$data[0],'value'=>$data[1]];
                            }
                            else{
                                $arrayMessage = json_decode($message->payload,true);
                            }
                            $result[]=$arrayMessage;
                        }

                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        throw new \Exception ("No more messages; will wait for more");
                        break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        throw new \Exception ("Time out!");
                        break;
                    default:
                        throw new \Exception($message->errstr(), $message->err);
                        break;
                }
            }else{
                $partition_end_status=true;
            }
        }
        return $result;       
    }

    public function  getHash($key,$partitions_count=3) {
        $hash = 0;
        $key = strval($key);
        for ($i = 0; $i < strlen($key); $i++) {
            $hash += (ord($key[$i]) * $i);
        }
        return $hash % $partitions_count;
    }
}
