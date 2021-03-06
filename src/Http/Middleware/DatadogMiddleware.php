<?php

declare(strict_types=1);

namespace AirSlate\Datadog\Http\Middleware;

use Illuminate\Container\Container;
use AirSlate\Datadog\Services\Datadog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatadogMiddleware
 *
 * @package AirSlate\Datadog\Http\Middleware
 */
class DatadogMiddleware
{
    /**
     * @var Datadog
     */
    private $datadog;

    /**
     * @var string
     */
    private $namespace;

    /**
     * DatadogMiddleware constructor.
     * @param Datadog $datadog
     * @param string $namespace
     */
    public function __construct(Datadog $datadog, string $namespace)
    {
        $this->namespace = $namespace;
        $this->datadog = $datadog;
    }

    /**
     * @param Request $request
     * @param $next
     *
     * @return mixed
     */
    public function handle(Request $request, $next)
    {
        $startTimer = microtime(true);
        $response = $next($request);
        $this->sendMetrics($request, $response, $startTimer);
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param float|null $startTimer
     */
    private function sendMetrics(Request $request, Response $response, float $startTimer = null): void
    {
        $start = \defined('LARAVEL_START') ? LARAVEL_START : $startTimer;
        $duration = microtime(true) - $start;

        $tags = [
            'code' => $response->getStatusCode(),
            'method' => $request->method(),
        ];

        // send response time
        $this->datadog->timing("{$this->namespace}.response_time", $duration, 1, $tags);
    }
}
