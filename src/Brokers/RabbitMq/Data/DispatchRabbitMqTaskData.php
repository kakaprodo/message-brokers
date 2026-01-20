<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq\Data;

use Closure;
use Kakaprodo\CustomData\CustomData;
use Kakaprodo\CustomData\Helpers\CustomActionBuilder;

/**
 * @property array $payload 
 * @property string|null routing_key 
 * @property Closure|CustomActionBuilder|null $handler 
 */
class DispatchRabbitMqTaskData extends CustomData
{
    protected function expectedProperties(): array
    {
        return [
            'message' => $this->property()
                ->string()
                ->castTo(fn($value) => json_decode($value, true))
                ->transform('payload'),
            'routing_key?' => $this->dataType()->string(),
            'handler?',
        ];
    }

    public function handlerIsCustomDataAction()
    {
        $handler = $this->handler;

        if (is_string($handler) && is_subclass_of($handler, CustomActionBuilder::class)) {
            return true;
        }

        return false;
    }

    /**
     * When it's a php action class
     */
    public function handlerHasHandleMethod()
    {
        $handler = $this->handler;

        if (!is_string($handler)) return false;

        if (!class_exists($handler)) return false;

        return method_exists($handler, 'handle');
    }

    public function getMessagePayload(): array
    {
        return array_merge([
            $this->payload
        ], [
            'routing_key' => $this->routing_key
        ]);
    }
}
