<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq;

use Kakaprodo\MessageBroker\Utilities\Util;
use Kakaprodo\CustomData\Helpers\CustomActionBuilder;
use Kakaprodo\MessageBroker\Brokers\RabbitMq\Data\MessageData;

class DispatchRabbitMqTaskService extends CustomActionBuilder
{
    /**
     * the published message
     * 
     * @var mixed
     */
    protected $message;

    public function handle(MessageData $data)
    {
        if ($data->handlerIsCustomDataAction()) {
            return ($data->handler)::process(
                array_merge([
                    'data_message' => $data
                ], $data->payload)
            );
        } elseif ($data->handlerHasHandleMethod()) {
            $handlerInstance = app($data->handler);
            return app()->call([$handlerInstance, 'handle'], [
                'dataMessage' => $data
            ]);
        } elseif (Util::isCallable($data->handler)) {
            return app()->call($data->handler, [
                'dataMessage' => $data
            ]);
        }
    }
}
