<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Publish;


use Swoft\Bean\Concern\PrototypeTrait;
use Swoft\Stdlib\Concern\DataPropertyTrait;

abstract class AbstractBuilder
{
    use DataPropertyTrait;
    use PrototypeTrait;

    /**
     * 交换机、路由配置
     * @var array = [
     *      exchange_name => string, //交换机
     *      route_key => string, //路由
     *      queue_name => string //队列名称
     * ]
     */
    protected $setting;

    /**
     * 业务数据
     * @var array
     */
    protected $messageBody = [];

    /**
     * @return array
     */
    public function getSetting(): array
    {
        return $this->setting;
    }

    /**
     * @param array $setting
     * @return void
     */
    public function setSetting(array $setting = []): void
    {
        if ($setting) {
            $this->setting = $setting;
        } else {
            $this->setting = $this->defaultSetting();
        }
    }

    /**
     * @return array
     */
    public function defaultSetting(): array
    {
        return [
            'exchange_name' => 'default', //交换机
            'route_key' => 'default', //路由
            'queue_name' => '/default', //队列名称
        ];
    }

    /**
     * @return array
     */
    public function getMessageBody()
    {
        return $this->messageBody;
    }

    /**
     * @param array $messageBody
     * @return void
     */
    public function setMessageBody(array $messageBody): void
    {
        $this->messageBody = $messageBody;
    }

    public function setData(array $data = []): void
    {
        if ($data) {
            $this->data = $data;
        } else {
            $this->data = $this->defaultData();
        }
    }

    public function defaultData()
    {
        return [
            'msg_id' => session_create_id(md5(uniqid())),
            'create_time' => intval(context()->getRequest()->getRequestTime()),
            'message_retries_number' => 0, //重试次数，
            'status' => 1, //消息状态
            'routing' => $this->getSetting(),
            'message_body' => $this->getMessageBody(),
        ];
    }

    /**
     * @param array $messageBody
     * @param array $setting
     * @return AbstractBuilder
     */
    public static function initialize(array $messageBody = [], $setting = [])
    {
        $self = self::__instance();
        // set setting
        $self->setSetting($setting);

        // set message body
        $self->setMessageBody($messageBody);

        // set data
        $self->setData();
        return $self;
    }
}
