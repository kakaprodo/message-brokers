# Message-brokers

A PHP and Laravel package that simplifies server-to-server communication using RabbitMQ.

## Prerequisites

Before installing make sure you support:

```
"php": ">=8.0",
"laravel/framework": ">=8.0",
"kakaprodo/custom-data": ">=2.3.3 || dev-develop",
"php-amqplib/php-amqplib": "^3.7"
```

## Installation

Run the following composer command:

```sh
composer require kakaprodo/message-broker
```

## Setup

### 1. Publishing files

Before start using the package, you should publish some files it provides by running
the following artisan command:

```sh
php artisan message-broker:install
```

This command will publish the `config/message-broker.php` and the `routes/message-broker.php` files

### 2. Provide env variables

Provide in your `.env` file the following RabbitMq keys for connection:

```env
RABBITMQ_HOST=localhost
RABBITMQ_POSRT=5672
RABBITMQ_USERNAME=
RABBITMQ_PASSWORD=
RABBITMQ_VHOST='/'
```

## Usage

### Sending Messages

You can publish a message to one specific subscriber or you can broadcast to many subscribers.

#### Send direct message

To send a message to one specific subscriber use the `sendTo` method, by passing to it, the `exchange_name`, `payload_message`, and the `routing_key`.

```php
use Kakaprodo\MessageBroker\Facades\MessageBroker;

$exchangeName = "order-lifecycle";
$payloadMessage = [
    'message' => "order created"
];
$routingKey = "created";

MessageBroker::sendTo($exchangeName, $payloadMessage, $routingKey);
```

#### broadcast message

To send a message to many subscribers use the `broadcast` method, by passing to it the `exchange_name` and the `payload_message`.

```php
use Kakaprodo\MessageBroker\Facades\MessageBroker;

$exchangeName = "hello-world";
$payloadMessage = [
    'message' => "It's a new day"
];

MessageBroker::broadcast($exchangeName, $payloadMessage);
```

### Subscribing to exchange

You can subscribe to broadcasted(`fanout`) messages, to `direct` message and to `topic` messages.
To define the route, open the `routes/message-broker.php` then define you route the following way:

**Brodcasted Messages**

To subscribe to brodcasted message you can use `any` when defining the route exchange.
Then provide the `exchange_name` and its callback to the `subscribe` method.

```php
    use Kakaprodo\MessageBroker\Brokers\RabbitMq\Data\MessageData;

    $exchangeName = "hello-world";

    Route::make()->any()->subscribe($exchangeName, function (MessageData $dataMessage) {
      dump($dataMessage->payload, $dataMessage->getRawPayload());
    });
```

**Direct Messages**

To subscribe to direct message simply pass to the `subscribe` method, the `exchange_name` and the callback of its routing key.

```php
    use Kakaprodo\MessageBroker\Brokers\RabbitMq\Data\MessageData;

    $exchangeName = 'order-lifecycle';
    $routingKey = "created";
    Route::make()
        ->subscribe($exchangeName)
        ->listenTo($routingKey, function (MessageData $dataMessage) {
            dump($dataMessage->payload);
        });
```

You may also bound many routing keys to a single exchange name by using the `listenToMany` method:

```php
Route::make()
    ->subscribe('order-lifecycle')
    ->listenToMany([
        'created' => function (MessageData $dataMessage) {
            dump($dataMessage->payload);
        },
        'paid' => MyOrderPaidAction::class, // can also be a custom-data action class
    ]);
```

**Topic Messages**

```php
Route::make()
    ->tipic()
    ->subscribe($topicExchange)
    ->listenTo($topicRoutingKey, function (MessageData $dataMessage) {
        dump($dataMessage->payload);
    });
```

### Messages handlers

The package support 3 types of handlers:

- Closure handler

    ```php
    $handler = function (MessageData $dataMessage) {
        dump($dataMessage->payload);
    };

    Route::make()
            ->subscribe($exchangeName)
            ->listenTo($routingKey, $handler);
    ```

- Php Action Classes

    The action should have a `handle` method

    ```php
    use Kakaprodo\MessageBroker\Brokers\RabbitMq\Data\MessageData;

    class MyOrderPaidAction
    {
        public function handle(MessageData $dataMessage)
        {
            dump($dataMessage->payload);
        }
    }

    Route::make()
            ->subscribe($exchangeName)
            ->listenTo($routingKey, MyOrderPaidAction::class);
    ```

- Action classes from the `kakaprodo/custom-data`

    ```php
    use Kakaprodo\CustomData\CustomData;
    use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
    use Kakaprodo\MessageBroker\Brokers\RabbitMq\Data\MessageData;

    class MyOrderPaidData extends CustomData
    {
        protected function expectedProperties(): array
        {
            return [
                'data_message' => $this->property(MessageData::class),
                'order_number' => $this->property()->number(),
            ];
        }
    }

    class MyOrderPaidAction extends CustomActionBuilder
    {
        public function handle(MyOrderPaidData $data)
        {
            dump($data->all());
        }
    }

    Route::make()
            ->subscribe($exchangeName)
            ->listenTo($routingKey, MyOrderPaidAction::class);
    ```
