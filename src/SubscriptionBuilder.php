<?php

namespace SierraTecnologia\Cashier;

use Carbon\Carbon;
use DateTimeInterface;
use SierraTecnologia\Cashier\Exceptions\SubscriptionCreationFailed;

class SubscriptionBuilder
{
    /**
     * The model that is subscribing.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $owner;

    /**
     * The name of the subscription.
     *
     * @var string
     */
    protected $name;

    /**
     * The name of the plan being subscribed to.
     *
     * @var string
     */
    protected $plan;

    /**
     * The quantity of the subscription.
     *
     * @var int
     */
    protected $quantity = 1;

    /**
     * The date and time the trial will expire.
     *
     * @var \Carbon\Carbon
     */
    protected $trialExpires;

    /**
     * Indicates that the trial should end immediately.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * The date on which the billing cycle should be anchored.
     *
     * @var int|null
     */
    protected $billingCycleAnchor = null;

    /**
     * The coupon code being applied to the customer.
     *
     * @var string|null
     */
    protected $coupon;

    /**
     * The metadata to apply to the subscription.
     *
     * @var array|null
     */
    protected $metadata;

    /**
     * Create a new subscription builder instance.
     *
     * @param  mixed  $owner
     * @param  string $name
     * @param  string $plan
     * @return void
     */
    public function __construct($owner, $name, $plan)
    {
        $this->name = $name;
        $this->plan = $plan;
        $this->owner = $owner;
    }

    /**
     * Specify the quantity of the subscription.
     *
     * @param  int $quantity
     * @return $this
     */
    public function quantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Specify the number of days of the trial.
     *
     * @param  int $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        $this->trialExpires = Carbon::now()->addDays($trialDays);

        return $this;
    }

    /**
     * Specify the ending date of the trial.
     *
     * @param  \Carbon\Carbon $trialUntil
     * @return $this
     */
    public function trialUntil(Carbon $trialUntil)
    {
        $this->trialExpires = $trialUntil;

        return $this;
    }

    /**
     * Force the trial to end immediately.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Change the billing cycle anchor on a plan creation.
     *
     * @param  \DateTimeInterface|int $date
     * @return $this
     */
    public function anchorBillingCycleOn($date)
    {
        if ($date instanceof DateTimeInterface) {
            $date = $date->getTimestamp();
        }

        $this->billingCycleAnchor = $date;

        return $this;
    }

    /**
     * The coupon to apply to a new subscription.
     *
     * @param  string $coupon
     * @return $this
     */
    public function withCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * The metadata to apply to a new subscription.
     *
     * @param  array $metadata
     * @return $this
     */
    public function withMetadata($metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Add a new SierraTecnologia subscription to the SierraTecnologia model.
     *
     * @param  array $options
     * @return \SierraTecnologia\Cashier\Subscription
     */
    public function add(array $options = [])
    {
        return $this->create(null, $options);
    }

    /**
     * Create a new SierraTecnologia subscription.
     *
     * @param  string|null $token
     * @param  array       $options
     * @return \SierraTecnologia\Cashier\Subscription
     */
    public function create($token = null, array $options = [])
    {
        $customer = $this->getSierraTecnologiaCustomer($token, $options);

        $subscription = $customer->subscriptions->create($this->buildPayload());

        if (in_array($subscription->status, ['incomplete', 'incomplete_expired'])) {
            $subscription->cancel();

            throw SubscriptionCreationFailed::incomplete($subscription);
        }

        if ($this->skipTrial) {
            $trialEndsAt = null;
        } else {
            $trialEndsAt = $this->trialExpires;
        }

        return $this->owner->subscriptions()->create(
            [
            'name' => $this->name,
            'sitecpayment_id' => $subscription->id,
            'sitecpayment_plan' => $this->plan,
            'quantity' => $this->quantity,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => null,
            ]
        );
    }

    /**
     * Get the SierraTecnologia customer instance for the current user and token.
     *
     * @param  string|null $token
     * @param  array       $options
     * @return \SierraTecnologia\Customer
     */
    protected function getSierraTecnologiaCustomer($token = null, array $options = [])
    {
        $customer = $this->owner->createOrGetSierraTecnologiaCustomer($options);

        if ($token) {
            $this->owner->updateCard($token);
        }

        return $customer;
    }

    /**
     * Build the payload for subscription creation.
     *
     * @return array
     */
    protected function buildPayload()
    {
        return array_filter(
            [
            'billing_cycle_anchor' => $this->billingCycleAnchor,
            'coupon' => $this->coupon,
            'metadata' => $this->metadata,
            'plan' => $this->plan,
            'quantity' => $this->quantity,
            'tax_percent' => $this->getTaxPercentageForPayload(),
            'trial_end' => $this->getTrialEndForPayload(),
            ]
        );
    }

    /**
     * Get the trial ending date for the SierraTecnologia payload.
     *
     * @return false|int|null|string
     *
     * @psalm-return 'now'|false|int|null
     */
    protected function getTrialEndForPayload()
    {
        if ($this->skipTrial) {
            return 'now';
        }

        if ($this->trialExpires) {
            return $this->trialExpires->getTimestamp();
        }
    }

    /**
     * Get the tax percentage for the SierraTecnologia payload.
     *
     * @return int|float|null
     */
    protected function getTaxPercentageForPayload()
    {
        if ($taxPercentage = $this->owner->taxPercentage()) {
            return $taxPercentage;
        }
    }
}
