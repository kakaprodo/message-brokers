<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq\Data;

use Kakaprodo\CustomData\CustomData;

/**
 * @property mixte message 
 * @property string routing_key 
 */
class DispatchRabbitMqTaskData extends CustomData
{
    protected function expectedProperties(): array
    {
        return [
            'message',
            'routing_key?' => $this->dataType()->string()
        ];
    }
}
