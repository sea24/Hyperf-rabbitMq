<?php declare(strict_types=1);



namespace MeiQuick\Swoft\RabbitMq;

use MeiQuick\Swoft\RabbitMq\Connection\ConnectionManager;
use Swoft\Rpc\Packet;
use Swoft\SwoftComponent;

/**
 * Class AutoLoader
 *
 * @since 2.0
 */
class AutoLoader extends SwoftComponent
{
    /**
     * @return array
     */
    public function getPrefixDirs(): array
    {
        return [
            __NAMESPACE__ => __DIR__,
        ];
    }

    /**
     * @return array
     */
    public function metadata(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function beans(): array
    {
        return [
            'rabbit'      => [
                'class'  => Rabbit::class,
            ],
            'rabbit.pool' => [
                'class'   => Pool::class,
                'client' => bean('rabbit')
            ],
            'connection.manager' => [
                'class'   => ConnectionManager::class
            ]
        ];
    }
}