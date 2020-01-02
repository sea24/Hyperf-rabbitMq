<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Publish;


use chan;
use Closure;
use MeiQuick\Rpc\Lib\Message;
use MeiQuick\Swoft\RabbitMq\Exception\PublishException;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\Concern\PrototypeTrait;
use Swoft\Redis\Pool;
use Swoft\Rpc\Client\Annotation\Mapping\Reference;

/**
 * Class Publish
 * @package App\Rpc\Publish
 * @Bean(scope=Bean::PROTOTYPE)
 */
class Publish
{
    use PrototypeTrait;

    /**
     * @Reference(pool="message.pool")
     * @var Message
     */
    private $message;

    /**
     * @Inject(name="message.middleware.redis.pool")
     * @var Pool
     */
    private $messageRedis;

    /**
     * @var array
     */
    private $prepareMessage;

    /**
     * @var array
     */
    private $preprocessResult;

    /**
     * @var array
     */
    private $deliverResult;

    public static function new(): self
    {
        $self = self::__instance();
        $self->deliverResult = new chan();
        return $self;
    }

    /**
     * 设置预处理消息
     * @param AbstractBuilder $builder
     * @return $this
     */
    public function builder(AbstractBuilder $builder)
    {
        $this->prepareMessage = $builder->getData();
        return $this;
    }

    /**
     * 发送预处理消息
     * @return bool
     */
    public function preprocess(): bool
    {
        $this->preprocessResult = $this->message->prepareMsg($this->prepareMessage);
        return ((int)$this->preprocessResult['status'] === 1) ? true : false;
    }

    /**
     * 获取预处理消息原始返回
     * @return array
     */
    public function getPreprocessResult(): array
    {
        return $this->preprocessResult;
    }

    /**
     * 投递消息
     */
    public function deliver(): void
    {
        sgo(function () {
            try {
                $deliverResult = $this->message->confirmMsgToSend($this->prepareMessage['msg_id'], 1);
                $this->deliverResult->push($deliverResult);
            } catch (\Throwable $e) {
                $this->messageRedis->set(sprintf('master_message_job:%s', (string)$this->prepareMessage['msg_id']), 1);
                throw new PublishException(sprintf('消息投递失败：[Message]:%s, [Params]:%s', $e->getMessage(), json_encode($this->prepareMessage)));
            }
        });
    }

    /**
     * 获取投递结果
     * @return array
     */
    public function getDeliverResult(): array
    {
        return $this->deliverResult->pop();
    }

    /**
     * @param AbstractBuilder $builder
     * @param Closure $cb
     * @return mixed
     * @throws PublishException
     */
    public function executor(AbstractBuilder $builder, Closure $cb)
    {
        if (false === $this->builder($builder)->preprocess()) {
            throw new PublishException("Message system busy, preprocess data failed");
        }
        $res = $cb();
        if (false !== $res) {
            $this->deliver();
        }
        return $res;
    }
}
