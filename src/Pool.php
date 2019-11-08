<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq;

use ReflectionException;
use MeiQuick\Swoft\RabbitMq\Connection\ConnectionManager;
use Swoft\Bean\BeanFactory;
use Swoft\Bean\Exception\ContainerException;
use Swoft\Connection\Pool\AbstractPool;
use Swoft\Connection\Pool\Contract\ConnectionInterface;
use Swoft\Rpc\Client\Exception\RpcClientException;

/**
 * Class Pool
 *
 * @since 2.0
 */
class Pool extends AbstractPool
{
    /**
     * @var Client
     */
    protected $client;

    public function createConnection(): ConnectionInterface
    {
        if (empty($this->client)) {
            throw new \Exception(
                sprintf('Pool(%s) client can not be null!', __CLASS__)
            );
        }
        return   $this->client->createConnection($this);
    }

    public function connect()
    {
        try {
            /* @var ConnectionManager $conManager */
            $conManager = BeanFactory::getBean('connection.manager');
            $connection = $this->getConnection();
            $connection->setRelease(true); //设置重新使用
            $conManager->setConnection($connection); //设置连接
        } catch (Throwable $e) {
            throw new \Exception(
                sprintf('Pool error is %s file=%s line=%d', $e->getMessage(), $e->getFile(), $e->getLine())
            );
        }
        // Not instanceof Connection
        if (!$connection instanceof Connection) {
            throw new \Exception(
                sprintf('%s is not instanceof %s', get_class($connection), Connection::class)
            );
        }
        return $connection;
    }

}