<?php

namespace Kakaprodo\MessageBroker;

use Kakaprodo\MessageBroker\Brokers\RabbitMq\Routing\RouteBuilder;

class MessageBroker
{

    public static function listenToMessages()
    {
        RouteBuilder::resolveRoutes();
    }
}
