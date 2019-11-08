<?php declare(strict_types=1);

namespace MeiQuick\Swoft\RabbitMq;

use function count;
use function explode;
use ReflectionException;
use function sprintf;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Concern\PrototypeTrait;
use Swoft\Bean\Exception\ContainerException;
use Swoft\Connection\Pool\AbstractConnection;
use Swoft\Log\Debug;
use Swoft\Rpc\Client\Exception\RpcClientException;
use Swoft\Rpc\Contract\PacketInterface;
use Swoft\Stdlib\Helper\JsonHelper;

/**
 * Class Connection
 *
 * @since 2.0
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class Connection extends AbstractConnection
{
    use PrototypeTrait;

    public $connection;

    protected $client;

    protected $host;

    protected $port;

    /**
     * @param \Swoft\Rpc\Client\Client $client
     * @param Pool $pool
     *
     * @return Connection
     * @throws ReflectionException
     * @throws ContainerException
     */
    public static function new($client, Pool $pool): Connection
    {
        $instance = self::__instance();
        $instance->client = $client;
        $instance->pool = $pool;
        $instance->lastTime = time();
        return $instance;
    }

    /**
     * @throws RpcClientException
     */
    public function create(): void
    {
        $host = $this->client->getHost();
        $port = $this->client->getPort();
        $getSetting = $this->client->getSetting();
        $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection($host, $port, $getSetting['userName'], $getSetting['password']);
        $setting = $this->client->getSetting();
        //赋值属性用于区分服务
        if (!empty($setting)) {
            //$connection->set($setting);
        }
        if (!$connection->isConnected()) {
            throw new \Exception(
                sprintf('Connect failed host=%s port=%d', $host, $port)
            );
        }
        $this->connection = $connection;
    }

    /**
     * Close connection
     */
    public function close(): void
    {
        $this->connection->close();
    }

    /**
     * @return bool
     * @throws RpcClientException
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function reconnect(): bool
    {
        $this->create();
        Debug::log('Rpc client reconnect success!');
        return true;
    }

    /**
     * @return \Swoft\Rpc\Client\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}