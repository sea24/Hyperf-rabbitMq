<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq\Listener;


use ReflectionException;
use MeiQuick\Swoft\RabbitMq\Connection\ConnectionManager;
use Swoft\Bean\BeanFactory;
use Swoft\Bean\Exception\ContainerException;
use Swoft\Event\Annotation\Mapping\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;
use Swoft\SwoftEvent;

/**
 * Class CoroutineDestoryListener
 *
 * @since 2.0
 *
 * @Listener(event=SwoftEvent::COROUTINE_DESTROY)
 */
class CoroutineDestoryListener implements EventHandlerInterface
{
    /**
     * @param EventInterface $event
     *
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function handle(EventInterface $event): void
    {
        /* @var ConnectionManager $conManager */
        $conManager = BeanFactory::getBean(ConnectionManager::class);
        $conManager->release(true);
    }
}