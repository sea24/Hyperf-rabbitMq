<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Publish;


use Closure;
use Exception;
use MeiQuick\Rpc\Lib\Message;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Concern\PrototypeTrait;
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
            $this->deliverResult = $this->message->confirmMsgToSend($this->prepareMessage['msg_id'], 1);
        });
    }

    public function getDeliverResult(): array
    {
        return $this->deliverResult;
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
