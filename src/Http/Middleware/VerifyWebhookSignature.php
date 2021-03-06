<?php

namespace SierraTecnologia\Cashier\Http\Middleware;

use Closure;
use SierraTecnologia\WebhookSignature;
use SierraTecnologia\Error\SignatureVerification;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as Config;

final class VerifyWebhookSignature
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The configuration repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @param  \Illuminate\Contracts\Config\Repository      $config
     * @return void
     */
    public function __construct(Application $app, Config $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, Closure $next)
    {
        try {
            WebhookSignature::verifyHeader(
                $request->getContent(),
                $request->header('SierraTecnologia-Signature'),
                $this->config->get('services.sierratecnologia.webhook.secret'),
                $this->config->get('services.sierratecnologia.webhook.tolerance')
            );
        } catch (SignatureVerification $exception) {
            $this->app->abort(403);
        }

        return $next($request);
    }
}
