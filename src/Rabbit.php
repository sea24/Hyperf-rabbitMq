<?php declare(strict_types=1);


namespace MeiQuick\Swoft\RabbitMq;

use ReflectionException;
use MeiQuick\Swoft\RabbitMq\Connection\ConnectionManager;
use Swoft\Bean\Exception\ContainerException;
use Swoft\Stdlib\Helper\Arr;

/**
 * Class Client
 *
 * @since 2.0
 */
class Rabbit
{
    /**
     * Default host
     *
     * @var string
     */
    protected $host = '127.0.0.1';
    /**
     * Default port
     *
     * @var int
     */
    protected $port = 5672;

    /**
     * Setting
     *
     * @var array
     */
    protected $setting = [];


    /**
     * @param Pool $pool
     *
     * @return Connection
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function createConnection($pool): Connection
    {
        //调用连接类
        $connection = Connection::new($this, $pool);
        $connection->create();
        return $connection;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return array
     */
    public function getSetting(): array
    {
        return Arr::merge($this->defaultSetting(), $this->setting);
    }

    /**
     * @return array
     */
    private function defaultSetting(): array
    {
        return [
            'open_eof_check' => true,
            'open_eof_split' => true,
            'package_eof'    => "\r\n\r\n",
        ];
    }
}