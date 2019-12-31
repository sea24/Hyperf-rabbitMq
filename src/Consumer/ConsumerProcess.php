<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Consumer;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Process\Process;
use Swoft\Process\UserProcess;

/**
 * Class ConsumerProcess
 * @package MeiQuick\Swoft\RabbitMq\Consumer
 * @Bean()
 */
class ConsumerProcess extends UserProcess
{
    /**
     * @var Consumer
     */
    private $consumer;

    public function run(Process $process): void
    {
        $this->consumer->handler($process);
    }
}
