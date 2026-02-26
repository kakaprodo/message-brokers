<?php

namespace Kakaprodo\MessageBroker\Commands;

use Illuminate\Console\Command;
use Kakaprodo\MessageBroker\MessageBroker;

class MessageBrokerConsumeMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message-broker:consume {--ack : Automatically acknowledge all stucked messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to consume broadcasted rabbitmq messages';

    protected $errorCounter = 0;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->signature = str_replace(
            "message-broker:consume",
            config('message-broker.listner_command'),
            $this->signature
        );

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        MessageBroker::listenToMessages($this);

        return 0;
    }
}
