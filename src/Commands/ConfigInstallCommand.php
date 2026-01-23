<?php

namespace Kakaprodo\MessageBroker\Commands;

use Illuminate\Console\Command;

class ConfigInstallCommand extends Command
{
    protected $signature = 'message-broker:install {--force}';

    protected $description = 'Generate the configuration file of the package';

    public function handle()
    {
        $params = [
            [
                '--provider' => "Kakaprodo\MessageBroker\MessageBrokerServiceProvider",
                '--tag' => "message-broker-config"
            ],
            [
                '--provider' => "Kakaprodo\MessageBroker\MessageBrokerServiceProvider",
                '--tag' => "message-broker-routes"
            ]
        ];

        foreach ($params as $param) {
            if ($this->option('force') === true) {
                $param['--force'] = true;
            }

            $this->call('vendor:publish', $param);
        }
    }
}
