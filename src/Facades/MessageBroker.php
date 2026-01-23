<?php

namespace Kakaprodo\MessageBroker\Facades;

use Illuminate\Support\Facades\Facade;

class MessageBroker extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'messagebroker';
    }
}
