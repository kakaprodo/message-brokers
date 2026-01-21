
<?php

use Kakaprodo\MessageBroker\Brokers\RabbitMq\Routing\Route;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\Data\MessageData;

/*
|--------------------------------------------------------------------------
| RabbitMQ Routes
|--------------------------------------------------------------------------
|
| Here is where you may define all of the RabbitMQ routes for your application.
| Each route is associated with an exchange or routing key and a handler.
| Handlers can be closures, php action classes, or CustomActionBuilder form 
| custom data.
| 
| Examples:
|   - Route::make()->any()->subscribe('exchange-name', function (MessageData $dataMessage) { ... });
|   - Route::make()->subscribe('exchange-name')->listenTo('routing-key', HandlerClass::class);
|   - Route::make()->subscribe('exchange-name')->listenToMany([
|       'key1' => function(MessageData $dataMessage) { ... },
|       'key2' => HandlerClass::class,
|   ]);
|
| These routes define how messages are processed when published to RabbitMQ.
|
| Publishing message:
| Kakaprodo\MessageBroker\Facades\MessageBroker::sendTo("order-lifecycle", ['message' => "order paid "], "paid")
| Kakaprodo\MessageBroker\Facades\MessageBroker::broadcast("hello-world", ['message' => "it's a new day "])
*/


Route::make()->any()->subscribe('hello-world', function (MessageData $dataMessage) {
    dump($dataMessage->payload, $dataMessage->getRawPayload());
});

Route::make()
    ->subscribe('order-lifecycle')
    ->listenToMany([
        'created' => function (MessageData $dataMessage) {
            dump($dataMessage->payload);
        },
        'paid' => MyOrderPaidAction::class, // can also be a custom-data action class
    ]);

class MyOrderPaidAction
{
    public function handle(MessageData $dataMessage)
    {
        dump($dataMessage->payload);
    }
}
