<?php

namespace Kakaprodo\MessageBroker\Utilities;

use Throwable;

class Util
{

    /**
     * Check whether a provided callable is
     * a closure and not a string
     */
    public static function isCallable($callable)
    {
        return is_callable($callable) && gettype($callable) != 'string';
    }

    /**
     * call a given public static function if it's callable otherwise return it
     * as a noormal variable
     */
    public static function callFunction($myFunction, ...$args)
    {
        if (static::isCallable($myFunction)) return $myFunction(...$args);

        return $myFunction;
    }

    /**
     * catch any error then send it its handler class
     */
    public static function catch(Throwable $th)
    {
        $errorHandlerClass = config('message-broker.error_listner_class');

        if (!$errorHandlerClass) return;

        if (!class_exists($errorHandlerClass)) return;

        if (!method_exists($errorHandlerClass, 'handle')) return;

        app($errorHandlerClass)->handle($th);
    }
}
