<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Kakaprodo\MessageBroker\Utilities\Util;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\Core\RabbitMqServiceCore;

class RabbitMqService
{
    /**
     * @var RabbitMqServiceCore
     */
    protected $mqCoreService;
    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * Routes names
     */
    const ROUTE_BROADCAST_ANY = "broadcast-any";
    const ROUTE_DIRECT_ANY = "direct-any";

    /**
     * Queue names
     */
    const DIRECT_QUEUE = 'direct_queue';
    const FANOUT_QUEUE = 'fanout_queue';

    public function __construct($routeName, $vHost = null)
    {
        $vHost = $vHost ?? config('services.rabbitmq.vhost');

        $this->mqCoreService = RabbitMqServiceCore::connect($vHost);

        $this->channel =  $this->mqCoreService->channel();
        $this->routeName = $routeName;
    }

    /**
     * init the rabbitmq connection with the route and vhost
     */
    public static function init($routeName, $vHost = null): RabbitMqService
    {
        return new self($routeName, $vHost);
    }

    /**
     * set a new route name
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * generate a unique qeueu name for each listener
     */
    public function generateQueueName($exchangeType)
    {
        return $this->routeName . '.' . $exchangeType . '.' . (Str::slug(config('app.name')));
    }

    /**
     * a method that will publish message using the fanout 
     * exchange type
     */
    public function sendBroadcast($message, $routingKey = '')
    {
        $this->channel->exchange_declare($this->routeName, 'fanout', false, true, false);

        $this->mqCoreService->send($this->channel, function (AMQPChannel $channel) use ($message, $routingKey) {

            $message =  $this->mqCoreService->formatMessage($message);

            $channel->basic_publish($message, $this->routeName, $routingKey);
        });

        return $this;
    }

    /**
     * Publish messages to given routing keys only
     */
    public function sendDirect($message, $routingKey = '')
    {
        $this->channel->exchange_declare($this->routeName, 'direct', false, true, false);

        $this->mqCoreService->send($this->channel, function (AMQPChannel $channel) use ($message, $routingKey) {

            $message =  $this->mqCoreService->formatMessage($message);

            $channel->basic_publish($message, $this->routeName, $routingKey);
        });

        return $this;
    }

    /**
     * listen to multiple routes and exchange types using
     * one channel and one connection
     */
    public function listen(callable $builderCallable)
    {
        // Manage load-balance: breadcast up to 10 messages, and keep doing it if the FIFO got achnowledged
        $this->channel->basic_qos(null, 10, null);

        $builderCallable($this);

        while ($this->channel->is_open()) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->mqCoreService->close();
    }

    /**
     * listen to messages broadcasted
     */
    public function listenToBroadcast($routingKeys = [], ?callable $callMeBack = null)
    {
        $this->channel->exchange_declare($this->routeName, 'fanout', false, true, false);

        [$queueName] = $this->channel->queue_declare(
            $this->generateQueueName(self::FANOUT_QUEUE),
            false,
            true,
            false,
            false,
            false,
            $this->mqCoreService->useQuorumQueue()
        );

        $routingKeys = $routingKeys == [] ? [''] : $routingKeys;

        foreach ($routingKeys as $routingKey) {
            $this->channel->queue_bind($queueName, $this->routeName, $routingKey);
        }

        $this->channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($callMeBack) {

                Util::callFunction($callMeBack, $msg->getBody(), $msg->getRoutingKey());

                $this->dispatchTasks($msg->getBody(), $msg->getRoutingKey(), $msg);
            }
        );

        return $this;
    }


    /**
     * listen to messages published to certain routing keys only
     */
    public function listenToDirect($routingKeys = [], ?callable $callMeBack = null)
    {
        $this->channel->exchange_declare($this->routeName, 'direct', false, true, false);

        [$queueName] = $this->channel->queue_declare(
            $this->generateQueueName(self::DIRECT_QUEUE),
            false,
            true,
            false,
            false,
            false,
            $this->mqCoreService->useQuorumQueue()
        );

        $routingKeys = $routingKeys == [] ? [''] : $routingKeys;

        foreach ($routingKeys as $routingKey) {
            $this->channel->queue_bind($queueName, $this->routeName, $routingKey);
        }

        $this->channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($callMeBack) {

                Util::callFunction($callMeBack, $msg->getBody(), $msg->getRoutingKey());
                $this->dispatchTasks($msg->getBody(), $msg->getRoutingKey(), $msg);
            }
        );

        return $this;
    }

    /**
     * Decide how the message will be consumed based on the routingKey
     */
    protected function dispatchTasks($message, $routingKey = null, ?AMQPMessage $msg = null)
    {
        try {
            DispatchRabbitMqTaskService::process([
                'message' => $message,
                'routing_key' => $routingKey
            ]);

            $msg->ack();
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            Log::info(json_encode([
                'message' => $message,
                'routing_key' => $routingKey
            ]));

            dump($th->getMessage(), [
                'message' => $message,
                'routing_key' => $routingKey
            ]);

            // TODO: call the configured error handler class
        }
    }
}
