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
        config(['message-broker.heartbeat' => 0]);

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
            ->sendBroadcast($this->appendToPayloadThenFormat($messagePayload));
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
            ->sendDirect($this->appendToPayloadThenFormat($messagePayload), $routingKey);
    }

    private function appendToPayloadThenFormat(array $messagePayload = [])
    {
        return json_encode([
            ...($messagePayload),
            'message_broker_origin' => config('message-broker.app_name')
        ]);
    }
}
