
<?php

use Kakaprodo\MessageBroker\Brokers\RabbitMq\Routing\Route;

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
|   - Route::make()->any()->subscribe('exchange-name', function ($payload) { ... });
|   - Route::make()->subscribe('exchange-name')->listenTo('routing-key', HandlerClass::class);
|   - Route::make()->subscribe('exchange-name')->listenToMany([
|       'key1' => function($payload) { ... },
|       'key2' => HandlerClass::class,
|   ]);
|
| These routes define how messages are processed when published to RabbitMQ.
|
*/


Route::make()->any()->subscribe('hello-world', function ($payload) {
    dump($payload);
});

Route::make()->subscribe('order-lifecycle')->listenToMany([
    'created' => function ($payload) {
        dump($payload);
    },
    'paid' => MyOrderPaidAction::class, // can also be a custom-data action class
]);

class MyOrderPaidAction
{
    public function handle($payload)
    {
        dump($payload);
    }
}
