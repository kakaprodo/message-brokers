<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq\Core;

use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqServiceCore
{

    /**
     * @var AMQPStreamConnection
     */
    public $connection;

    /**
     * indicate whether connection need to be closed 
     * after sending a message
     */
    public $shouldCloseConnection = true;

    public function __construct($vHost = "/")
    {
        $this->connection = new AMQPStreamConnection(
            host: config('message-broker.rabbitmq.host'),
            port: config('message-broker.rabbitmq.port'),
            user: config('message-broker.rabbitmq.username'),
            password: config('message-broker.rabbitmq.password'),
            vhost: $vHost,
            read_write_timeout: config('message-broker.rabbitmq.read_write_timeout', 3.0),
            heartbeat: config('message-broker.rabbitmq.heartbeat', 0),
        );
    }

    /**
     * create instance and create connection
     */
    public static function connect($vHost = "/"): RabbitMqServiceCore
    {
        return new self($vHost);
    }

    /**
     * Fetches a channel object identified by the numeric channel_id, 
     * or create that object if it doesn't already exist.
     */
    public function channel($id = null): AMQPChannel
    {
        return $this->connection->channel($id);
    }

    /**
     * format the message to send
     */
    public function formatMessage($message): AMQPMessage
    {
        return new AMQPMessage($message, [
            'delivery_mode' => 2
        ]);
    }

    public function close()
    {
        $this->connection->close();
    }

    /**
     * send a message on a given channel then close the channel
     * based on some instructions
     */
    public function send(AMQPChannel $channel, callable $funcToCall)
    {
        $funcToCall($channel, $this);

        if (!$this->shouldCloseConnection) return;

        $channel->close();
        $this->close();
    }

    /**
     * statement to check whether the connection and channel should 
     * be closed
     */
    public function setShouldCloseConnection($statement)
    {
        $this->shouldCloseConnection = (bool) $statement;

        return $this;
    }

    /**
     * define Quorum as default queue
     */
    public function useQuorumQueue($addAttributes = []): AMQPTable
    {
        return new AMQPTable(
            array_merge([
                'x-queue-type' => 'quorum',
                //'x-expires' => 7 * 24 * 60 * 60 * 1000 // 7 days in ms
            ], $addAttributes)
        );
    }
}
