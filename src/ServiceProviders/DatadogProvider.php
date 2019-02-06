<?php
declare(strict_types=1);

namespace AirSlate\Datadog\ServiceProviders;

use AirSlate\Datadog\Services\Datadog;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;

/**
 * Class DatadogProvider
 *
 * @package AirSlate\Datadog\ServiceProviders
 */
class DatadogProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                \dirname($this->configPath()) => $this->app->make('path.config')], 'datadog');
        }
        $this->mergeConfigFrom($this->configPath(), 'datadog');
    }

    public function register(): void
    {
        $config = $this->app->get('config')->get('datadog');

        $datadog = new Datadog([
            'host' => $config['statsd_server'] ?? '172.17.0.1',
            'port' => $config['statsd_port'] ?? 8125,
        ]);

        $this->app->singleton(Datadog::class, function() use ($datadog) {
            return $datadog;
        });

        $datadog->addTag('env', $config['environment']);
        $datadog->addTag('app', $config['application_name']);
        $datadog->addTag('ver', $config['application_version']);

        $this->registerRouteMatchedListener($datadog);
    }

    /**
     * Return config path.
     *
     * @return string
     */
    private function configPath(): string
    {
        return \dirname(__DIR__) . '/config/datadog.php';
    }

    /**
     * @param Datadog $datadog
     */
    private function registerRouteMatchedListener(Datadog $datadog): void
    {
        $this->app->make('router')->matched(function(RouteMatched $matched) use ($datadog) {
            $operationName = sprintf(
                '%s/%s/%s',
                strtoupper($matched->request->getScheme()),
                $matched->request->method(),
                $matched->route->uri
            );

            $datadog->addTag('url', $operationName);

            return $operationName;
        });
    }
}