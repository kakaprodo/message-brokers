<?php

namespace Kakaprodo\MessageBroker;

use Illuminate\Console\Command;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\RabbitMqService;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\Routing\RouteBuilder;

class MessageBroker
{
    protected ?RabbitMqService $rabbitMqService;

    public function __construct()
    {
        $this->rabbitMqService =  RabbitMqService::init();
        $this->rabbitMqService->shouldCloseConnection(false);
    }

    /**
     * Establish connection with rabbitmq, bind queues and start
     * listening on events
     * 
     * This method will be called in a command handle
     */
    public static function listenToMessages(Command $command)
    {
        RouteBuilder::resolveRoutes($command);
    }

    /**
     * Send a fanout message
     */
    public  function broadcast(string $exchangeName, array $messagePayload)
    {
        return $this->rabbitMqService
            ->setExchangeName($exchangeName)
            ->sendBroadcast(json_encode($messagePayload));
    }

    /**
     * Send a direct message
     */
    public function sendTo(
        string $exchangeName,
        array $messagePayload,
        string $routingKey
    ) {
        return $this->rabbitMqService
            ->setExchangeName($exchangeName)
            ->sendDirect(json_encode($messagePayload), $routingKey);
    }
}
