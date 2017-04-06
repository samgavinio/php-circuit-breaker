<?php

namespace Zendesk\PHPCircuitBreaker\Contracts;

abstract class AbstractDataStore
{
    /**
     * The name of the service
     *
     * @var string
     */
    protected $serviceName;

    /**
     * Check if the service has been marked as open in the data store
     *
     * @return boolean
     */
    abstract public function isOpen();

    /**
     * Get the number of failed requests
     *
     * @return int
     */
    abstract public function getFailedRequests();

    /**
     * Get the number of successful requests
     *
     * @return int
     */
    abstract public function getSuccessfulRequests();

    /**
     * Clears the failed request count
     */
    abstract public function clearFailedRequests();

    /**
     * Clears the successful request count
     */
    abstract public function clearSuccessfulRequests();

    /**
     * Toggles the circuit state
     *
     * @param boolean $isOpen
     */
    abstract public function toggleCircuit($isOpen);

    /**
     * Increment the success counter
     */
    abstract public function addSuccess();

    /**
     * Increment the failure counter
     */
    abstract public function addFailure();

    /**
     * Set the service name
     *
     * @param string $serviceName
     */
    public function setServiceName($serviceName)
    {
        $cleanedKey = preg_replace('/[^a-zA-Z0-9]+/', '_', $serviceName);
        $this->serviceName = strtolower($cleanedKey);

        return $this;
    }

    /**
     * Retrieves the number of total requests that have been made
     *
     * @return int
     */
    public function getTotalRequests()
    {
        $failures = $this->getFailedRequests();
        $successes = $this->getSuccessfulRequests();
        $total = $failures + $successes;

        return (int) $total;
    }

    /**
     * Returns the percentage of errors
     *
     * @return float
     */
    public function getErrorPercentage()
    {
        $total = $this->getTotalRequests();
        if ($total === 0) {
            return 0;
        } else {
            $failures = $this->getFailedRequests();

            return 100 * ($failures / (float) $total);
        }
    }

    /**
     * Reset the request statistics
     */
    public function resetRequestStats()
    {
        $this->clearFailedRequests();
        $this->clearSuccessfulRequests();
    }
}
