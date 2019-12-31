<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Publish;


use chan;
use Closure;
use Exception;
use MeiQuick\Rpc\Lib\Message;
use MeiQuick\Swoft\RabbitMq\Exception\PublishException;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\Concern\PrototypeTrait;
use Swoft\Redis\Pool;
use Swoft\Rpc\Client\Annotation\Mapping\Reference;
use Swoft\Rpc\Exception\RpcException;

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
     * @Inject(name="messageRedis.pool")
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

    public function builder(AbstractBuilder $builder)
    {
        $this->prepareMessage = $builder->getData();
        return $this;
    }

    public function preprocess(): bool
    {
        $this->preprocessResult = $this->message->prepareMsg($this->prepareMessage);
        return ((int)$this->preprocessResult['status'] === 1) ? true : false;
    }

    public function getPreprocessResult(): array
    {
        return $this->preprocessResult;
    }

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

    public function getDeliverResult(): array
    {
        return $this->deliverResult->pop();
    }

    /**
     * @param AbstractBuilder $builder
     * @param $cb
     * @return mixed
     * @throws Exception
     */
    public function executor(AbstractBuilder $builder, Closure $cb)
    {
        if (false === $this->builder($builder)->preprocess()) {
            throw new RpcException("Message system busy, preprocess data failed");
        }
        $res = $cb();
        if (false !== $res) {
            $this->deliver();
        }
        return $res;
    }
}
