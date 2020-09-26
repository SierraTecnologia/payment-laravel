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
}
