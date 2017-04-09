# PHP Circuit Breaker #

This is a POC library for a PHP fault tolerance library that is going to be built by Zendesk.

PHPCircuitBreaker is a fault tolerance library that can prevent cascading system failure for applications that rely heavily on remote services. This is done by throttling outgoing requests depending on the health of the remote service being called by effectively preventing consecutive request failures for any particular service.

PHPCircuitBreaker will stop requests made to an unhealthy service until such a point that it has found that the service is once again healthy enough to handle requests.

## Installation

PHPCircuitBreaker can be installed using [Composer](https://getcomposer.org/)

Add the following to your `composer.json` file
```
"repositories": [
  {
    "type": "git",
    "url": "git@github.com:zendesk/zendesk_laravel_diagnostic.git"
  }
],
```

Under require:

```
"zendesk/php_circuit_breaker": "dev-master"
```

And then run:

```
composer install zendesk/php_circuit_breaker
```

## Usage

PHPCircuitBreaker protects remote service calls by allowing applications to wrap a function callback within an execute block. A minimal implementation to wrap outgoing guzzle requests would look like:

```
<?php

require __DIR__ .'/vendor/autoload.php';

$guzzle = new GuzzleHttp\Client();

$fallback = function (\Exception $e) {
    echo $e->getMessage();
};

$storage = new Zendesk\PHPCircuitBreaker\RedisDataStore('127.0.0.1');
$circuit = new Zendesk\PHPCircuitBreaker\CircuitBreaker('guzzle_request', $storage, [], $fallback);

$circuit->execute(function() use ($guzzle) {
    return $guzzle->request('GET', 'https://api.github.com/user', [
        'auth' => ['user', 'pass']
    ]);
});
```

To keep the state of the circuit, PHPCircuitBreaker needs to be passed a data storage to hold information about the circuit and the services it is interacting with. This library ships with a Redis adapater to allow for easier adaption out of the box.

The `CircuitBreaker` object needs to be passed in

* service
    * Value: String
    * The identifier/name of the service being protected
* dataStore
    * Value: `AbstractDataStore`
    * Implementation of the `AbstractDataStore` to keep the state of the circuit
* config
    * Value: Array
    * Configurable options for the circuit breaker
        * `requestCountThreshold`
            * Value: Integer
            * Number of requests before the circuit starts to evaluate the health of the remote service
        * `allowedErrorPercentage`
            * Value: Float
            * Percentage of errors at which the circuit breaker will open and stop any more outgoing requests
        * `testWaitInMilliSeconds`
            * Value: Integer
            * Number of milliseconds before trying to see if an open circuit can be closed
* fallback
    * Value: function
    * A callback function to be called when the circuit breaker records a failed service request.

## Testing

This project uses [phpunit](https://phpunit.de/) for unit testing.

After cloning, run `composer install --dev` to make sure you have all the dependencies. You can then run `vendor/bin/phpunit tests` to run the unit tests.

## Useful Links

* Based off Upwork's [Phystrix](https://github.com/upwork/phystrix). Unlike phystrix though, PHPCircuitBreaker does not make use of the command pattern which is very uncommon in the PHP world.
* Which in turn is based off Netflix's Hystrix: https://github.com/Netflix/Hystrix/wiki
* The Circuit Breaker Pattern: https://martinfowler.com/bliki/CircuitBreaker.html

## Project Structure

This project conforms to the [PSR-2 PHP coding standard](http://www.php-fig.org/psr/psr-2/). To test your code against the standard, you can run `vendor/bin/phpcs --extensions=php --standard=PSR2 -p src/ tests/`
