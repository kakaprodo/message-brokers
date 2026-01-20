<?php

namespace Kakaprodo\MessageBroker;

use Illuminate\Support\ServiceProvider;
use Kakaprodo\MessageBroker\Commands\ConfigInstallCommand;
use Kakaprodo\MessageBroker\Commands\MessageBrokerConsumeMessage;

class MessageBrokerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/message-broker.php',
            'message-broker'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();

        $this->stackToPublish();
    }

    protected function registerCommands()
    {
        if (!$this->app->runningInConsole()) return;

        $this->commands([
            MessageBrokerConsumeMessage::class,
            ConfigInstallCommand::class
        ]);
    }


    public function stackToPublish()
    {
        $this->publishes([
            __DIR__ . '/config/message-broker.php' => config_path('message-broker.php'),
        ], 'message-broker-config');

        $this->publishes([
            __DIR__ . '/routes/message-broker.php' => base_path('routes/message-broker.php'),
        ], 'message-broker-routes');
    }
}
