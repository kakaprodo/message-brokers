<?php

return [

    /**
     * Use as the message's origin identifier 
     */
    'app_name' => env('APP_NAME'),

    /**
     * the command to listen to all messages that are being
     * published
     */
    'listner_command' => 'message-broker:consume',

    /**
     * This should be any class that has a "handle" method.
     * When an error occurs, the package will inject the 
     * exception in the handle method.
     * 
     * Note: you should not throw an exception in its handle method.
     *       otherwise the rabbitmq connection will be lost.
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
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'heartbeat' => env('RABBITMQ_HEARTBEAT', 0),
        'read_write_timeout' =>  env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),
    ],

];
