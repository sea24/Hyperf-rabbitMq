<?php declare(strict_types=1);

use MeiQuick\Swoft\RabbitMq\Publish\Publish;

$id = 1;
$name = 'jesse';
$builder = \MeiQuick\Swoft\RabbitMq\Publish\Demo\Builder\DemoBuilder::builder($id, $name);
$publish = \MeiQuick\Swoft\RabbitMq\Publish\Publish::new();

// 对象调用方法
// 发送预处理消息
$status = $publish->builder($builder)->preprocess();
// 判断预处理结果
if ($status) {
    // 处理业务逻辑
    $result = false;
    // 判断业务处理是否成功：成功调用deliver投递消息
    if ($result) {
        $publish->deliver();
    }
    $result = '主业务处理失败';
} else {
    $result = '预发送失败';
}

$params = [];
// 回调函数调用, 返回false则不会执行deliver()方法；方法最终返回，匿名函数内部业务处理返回的结果
// 回调函数处理预发送失败时，会自动抛异常，复杂的业务逻辑可以使用对象调用方法
try {
    $result = $publish->executor($builder, function () use ($params) {
        if ($params) {
            $res = false;
        } else {
            $res = true;
        }
        return $res;
    });
} catch (Exception $e) {
}