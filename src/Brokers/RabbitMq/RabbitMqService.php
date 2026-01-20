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
    protected $exchangeName;

    /**
     * Supported exchange types
     */
    const EXCHANGE_TYPE_DIRECT = "direct";
    const EXCHANGE_TYPE_FANOUT = "fanout";
    const EXCHANGE_TYPE_TOPIC = "topic";
    const EXCHANGE_TYPE_HEADERS = "headers";

    public function __construct()
    {
        $vHost = config('message-broker.rabbitmq.vhost', "/");

        $this->mqCoreService = RabbitMqServiceCore::connect($vHost);

        $this->channel =  $this->mqCoreService->channel();
    }

    /**
     * init the rabbitmq connection with the route and vhost
     */
    public static function init(): RabbitMqService
    {
        return new self();
    }

    /**
     * set a new route name
     */
    public function setExchangeName($exchangeName)
    {
        $this->exchangeName = $exchangeName;

        return $this;
    }

    /**
     * generate a unique qeueu name for each listener
     */
    public function generateQueueName($exchangeType)
    {
        $appendToQueue = config('message-broker.append_to_queue_names');
        $appendToQueue = $appendToQueue ? '.' . $appendToQueue : '';

        return $this->exchangeName . '.' . $exchangeType . '.' . (Str::slug(config('app.name'))) . $appendToQueue;
    }

    /**
     * a method that will publish message using the fanout 
     * exchange type
     */
    public function sendBroadcast(string $message)
    {
        $this->channel->exchange_declare($this->exchangeName, 'fanout', false, true, false);

        $this->mqCoreService->send($this->channel, function (AMQPChannel $channel) use ($message) {

            $message =  $this->mqCoreService->formatMessage($message);

            $channel->basic_publish($message, $this->exchangeName);
        });

        return $this;
    }

    /**
     * Publish messages to given routing keys only
     */
    public function sendDirect(string $message, $routingKey = '')
    {
        $this->channel->exchange_declare($this->exchangeName, 'direct', false, true, false);

        $this->mqCoreService->send($this->channel, function (AMQPChannel $channel) use ($message, $routingKey) {

            $message =  $this->mqCoreService->formatMessage($message);

            $channel->basic_publish($message, $this->exchangeName, $routingKey);
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
     * 
     * @param Closure|CustomActionBuilder|null $handler 
     */
    public function listenToBroadcast($handler = null)
    {
        $this->channel->exchange_declare($this->exchangeName, 'fanout', false, true, false);

        [$queueName] = $this->channel->queue_declare(
            $this->generateQueueName('broadcast'),
            false,
            true,
            false,
            false,
            false,
            $this->mqCoreService->useQuorumQueue()
        );

        $this->channel->queue_bind($queueName, $this->exchangeName);

        $this->channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($handler) {
                if ($handler) {
                    $this->dispatchTasks($msg->getBody(), $handler);
                }

                $msg->ack();
            }
        );

        return $this;
    }


    /**
     * listen to messages published to certain routing keys only
     */
    public function listenToDirect($routingKeysMappedWithHandler = [])
    {
        $this->channel->exchange_declare($this->exchangeName, 'direct', false, true, false);



        foreach ($routingKeysMappedWithHandler as $routingKey => $handler) {
            [$queueName] = $this->channel->queue_declare(
                $this->generateQueueName('direct.' . $routingKey),
                false,
                true,
                false,
                false,
                false,
                $this->mqCoreService->useQuorumQueue()
            );

            $this->channel->queue_bind($queueName, $this->exchangeName, $routingKey);

            $this->channel->basic_consume(
                $queueName,
                '',
                false,
                false,
                false,
                false,
                function (AMQPMessage $msg) use ($handler) {

                    $this->dispatchTasks($msg->getBody(), $handler, $msg->getRoutingKey());

                    $msg->ack();
                }
            );
        }

        return $this;
    }

    /**
     * Decide how the message will be consumed based on the routingKey
     * 
     * @param string $message
     * @param Closure|CustomActionBuilder|null $handler 
     * @param string|null $routingKey
     */
    protected function dispatchTasks($message, $handler = null, $routingKey = null)
    {
        try {
            DispatchRabbitMqTaskService::process([
                'message' => $message,
                'routing_key' => $routingKey,
                'handler' => $handler
            ]);
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
