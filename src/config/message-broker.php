<?php

return [
    /**
     * This should be any class that has a handle method.
     * When an error occurs, the package will inject the 
     * exception in the handle method
     */
    'error_listner_class' => null,

    /**
     * The type of the message broker you want to you. for
     * now, it should be only rabbimq but in the future , 
     * we can support kafka as well
     */
    'broker_type' => 'rabbitmq',

    /**
     * A word with no space that should be appended to queue names
     * so the names don't conflict because of the app environment.
     * example: config('app.env')
     */
    'append_to_queue_names' => null,

    /**
     * File path in which subscription to mssage broker will be defined
     */
    'route_path' => base_path('routes/message-broker.php'),

    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'localhost'),
        'port' => env('RABBITMQ_POSRT', '5672'),
        'username' => env('RABBITMQ_USERNAME'),
        'password' => env('RABBITMQ_PASSWORD'),
        'vhost' => env('RABBITMQ_VHOST', '/')
    ],

];
