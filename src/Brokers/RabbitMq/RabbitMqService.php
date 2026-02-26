<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq;

use Closure;
use Illuminate\Support\Str;
use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\Core\RabbitMqServiceCore;
use Kakaprodo\MessageBroker\Utilities\Util;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

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
     * Wether acknowlegment should executed before handlers
     */
    protected $acknowledgeBeforeHandler = false;

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
     * Decide whether a connection should be closed once a message is 
     * sent
     */
    public function shouldCloseConnection($decision = true)
    {
        $this->mqCoreService->setShouldCloseConnection($decision);

        return $this;
    }

    /**
     * acknowlegment rabbitmq message before executing handlers
     */
    public function shouldAcknowledgeBeforeHandler($decision = true)
    {
        $this->acknowledgeBeforeHandler = $decision;

        return $this;
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
    public function listen(callable $builderCallable, $shouldKeepConnectionAlive = true)
    {
        // Manage load-balance: breadcast up to 10 messages, and keep doing it if the FIFO got achnowledged
        $this->channel->basic_qos(null, 10, null);

        $builderCallable($this);

        if ($shouldKeepConnectionAlive) {
            while ($this->channel->is_open()) {
                $this->channel->wait();
            }
        } else {
            $this->channel->wait(null, false, 1);
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
                $this->executeHandlerWithTryCatch(
                    fn() => $this->dispatchTasks(
                        message: $msg->getBody(),
                        handler: $handler,
                        exchange: $msg->getExchange()
                    ),
                    $msg
                );
            }
        );

        return $this;
    }


    /**
     * listen to messages published to certain routing keys only
     * 
     * @param string $exchangeType : can be direct or topic
     * @param array<routingKey, handler> $routingKeysMappedWithHandler
     */
    public function listenToDirect(string $exchangeType, array $routingKeysMappedWithHandler)
    {
        $this->channel->exchange_declare($this->exchangeName, $exchangeType, false, true, false);

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
                    $this->executeHandlerWithTryCatch(
                        fn() => $this->dispatchTasks(
                            message: $msg->getBody(),
                            handler: $handler,
                            routingKey: $msg->getRoutingKey(),
                            exchange: $msg->getExchange(),
                        ),
                        $msg
                    );
                }
            );
        }

        return $this;
    }

    /**
     * execute the handler and only aknowledge message 
     * if handler did not fail
     */
    protected function executeHandlerWithTryCatch(Closure $executeMe, AMQPMessage $msg)
    {
        try {
            if ($this->acknowledgeBeforeHandler)  $msg->ack();

            $executeMe();

            if (!$this->acknowledgeBeforeHandler)  $msg->ack();
        } catch (\Throwable $th) {
            $info = [
                'error_message' => $th->getMessage(),
                'sent_message' => $msg->getBody(),
                'routing_key' => $msg->getRoutingKey(),
                "exchange" =>  $msg->getExchange(),
                "event" => "When executing the handler"
            ];
            Util::catch($th, $info);
        }
    }

    /**
     * Decide how the message will be consumed based on the routingKey
     * 
     * @param string $message
     * @param Closure|CustomActionBuilder|null $handler 
     * @param string|null $routingKey
     */
    protected function dispatchTasks($message, $handler = null, $routingKey = null, $exchange = null)
    {
        DispatchRabbitMqTaskService::process([
            'message' => $message,
            'routing_key' => $routingKey,
            'handler' => $handler,
            'exchange' => $exchange
        ]);
    }
}
