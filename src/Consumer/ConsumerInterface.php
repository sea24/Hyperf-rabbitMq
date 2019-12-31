<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Consumer;


interface ConsumerInterface
{
    public function handler(array $data): bool;
}
