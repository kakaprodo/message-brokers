<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq;

use Kakaprodo\MessageBroker\Utilities\Util;
use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\Data\DispatchRabbitMqTaskData;

class DispatchRabbitMqTaskService extends CustomActionBuilder
{
    /**
     * the published message
     * 
     * @var mixed
     */
    protected $message;

    public function handle(DispatchRabbitMqTaskData $data)
    {
        if ($data->handlerIsCustomDataAction()) {
            return ($data->handler)::process($data->getMessagePayload());
        } elseif ($data->handlerHasHandleMethod()) {
            return app()->call([$data->handler, 'handle'], [
                'payload' => $data->getMessagePayload()
            ]);
        } elseif (Util::isCallable($data->handler)) {
            return app()->call($data->handler, [
                'payload' => $data->getMessagePayload()
            ]);
        }
    }
}
