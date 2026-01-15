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
    'broker_type' => 'rabbitmq'
];
