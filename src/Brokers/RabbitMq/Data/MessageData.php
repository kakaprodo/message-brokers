<?php

namespace Kakaprodo\MessageBroker\Brokers\RabbitMq\Data;

use Closure;
use Kakaprodo\CustomData\CustomData;
use Kakaprodo\CustomData\Helpers\CustomActionBuilder;

/**
 * @property string $message : encoded version of published message
 * @property array $payload : decoded version of the message  sent
 * @property string|null $routing_key 
 * @property string|null $exchange 
 * @property Closure|CustomActionBuilder|null $handler 
 */
class MessageData extends CustomData
{
    protected function expectedProperties(): array
    {
        return [
            'message' => $this->property()->string(),
            'payload?' => $this->property()->castTo(fn() => json_decode($this->message, true)),
            'routing_key?' => $this->dataType()->string(),
            'exchange?' => $this->dataType()->string(),
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

    /**
     * Get  payload in encoded format
     */
    public function getRawPayload(): string
    {
        return $this->message;
    }
}
