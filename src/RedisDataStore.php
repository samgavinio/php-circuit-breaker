<?php

namespace Zendesk\PHPCircuitBreaker;

use Predis\Client;
use Predis\Connection\ConnectionException;
use Zendesk\PHPCircuitBreaker\Contracts\AbstractDataStore;

class RedisDataStore extends AbstractDataStore
{
    const REDIS_KEY_PREFIX = 'php_cb_';

    /**
     * Redis client
     *
     * @var Predis\Client
     */
    private $client;

    /**
     * Class contructor.
     *
     * @param string $hostname
     * @param int $port
     * @param string $password
     * @param string database
     */
    public function __construct($hostname, $port = 6379, $password = null, $database = 0)
    {
        $this->client = new Client([
            'host' => $hostname,
            'port' => $port,
            'password' => $password,
            'database' => $database
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function isOpen()
    {
        $key = $this->prefixKey($this->serviceName . '_is_open');

        return (boolean) $this->client->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getSuccessfulRequests()
    {
        $key = $this->prefixKey($this->serviceName . '_success_counts');

        return (int) $this->client->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getFailedRequests()
    {
        $key = $this->prefixKey($this->serviceName . '_failure_counts');

        return (int) $this->client->get($key);
    }

    /**
     * {@inheritDoc}
     */
    public function toggleCircuit($isOpen)
    {
        $key = $this->prefixKey($this->serviceName . '_is_open');

        $this->client->set($key, $isOpen);
    }

    /**
     * {@inheritDoc}
     */
    public function clearFailedRequests()
    {
        $key = $this->prefixKey($this->serviceName . '_failure_counts');
        $this->client->set($key, 0);
    }

    /**
     * {@inheritDoc}
     */
    public function clearSuccessfulRequests()
    {
        $key = $this->prefixKey($this->serviceName . '_success_counts');
        $this->client->set($key, 0);
    }

    /**
     * {@inheritDoc}
     */
    public function addSuccess()
    {
        $key = $this->prefixKey($this->serviceName . '_success_counts');
        $count = $this->client->get($key);
        $this->client->set($key, $count + 1);
    }

    /**
     * {@inheritDoc}
     */
    public function addFailure()
    {
        $key = $this->prefixKey($this->serviceName . '_failure_counts');
        $count = $this->client->get($key);
        $this->client->set($key, $count + 1);
    }

    /**
     * Prepends the redis key prefix
     *
     * @var string $keyName
     */
    private function prefixKey($keyName)
    {
        return self::REDIS_KEY_PREFIX . $keyName;
    }
}
