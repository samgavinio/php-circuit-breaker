<?php

namespace Zendesk\PHPCircuitBreaker;

use Exception;
use RuntimeException;
use Zendesk\PHPCircuitBreaker\Contracts\AbstractDataStore;

class CircuitBreaker
{
    /**
     * Key of the service making the call
     *
     * @var string
     */
    private $service;

    /**
     * The data store to hold the state of the circuit
     *
     * @var DataStoreInterface
     */
    private $dataStore;

    /**
     * Failure fallback
     */
    private $failureFallback;

    /**
     * The circuit configuration
     */
    private $config = [
        'enabled' => true,
        'requestCountThreshold' => 2,
        'allowedErrorPercentage' => 50
    ];

    /**
     * Class contructor
     *
     * @param string $service
     * @param DataStoreInterface $dataStore
     * @param array $config
     * @param function $failureCallback
     */
    public function __construct(
        $service,
        AbstractDataStore $dataStore,
        $config = [],
        $failureFallback = null
    ) {
        $this->dataStore = $dataStore;
        $this->service = $service;
        $this->config = array_merge($this->config, $config);
        $this->failureFallback = $failureFallback;

        $this->dataStore->setServiceName($service);
    }

    /**
     * Check if the circuit is currently open
     *
     * @return boolean
     */
    public function isOpen()
    {
        if ($this->dataStore->isOpen()) {
            return true;
        }

        /**
         * Only run the open circuit logic if the request threshold has been exceeded.
         */
         $requestCount = $this->dataStore->getTotalRequests();
        if ($requestCount < $this->config['requestCountThreshold']) {
            return false;
        }

        if ($this->dataStore->getErrorPercentage() < $this->config['allowedErrorPercentage']) {
            return false;
        } else {
            $this->dataStore->toggleCircuit(true);

            return true;
        }
    }

    /**
     * Return whether the request is allowed
     *
     * @return boolean
     */
    public function allowRequest()
    {
        return !$this->isOpen();
    }

    /**
     * Execute the wrapper service call
     *
     * @param function $callback
     */
    public function execute($callback)
    {
        if (!$this->allowRequest()) {
            $this->executeFallback();

            return false;
        }

        try {
            $callback();
            if ($this->dataStore->isOpen()) {
                $this->dataStore->toggleCircuit(true);
                $this->dataStore->resetRequestStats();
            }

            $this->dataStore->addSuccess();
        }
        catch (Exception $e) {
            $this->dataStore->addFailure();
            $this->executeFallback($e);
        }
    }

    /**
     * Execute the fallback
     *
     * @param Exception $e
     * @throws RuntimeException
     */
    public function executeFallback(Exception $e = null)
    {
        $message = $e === null ? "Service {$this->service} is short-circuited" : $e->getMessage();

        if (!is_callable($this->failureFallback)) {
            $message .= ' and no fallback is available.';

            throw new RuntimeException($message, 0, $e);
        }

        call_user_func($this->failureFallback, $e);
    }
}
