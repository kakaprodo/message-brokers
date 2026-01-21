<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq\Routing;

use Illuminate\Console\Command;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\RabbitMqService;
use Kakaprodo\MessageBroker\Exceptions\MessageBrokerException;
use Kakaprodo\MessageBroker\Utilities\Util;

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
    public static function resolveRoutes(Command $command)
    {
        $service = RabbitMqService::init();

        static::loadRoutes();

        try {
            $service?->listen(function (RabbitMqService $mqService) {
                foreach (static::$allRoutes as $routeSettings) {
                    [$exchangeName, $handler] = $routeSettings['exchange_name'];

                    $mqService->setExchangeName($exchangeName);

                    $exchangeType = $routeSettings['exchange_type'];

                    if ($exchangeType === RabbitMqService::EXCHANGE_TYPE_FANOUT) {
                        $mqService->listenToBroadcast($handler);
                    } else {
                        $routingKeys = $routeSettings['routing_keys'];
                        if (empty($routingKeys)) {
                            throw  new MessageBrokerException("The {$exchangeType} {$exchangeName} exchange misses the routing keys.");
                        }

                        $mqService->listenToDirect($exchangeType, $routeSettings['routing_keys']);
                    }
                }
            });
        } catch (\Throwable $th) {
            $command->error('Rabbitmq listener: ' . $th->getMessage());
            Util::catch($th, [
                "event" => "When binding routes to queue",
                'error_message' => null,
                'sent_message' => null,
                'routing_key' => null,
                "exchange" => null,
            ]);
        }
    }
}
