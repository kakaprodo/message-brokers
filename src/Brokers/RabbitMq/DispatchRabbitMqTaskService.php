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
        if (!$data->routing_key) return;
        $this->message = $data->message;

        $handler =  [
            RabbitConstantService::TRANSFER_WALLET_OWNERSHIP => fn() => $this->transferWarehouseOnwershipListerner(),
        ][$data->routing_key] ?? null;

        return Util::callFunction($handler);
    }
}
