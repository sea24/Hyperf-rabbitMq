# Rpc-Lib
基于swoft封装的rabbitmq连接池

## 使用需知
需要加载amqp扩展
composer require php-amqplib/php-amqplib

### 1. Publish（生产者）使用说明

##### 1.1 创建一个构建预处理数据的类
```php
<?php declare(strict_types=1);


namespace App\Components\Publish;

use MeiQuick\Swoft\RabbitMq\Publish\AbstractBuilder;
use Swoft\Bean\Annotation\Mapping\Bean;

/**
 * @Bean(scope=Bean::PROTOTYPE)
 */
class testBuilder extends AbstractBuilder
{
    // 方法名称和形参自定义
    // 目的是为了让调用的客户端可以清楚知道，这个消费者需要传递哪些业务参数
    public static function builder(int $id, int $name)
    {
        return parent::initialize([
            // 定义消费者需要的业务参数
            'id' => $id,
            'name' => $name,
        ], [
            // 定义消息中间件基础配置
            'exchange_name' => 'test',
            'route_key' => '/test',
            'queue_name' => 'test',
        ]);
    }
}
```

##### 1.2 调用上面创建好的类方法，构建预处理数据
```php
<?php declare(strict_types=1);

use MeiQuick\Swoft\RabbitMq\Publish\Publish;

$id = 1;
$name = 'jesse';
// 构建预处理（预发送）的消息
$builder = \App\Components\Publish\testBuilder::builder($id, $name);
```

##### 1.3  实例一个Publish（生产者）
```php
// 实例一个Publish（生产者）
$publish = \MeiQuick\Swoft\RabbitMq\Publish\Publish::new();
```
##### 1.4.1 对象方法调用
```php
// 发送预处理消息
$status = $publish->builder($builder)->preprocess();
// 判断预处理结果
if ($status) {
    // 处理业务逻辑
    $result = '这里是你的业务逻辑处理结果' ? true : false;
    // 判断业务处理是否成功：成功调用deliver投递预处理的消息
    if ($result) {
        $publish->deliver();
    }
    $result = '主业务处理失败';
} else {
    $result = '预处理消息失败';
}
```

##### 1.4.2 回调函数调用
```php
// 回调函数调用, 返回false则不会执行deliver()方法；方法最终返回，匿名函数内部业务处理返回的结果
// 回调函数处理预发送失败时，会自动抛异常(PublishException)，可以自行捕获这个异常做一些处理
// 复杂的业务逻辑可以使用对象方法调用
try {
    $result = $publish->executor($builder, function () {
        $res = '这里是你的业务逻辑处理结果' ? true : false;
        return $res;
    });
} catch (Exception $e) {
}
```
##### 1.5 获取消息中间件返回的原始结果
```php
// 获取预处理的返回结果
$publish->getPreprocessResult();
// 获取消息投递的返回结果
$publish->getDeliverResult();    

```

### 2. Consumer（消费者）使用说明

##### 1. 需要在bean.php添加如下配置
```php
return [
    // 在对应要启动的服务配置内，添加process配置
    'rpcServer|httpServer|wsServer' => [
        'class' => ServiceServer::class,
        'port' => env('RPC_SERVER_PORT', 18305),
        'process' => [
            // 添加消费者服务进程
            'test' => bean("test.process"),
        ]
    ],
    ...省略...
    // 定义一个消费者服务
    'test.consumer' => [
        'class' => \MeiQuick\Swoft\RabbitMq\Consumer\Consumer::class,
        'rabbit' => bean("rabbit.pool"),
        // redis配置的前缀必须是Message:（必须跟消息中间件的保持一致）
        // 可以使用包预定义的配置（message.middleware.redis.pool）
        'redis' => bean("message.middleware.redis.pool"),
        'exchangeName' => "test",  // 交换器
        'routeKey' => "/test",  // 路由键
        'queueName' => "test",  // 队列名称
        // 该类用户自定义如何消费消息的一些业务逻辑
        // 这个类必须实现\MeiQuick\Swoft\RabbitMq\ConsumerConsumerInterface接口
        'className' => \App\Components\Consumer\TestConsumer::class,
    ],
    // 消费者服务进程配置
    'test.process' => [
        'class' => \MeiQuick\Swoft\RabbitMq\Consumer\ConsumerProcess::class,
        'consumer' => bean('test.consumer') // 执行定义好的消费者服务
    ]
]
```

##### 2. 定义消费信息业务逻辑类
```php
<?php declare(strict_types=1);


namespace App\Components\Consumer;


use MeiQuick\Swoft\RabbitMq\Consumer\ConsumerInterface;
use MeiQuick\Swoft\RabbitMq\Exception\ConsumerException;

class TestConsumer implements ConsumerInterface
{

    public function handler(array $data): bool
    {
        // TODO: Implement handler() method.
        // 自定义消费业务逻辑部分
        $res = $data['message_body'] ? true : false;
        if ($res === true){
            return true;  // 消费成功
        } else {
            // 可以自定义消费失败的原因，通过ConsumeException抛出；
            // 错误原因会记录在redis里面
            // hash表：Message:message_consume_fail_detail
            // 键[msg_id] => 值[错误原因]
            throw new ConsumerException('【消费失败】失败原因：意外失败了');
        }       

    }
}
```
