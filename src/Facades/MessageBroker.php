<?php

namespace Kakaprodo\MessageBroker\Facades;

use Illuminate\Support\Facades\Facade;
use Kakaprodo\MessageBroker\MessageBroker as MessageBrokerHandler;

/**
 * @method static mixed broadcast(string $exchangeName, array $messagePayload)
 * @method static mixed sendTo(string $exchangeName, array $messagePayload, string $routingKey)
 *
 * Facade for {@see \Kakaprodo\MessageBroker\MessageBroker} methods used by IDEs for autocompletion.
 */
class MessageBroker extends Facade
{

    protected static function getFacadeAccessor()
    {
        return MessageBrokerHandler::class;
    }
}
