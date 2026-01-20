<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq\Routing;

use Illuminate\Support\Facades\Log;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\RabbitMqService;

class RouteBuilder
{
    static $allRoutes = [];

    /**
     * Call all the defined routesso they are added to $allRoutes
     */
    public static function loadRoutes()
    {
        RouteBuilder::$allRoutes = [];

        $routePath = config('message-broker.route_path');

        require $routePath;
    }

    /**
     * Connect routes to rabbitMq
     */
    public static function resolveRoutes()
    {
        $service = null;

        try {
            $service = RabbitMqService::init();
        } catch (\Throwable $th) {
            // TODO:: log error
            Log::info('Rabbitmq connection: ' . $th->getMessage());
        }

        if (!($service instanceof RabbitMqService)) return;

        static::loadRoutes();
        dd(static::$allRoutes);
        try {
            $service->listen(function (RabbitMqService $mqService) {
                foreach (static::$allRoutes as $routeSettings) {
                    [$exchangeName, $handler] = $routeSettings['exchange_name'];

                    $mqService->setExchangeName($exchangeName);

                    $exchangeType = $routeSettings['exchange_type'];

                    if ($exchangeType === RabbitMqService::EXCHANGE_TYPE_FANOUT) {
                        $mqService->listenToBroadcast($handler);
                    } else {
                        $mqService->listenToDirect($routeSettings['routing_keys']);
                    }
                }
            });
        } catch (\Throwable $th) {
            // TODO:: log error
            Log::info('Rabbitmq Listener cmd: ' . $th->getMessage());
        }
    }
}
