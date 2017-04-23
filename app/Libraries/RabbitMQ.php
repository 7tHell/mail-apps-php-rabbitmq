<?php

namespace App\Libraries;
Use Illuminate\Support\Facades\Log;
use Mockery\CountValidator\Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQ {
    /*
     * Parameter
     * queue_name 
     * passive
     * durable
     * exclusive
     * auto_delete
     * persistent
     * routing_key
     * data
     */
    public function task($param)
    {
        if(!empty($param['queue_name']))
        {
            try 
            {
                $connection = $this->setUpConnection();
                
                $channel = $this->setUpChannel($connection, $param);
                
                $message = new AMQPMessage($param['data'],
                        array('delivery_mode' => 
                            (isset($param['persistent']) ? $param['persistent'] : 2)
                        )
                      );
                
                $channel->basic_publish(
                        $message, 
                        isset($param['routing_key']) ? $param['routing_key'] : '', 
                        $param['queue_name']
                        );

                $this->close($connection, $channel);
                
                $result['status'] = 1;

                return $result;
            } 
            catch (\Throwable $throwable)
            {
                Log::error('Error RabbitMQ/task()',
                    ['param' => $param, 'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(), 'message' => $throwable->getMessage()]);

                $message = 'file: '.$throwable->getFile().
                    ' | line: '.$throwable->getLine().
                    ' | message: '.$throwable->getMessage();

                throw new Exception($message);
            }
        }
        else
        {
            $result['status'] = 0;
        }
    }
    
    public function close($connection, $channel)
    {
        $channel->close();
        $connection->close();
    }
    
    public function setUpConnection()
    {
        $connection = new AMQPStreamConnection(
                    env('MQ_HOST'), env('MQ_PORT'), 
                    env('MQ_USERNAME'), env('MQ_PASSWORD')
                    );
        
        return $connection;
    }
    
    public function setUpChannel($connection, $param)
    {
        $channel = $connection->channel();
        
        $channel->queue_declare(
                        $param['queue_name'], 
                        isset($param['passive']) ? $param['passive'] : FALSE, 
                        isset($param['durable']) ? $param['durable'] : TRUE, 
                        isset($param['exclusive']) ? $param['exclusive'] : FALSE, 
                        isset($param['auto_del']) ? $param['auto_del'] : FALSE
                        );
        
        return $channel;
    }
}
