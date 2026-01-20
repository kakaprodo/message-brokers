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
    protected $signature = 'message-broker:consume';

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
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        MessageBroker::listenToMessages();

        return 0;
    }
}
