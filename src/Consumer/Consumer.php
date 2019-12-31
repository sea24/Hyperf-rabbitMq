<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Consumer;


use MeiQuick\Swoft\RabbitMq\Pool;
use PhpAmqpLib\Channel\AMQPChannel;
use Swoft\Bean\Annotation\Mapping\Bean;
use MeiQuick\Swoft\RabbitMq\Exception\ConsumerException;
use Swoft\Process\Process;


/**
 * Class Consumer
 * @package MeiQuick\Swoft\RabbitMq\Consumer
 * @Bean()
 */
class Consumer
{
    private $rabbit;

    private $redis;

    private $exchangeName;

    private $routeKey;

    private $queueName;

    private $className;

    /**
     * @param Process $process
     * @throws ConsumerException
     */
    public function handler(Process $process)
    {
        try {
            $connection = $this->rabbit->connect();
            $connectionRabbit = $connection->connection;
            /** @var AMQPChannel $channel */
            $channel = $connectionRabbit->channel();

            $channel->queue_declare($this->queueName, false, true, false, false);
            $channel->exchange_declare($this->exchangeName, \PhpAmqpLib\Exchange\AMQPExchangeType::DIRECT, false, true, false);

            //队列绑定交换机跟路由
            $channel->queue_bind($this->queueName, $this->exchangeName, $this->routeKey);
            $channel->basic_consume($this->queueName, '', false, false, false, false, function ($message) {
                try {
                    $data = json_decode($message->body, true);

                    // 当前消息已处理，原因（已被消费，确认消息删除，超过最大投递次数）
                    if (!$this->redis->hExists("message_system", (string)$data['msg_id'])) {
                        //响应ack
                        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                        return false;
                    }

                    // 幂等性；这个值用于消息中间件进行投递、消费成功删除预发送；
                    $statusJob = $this->redis->get("integrating_message_job:" . $data['msg_id'], (string)$data['msg_id']);

                    // 已经消费成功了
                    switch ((int)$statusJob) {
                        case 2: // 已经消费成功了
                        case 1: // 任务正在执行
                            return false;
                        default:
                            // 设置该消息的执行状态为：执行任务当中
                            $this->redis->set(sprintf("integrating_message_job:%s", (string)$data['msg_id']), 1);
                            // 判断类是否存在
                            if (!class_exists($this->className)) {
                                throw new ConsumerException('当前类:%s不存在', $this->className);
                            }
                            // 更新业务，完成则处理，如果业务异常消息系统重新推送
                            $res = $this->dispatch((new $this->className), $data);
                            if (true === $res) {
                                // 设置该消息的执行状态为：已消费完成，由消息中间件统一删除；解决ack缺陷问题
                                $this->redis->set(sprintf("integrating_message_job:%s", (string)$data['msg_id']), 2);

                                // 响应ack
                                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                            } else {
                                // 设置该消息的执行状态为：消费失败；消息系统会根据这个状态，来判断是否重新投递
                                $this->redis->set(sprintf("integrating_message_job:%s", (string)$data['msg_id']), 3);
                                if ($data['message_retries_number'] >= 3) {
                                    // 最大投递次数消费失败，记录消息消费失败的原因，方便后台补偿
                                    $this->redis->hset('message_consume_fail_detail', $data['msg_id'], $res);
                                }
                            }
                            return true;
                    }
                } catch (\Throwable $e) {
                    // 捕获消费时，会不会出现异常，遇到异常设置该消息的执行状态为：消费失败；
                    // 避免系统意外导致，消息无法正确获取消费状态（通过消费状态判断消息是否需要重新投递，或者是消费成功删除预发送）
                    $this->redis->set(sprintf("integrating_message_job:%s", (string)$data['msg_id']), 3);
                }
            });
            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } catch (\Exception $e) {
            throw new ConsumerException(sprintf("Consumer Error: ") . $e->getMessage());
        }
    }

    /**
     * 用户自定义消费处理器的调度器
     * @param ConsumerInterface $consumer
     * @param array $data
     * @return mixed
     */
    public function dispatch(ConsumerInterface $consumer, array $data)
    {
        return $consumer->handler($data);
    }
}

