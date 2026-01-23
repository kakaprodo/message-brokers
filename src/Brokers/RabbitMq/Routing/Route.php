<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq\Routing;

use Closure;
use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\RabbitMqService;
use Kakaprodo\MessageBroker\Exceptions\MessageBrokerException;

class Route
{
    protected $index = 0;

    /**
     * setting fro a single route
     */
    protected $settings = [
        'exchange_type' => 'direct', // 'fanout',
        'exchange_name' => null, //['queue_name', $handler],
        'routing_keys' => [] // ['routing_key_name' => $handler]
    ];

    public function __construct()
    {
        array_push(RouteBuilder::$allRoutes, []);

        $this->index = count(RouteBuilder::$allRoutes) - 1;
    }

    /**
     * Create the route instance
     */
    public static function make()
    {
        return new self();
    }

    /**
     * Apply chnages on the known routes
     */
    public function updateGlobalRoutes()
    {
        RouteBuilder::$allRoutes[$this->index] = $this->settings;
    }

    /**
     * Configure the route to listen to any broadcasted message
     */
    public function fanout()
    {
        $this->settings = array_merge(($this->settings), [
            'exchange_type' => RabbitMqService::EXCHANGE_TYPE_FANOUT
        ]);

        $this->updateGlobalRoutes();

        return $this;
    }

    /**
     * Configure the route to listen to any direct message
     */
    public function direct()
    {
        $this->settings = array_merge(($this->settings), [
            'exchange_type' =>  RabbitMqService::EXCHANGE_TYPE_DIRECT
        ]);

        $this->updateGlobalRoutes();

        return $this;
    }

    /**
     * Configure the route to listen to any topic message
     */
    public function topic()
    {
        $this->settings = array_merge(($this->settings), [
            'exchange_type' =>  RabbitMqService::EXCHANGE_TYPE_TOPIC
        ]);

        $this->updateGlobalRoutes();

        return $this;
    }

    /**
     * Configure the route to listen to any broadcasted message
     */
    public function any()
    {
        return static::fanout();
    }

    /**
     * listen to all broadcasted messages
     */
    public function broadcast()
    {
        return static::fanout();
    }

    /**
     * Subscribe to messages published on a given exchange.
     * 
     * @param string $exchangeName
     * @param Closure|array|CustomActionBuilder $handler 
     */
    public function subscribe(string $exchangeName, $handler = null)
    {
        $this->settings = array_merge(($this->settings), [
            'exchange_name' =>  [$exchangeName, $handler]
        ]);

        $this->updateGlobalRoutes();

        return $this;
    }

    /**
     * Bind a single routing key to a subscription exchange.
     * 
     * @param string $routingKey
     * @param Closure|array|CustomActionBuilder $handler 
     */
    public function listenTo(string $routingKey, $handler)
    {
        $routingKeys =  array_merge($this->settings['routing_keys'], [
            $routingKey => $handler
        ]);

        $this->settings = array_merge(($this->settings), [
            'routing_keys' =>  $routingKeys
        ]);

        $this->updateGlobalRoutes();

        return $this;
    }

    /**
     * Bind many routing keys to a subscription exchange.
     * 
     * @param array<string, Closure|array|CustomActionBuilder $handler> $routingKeysMapWithHandlers
     */
    public function listenToMany(array $routingKeysMapWithHandlers)
    {
        foreach ($routingKeysMapWithHandlers as $routingKey => $handler) {
            if (!$handler) {
                throw  new MessageBrokerException("The routingKey($routingKey) provided with no handler.");
            }

            static::listenTo($routingKey,  $handler);
        }

        return $this;
    }
}
