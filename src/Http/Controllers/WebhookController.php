<?php

namespace Laravel\Cashier\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Cashier\Cashier;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

class WebhookController extends Controller
{
    /**
     * Create a new WebhookController instance.
     *
     * @return void
     */
    public function __construct()
    {
        if (config('services.sierratecnologia.webhook.secret')) {
            $this->middleware(VerifyWebhookSignature::class);
        }
    }

    /**
     * Handle a SierraTecnologia webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle'.Str::studly(str_replace('.', '_', $payload['type']));

        if (method_exists($this, $method)) {
            return $this->{$method}($payload);
        }

        return $this->missingMethod();
    }

    /**
     * Handle customer subscription updated.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        if ($user = $this->getUserBySierraTecnologiaId($payload['data']['object']['customer'])) {
            $data = $payload['data']['object'];

            $user->subscriptions->filter(function (Subscription $subscription) use ($data) {
                return $subscription->sierratecnologia_id === $data['id'];
            })->each(function (Subscription $subscription) use ($data) {
                // Quantity...
                if (isset($data['quantity'])) {
                    $subscription->quantity = $data['quantity'];
                }

                // Plan...
                if (isset($data['plan']['id'])) {
                    $subscription->sierratecnologia_plan = $data['plan']['id'];
                }

                // Trial ending date...
                if (isset($data['trial_end'])) {
                    $trial_ends = Carbon::createFromTimestamp($data['trial_end']);

                    if (! $subscription->trial_ends_at || $subscription->trial_ends_at->ne($trial_ends)) {
                        $subscription->trial_ends_at = $trial_ends;
                    }
                }

                // Cancellation date...
                if (isset($data['cancel_at_period_end']) && $data['cancel_at_period_end']) {
                    $subscription->ends_at = $subscription->onTrial()
                                ? $subscription->trial_ends_at
                                : Carbon::createFromTimestamp($data['current_period_end']);
                }

                $subscription->save();
            });
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle a cancelled customer from a SierraTecnologia subscription.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        if ($user = $this->getUserBySierraTecnologiaId($payload['data']['object']['customer'])) {
            $user->subscriptions->filter(function ($subscription) use ($payload) {
                return $subscription->sierratecnologia_id === $payload['data']['object']['id'];
            })->each(function ($subscription) {
                $subscription->markAsCancelled();
            });
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle customer updated.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerUpdated(array $payload)
    {
        if ($user = $this->getUserBySierraTecnologiaId($payload['data']['object']['id'])) {
            $user->updateCardFromSierraTecnologia();
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle customer source deleted.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSourceDeleted(array $payload)
    {
        if ($user = $this->getUserBySierraTecnologiaId($payload['data']['object']['customer'])) {
            $user->updateCardFromSierraTecnologia();
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Handle deleted customer.
     *
     * @param  array $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerDeleted(array $payload)
    {
        if ($user = $this->getUserBySierraTecnologiaId($payload['data']['object']['id'])) {
            $user->subscriptions->each(function (Subscription $subscription) {
                $subscription->skipTrial()->markAsCancelled();
            });

            $user->forceFill([
                'sierratecnologia_id' => null,
                'trial_ends_at' => null,
                'card_brand' => null,
                'card_last_four' => null,
            ])->save();
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * Get the billable entity instance by SierraTecnologia ID.
     *
     * @param  string  $sierratecnologiaId
     * @return \Laravel\Cashier\Billable
     */
    protected function getUserBySierraTecnologiaId($sierratecnologiaId)
    {
        $model = Cashier::sierratecnologiaModel();

        return (new $model)->where('sierratecnologia_id', $sierratecnologiaId)->first();
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function missingMethod($parameters = [])
    {
        return new Response;
    }
}
