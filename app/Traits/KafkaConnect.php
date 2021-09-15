<?php
namespace App\Traits;

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
            $this->producer = new \RdKafka\Producer($conf);
            $this->topic = $this->producer->newTopic($topic);
        }else if($type === 'consume'){

            $conf = new \RdKafka\Conf();
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
    public function produce($message,$partition=0){
        if(is_array($message)){
            $message=json_encode($message);
        }
        $this->topic->produce($partition, 0, $message); 
        $this->producer->poll(0);
        // $this->($message);
        
    }

    public function consume($partition)
    {
        $this->topic->consumeStart($partition, RD_KAFKA_OFFSET_STORED);
        $message = $this->topic->consume($partition,1500);

        if($message){
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    // var_dump($message);
                    $result = json_decode($message->payload,true);
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

    public function cousumeAllMessage($partition){
       
        $result = [];
       
        $this->topic->consumeStart($partition, RD_KAFKA_OFFSET_STORED);
    
        $j=0;
        $partition_end_status=false;
        
        while (!$partition_end_status) {
            
            $message = $this->topic->consume($partition,1500);
    
            if($message){
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $arrayMessage = json_decode($message->payload,true);
                        $result[]=$arrayMessage;
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
