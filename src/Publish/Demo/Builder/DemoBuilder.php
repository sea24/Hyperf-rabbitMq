<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Publish\Demo\Builder;

use MeiQuick\Swoft\RabbitMq\Publish\AbstractBuilder;
use Swoft\Bean\Annotation\Mapping\Bean;

/**
 * Class OrderNoticePublish
 * @package App\Rpc\PrepareMessage
 * @Bean(scope=Bean::PROTOTYPE)
 */
class DemoBuilder extends AbstractBuilder
{
    /**
     * 自定义调用接口形参：例如：$id,$name等等消息投递是，messageBody需要携带的参数
     * @param int $id
     * @param string $name
     * @param array $more
     * @return AbstractBuilder
     */
    public static function builder(int $id, string $name, ...$more)
    {
        return parent::initialize([
            'id' => $id,
            'name' => $name,
            '...more' => $more
        ], [
            'exchange_name' => 'demo',  // 设置对应的交换机
            'route_key' => '/demo',  // 设置对应的路由
            'queue_name' => 'demo',  // 设置对应的队列名称
        ]);
    }
}
