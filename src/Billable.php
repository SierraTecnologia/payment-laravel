<?php

namespace SierraTecnologia\Cashier;

use Exception;
use InvalidArgumentException;
use SierraTecnologia\Card as SierraTecnologiaCard;
use SierraTecnologia\Token as SierraTecnologiaToken;
use Illuminate\Support\Collection;
use SierraTecnologia\Charge as SierraTecnologiaCharge;
use SierraTecnologia\Refund as SierraTecnologiaRefund;
use SierraTecnologia\Invoice as SierraTecnologiaInvoice;
use SierraTecnologia\Customer as SierraTecnologiaCustomer;
use SierraTecnologia\BankAccount as SierraTecnologiaBankAccount;
use SierraTecnologia\InvoiceItem as SierraTecnologiaInvoiceItem;
use SierraTecnologia\Error\InvalidRequest as SierraTecnologiaErrorInvalidRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait Billable
{
    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param  int   $amount
     * @param  array $options
     * @return \SierraTecnologia\Charge
     * @throws \InvalidArgumentException
     */
    public function charge($amount, array $options = [])
    {
        $options = array_merge(
            [
            'currency' => $this->preferredCurrency(),
            ], $options
        );

        $options['amount'] = $amount;

        if (! array_key_exists('source', $options) && $this->sierratecnologia_id) {
            $options['customer'] = $this->sierratecnologia_id;
        }

        if (! array_key_exists('source', $options) && ! array_key_exists('customer', $options)) {
            throw new InvalidArgumentException('No payment source provided.');
        }

        return SierraTecnologiaCharge::create($options, Cashier::sierratecnologiaOptions());
    }

    /**
     * Refund a customer for a charge.
     *
     * @param  string $charge
     * @param  array  $options
     * @return \SierraTecnologia\Refund
     * @throws \InvalidArgumentException
     */
    public function refund($charge, array $options = [])
    {
        $options['charge'] = $charge;

        return SierraTecnologiaRefund::create($options, Cashier::sierratecnologiaOptions());
    }

    /**
     * Determines if the customer currently has a card on file.
     *
     * @return bool
     */
    public function hasCardOnFile()
    {
        return (bool) $this->card_brand;
    }

    /**
     * Add an invoice item to the customer's upcoming invoice.
     *
     * @param  string $description
     * @param  int    $amount
     * @param  array  $options
     * @return \SierraTecnologia\InvoiceItem
     * @throws \InvalidArgumentException
     */
    public function tab($description, $amount, array $options = [])
    {
        if (! $this->sierratecnologia_id) {
            throw new InvalidArgumentException(class_basename($this).' is not a SierraTecnologia customer. See the createAsSierraTecnologiaCustomer method.');
        }

        $options = array_merge(
            [
            'customer' => $this->sierratecnologia_id,
            'amount' => $amount,
            'currency' => $this->preferredCurrency(),
            'description' => $description,
            ], $options
        );

        return SierraTecnologiaInvoiceItem::create($options, Cashier::sierratecnologiaOptions());
    }

    /**
     * Invoice the customer for the given amount and generate an invoice immediately.
     *
     * @param  string $description
     * @param  int    $amount
     * @param  array  $tabOptions
     * @param  array  $invoiceOptions
     * @return \SierraTecnologia\Invoice|bool
     */
    public function invoiceFor($description, $amount, array $tabOptions = [], array $invoiceOptions = [])
    {
        $this->tab($description, $amount, $tabOptions);

        return $this->invoice($invoiceOptions);
    }

    /**
     * Begin creating a new subscription.
     *
     * @param  string $subscription
     * @param  string $plan
     * @return \SierraTecnologia\Cashier\SubscriptionBuilder
     */
    public function newSubscription($subscription, $plan)
    {
        return new SubscriptionBuilder($this, $subscription, $plan);
    }

    /**
     * Determine if the SierraTecnologia model is on trial.
     *
     * @param  string      $subscription
     * @param  string|null $plan
     * @return bool
     */
    public function onTrial($subscription = 'default', $plan = null)
    {
        if (func_num_args() === 0 && $this->onGenericTrial()) {
            return true;
        }

        $subscription = $this->subscription($subscription);

        if (is_null($plan)) {
            return $subscription && $subscription->onTrial();
        }

        return $subscription && $subscription->onTrial() &&
               $subscription->sierratecnologia_plan === $plan;
    }

    /**
     * Determine if the SierraTecnologia model is on a "generic" trial at the model level.
     *
     * @return bool
     */
    public function onGenericTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine if the SierraTecnologia model has a given subscription.
     *
     * @param  string      $subscription
     * @param  string|null $plan
     * @return bool
     */
    public function subscribed($subscription = 'default', $plan = null)
    {
        $subscription = $this->subscription($subscription);

        if (is_null($subscription)) {
            return false;
        }

        if (is_null($plan)) {
            return $subscription->valid();
        }

        return $subscription->valid() &&
               $subscription->sierratecnologia_plan === $plan;
    }

    /**
     * Get a subscription instance by name.
     *
     * @param  string $subscription
     * @return \SierraTecnologia\Cashier\Subscription|null
     */
    public function subscription($subscription = 'default')
    {
        return $this->subscriptions->sortByDesc(
            function ($value) {
                return $value->created_at->getTimestamp();
            }
        )->first(
            function ($value) use ($subscription) {
                return $value->name === $subscription;
            }
        );
    }

    /**
     * Get all of the subscriptions for the SierraTecnologia model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Invoice the billable entity outside of regular billing cycle.
     *
     * @param  array $options
     * @return \SierraTecnologia\Invoice|bool
     */
    public function invoice(array $options = [])
    {
        if ($this->sierratecnologia_id) {
            $parameters = array_merge($options, ['customer' => $this->sierratecnologia_id]);

            try {
                return SierraTecnologiaInvoice::create($parameters, Cashier::sierratecnologiaOptions())->pay();
            } catch (SierraTecnologiaErrorInvalidRequest $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the entity's upcoming invoice.
     *
     * @return \SierraTecnologia\Cashier\Invoice|null
     */
    public function upcomingInvoice()
    {
        try {
            $sierratecnologiaInvoice = SierraTecnologiaInvoice::upcoming(['customer' => $this->sierratecnologia_id], Cashier::sierratecnologiaOptions());

            return new Invoice($this, $sierratecnologiaInvoice);
        } catch (SierraTecnologiaErrorInvalidRequest $e) {
            //
        }
    }

    /**
     * Find an invoice by ID.
     *
     * @param  string $id
     * @return \SierraTecnologia\Cashier\Invoice|null
     */
    public function findInvoice($id)
    {
        try {
            $sierratecnologiaInvoice = SierraTecnologiaInvoice::retrieve(
                $id, Cashier::sierratecnologiaOptions()
            );

            $sierratecnologiaInvoice->lines = SierraTecnologiaInvoice::retrieve($id, Cashier::sierratecnologiaOptions())
                ->lines
                ->all(['limit' => 1000]);

            return new Invoice($this, $sierratecnologiaInvoice);
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Find an invoice or throw a 404 error.
     *
     * @param  string $id
     * @return \SierraTecnologia\Cashier\Invoice
     */
    public function findInvoiceOrFail($id)
    {
        $invoice = $this->findInvoice($id);

        if (is_null($invoice)) {
            throw new NotFoundHttpException;
        }

        if ($invoice->customer !== $this->sierratecnologia_id) {
            throw new AccessDeniedHttpException;
        }

        return $invoice;
    }

    /**
     * Create an invoice download Response.
     *
     * @param  string $id
     * @param  array  $data
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadInvoice($id, array $data)
    {
        return $this->findInvoiceOrFail($id)->download($data);
    }

    /**
     * Get a collection of the entity's invoices.
     *
     * @param  bool  $includePending
     * @param  array $parameters
     * @return \Illuminate\Support\Collection
     */
    public function invoices($includePending = false, $parameters = [])
    {
        $invoices = [];

        $parameters = array_merge(['limit' => 24], $parameters);

        $sierratecnologiaInvoices = $this->asSierraTecnologiaCustomer()->invoices($parameters);

        // Here we will loop through the SierraTecnologia invoices and create our own custom Invoice
        // instances that have more helper methods and are generally more convenient to
        // work with than the plain SierraTecnologia objects are. Then, we'll return the array.
        if (! is_null($sierratecnologiaInvoices)) {
            foreach ($sierratecnologiaInvoices->data as $invoice) {
                if ($invoice->paid || $includePending) {
                    $invoices[] = new Invoice($this, $invoice);
                }
            }
        }

        return new Collection($invoices);
    }

    /**
     * Get an array of the entity's invoices.
     *
     * @param  array $parameters
     * @return \Illuminate\Support\Collection
     */
    public function invoicesIncludingPending(array $parameters = [])
    {
        return $this->invoices(true, $parameters);
    }

    /**
     * Get a collection of the entity's cards.
     *
     * @param  array $parameters
     * @return \Illuminate\Support\Collection
     */
    public function cards($parameters = [])
    {
        $cards = [];

        $parameters = array_merge(['limit' => 24], $parameters);

        $sierratecnologiaCards = $this->asSierraTecnologiaCustomer()->sources->all(
            ['object' => 'card'] + $parameters
        );

        if (! is_null($sierratecnologiaCards)) {
            foreach ($sierratecnologiaCards->data as $card) {
                $cards[] = new Card($this, $card);
            }
        }

        return new Collection($cards);
    }

    /**
     * Get the default card for the entity.
     *
     * @return \SierraTecnologia\Card|null
     */
    public function defaultCard()
    {
        if (! $this->hasSierraTecnologiaId()) {
            return;
        }

        $customer = $this->asSierraTecnologiaCustomer();

        foreach ($customer->sources->data as $card) {
            if ($card->id === $customer->default_source) {
                return $card;
            }
        }
    }

    /**
     * Update customer's credit card.
     *
     * @param  string $token
     * @return void
     */
    public function updateCard($token)
    {
        $customer = $this->asSierraTecnologiaCustomer();

        $token = SierraTecnologiaToken::retrieve($token, Cashier::sierratecnologiaOptions());

        // If the given token already has the card as their default source, we can just
        // bail out of the method now. We don't need to keep adding the same card to
        // a model's account every time we go through this particular method call.
        if ($token[$token->type]->id === $customer->default_source) {
            return;
        }

        $card = $customer->sources->create(['source' => $token]);

        $customer->default_source = $card->id;

        $customer->save();

        // Next we will get the default source for this model so we can update the last
        // four digits and the card brand on the record in the database. This allows
        // us to display the information on the front-end when updating the cards.
        $source = $customer->default_source
                    ? $customer->sources->retrieve($customer->default_source)
                    : null;

        $this->fillCardDetails($source);

        $this->save();
    }

    /**
     * Synchronises the customer's card from SierraTecnologia back into the database.
     *
     * @return $this
     */
    public function updateCardFromSierraTecnologia()
    {
        $defaultCard = $this->defaultCard();

        if ($defaultCard) {
            $this->fillCardDetails($defaultCard)->save();
        } else {
            $this->forceFill(
                [
                'card_brand' => null,
                'card_last_four' => null,
                ]
            )->save();
        }

        return $this;
    }

    /**
     * Fills the model's properties with the source from SierraTecnologia.
     *
     * @param  \SierraTecnologia\Card|\SierraTecnologia\BankAccount|null $card
     * @return $this
     */
    protected function fillCardDetails($card)
    {
        if ($card instanceof SierraTecnologiaCard) {
            $this->card_brand = $card->brand;
            $this->card_last_four = $card->last4;
        } elseif ($card instanceof SierraTecnologiaBankAccount) {
            $this->card_brand = 'Bank Account';
            $this->card_last_four = $card->last4;
        }

        return $this;
    }

    /**
     * Deletes the entity's cards.
     *
     * @return void
     */
    public function deleteCards()
    {
        $this->cards()->each(
            function ($card) {
                $card->delete();
            }
        );

        $this->updateCardFromSierraTecnologia();
    }

    /**
     * Apply a coupon to the billable entity.
     *
     * @param  string $coupon
     * @return void
     */
    public function applyCoupon($coupon)
    {
        $customer = $this->asSierraTecnologiaCustomer();

        $customer->coupon = $coupon;

        $customer->save();
    }

    /**
     * Determine if the SierraTecnologia model is actively subscribed to one of the given plans.
     *
     * @param  array|string $plans
     * @param  string       $subscription
     * @return bool
     */
    public function subscribedToPlan($plans, $subscription = 'default')
    {
        $subscription = $this->subscription($subscription);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        foreach ((array) $plans as $plan) {
            if ($subscription->sierratecnologia_plan === $plan) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the entity is on the given plan.
     *
     * @param  string $plan
     * @return bool
     */
    public function onPlan($plan)
    {
        return ! is_null(
            $this->subscriptions->first(
                function ($value) use ($plan) {
                    return $value->sierratecnologia_plan === $plan && $value->valid();
                }
            )
        );
    }

    /**
     * Determine if the entity has a SierraTecnologia customer ID.
     *
     * @return bool
     */
    public function hasSierraTecnologiaId()
    {
        return ! is_null($this->sierratecnologia_id);
    }

    /**
     * Create a SierraTecnologia customer for the given model.
     *
     * @param  array $options
     * @return \SierraTecnologia\Customer
     */
    public function createAsSierraTecnologiaCustomer(array $options = [])
    {
        $options = array_key_exists('email', $options)
                ? $options
                : array_merge($options, ['email' => $this->email]);

        // Here we will create the customer instance on SierraTecnologia and store the ID of the
        // user from SierraTecnologia. This ID will correspond with the SierraTecnologia user instances
        // and allow us to retrieve users from SierraTecnologia later when we need to work.
        $customer = SierraTecnologiaCustomer::create(
            $options, Cashier::sierratecnologiaOptions()
        );

        $this->sierratecnologia_id = $customer->id;

        $this->save();

        return $customer;
    }

    /**
     * Update the underlying SierraTecnologia customer information for the model.
     *
     * @param  array $options
     * @return \SierraTecnologia\Customer
     */
    public function updateSierraTecnologiaCustomer(array $options = [])
    {
        return SierraTecnologiaCustomer::update(
            $this->sierratecnologia_id, $options, Cashier::sierratecnologiaOptions()
        );
    }

    /**
     * Get the SierraTecnologia customer instance for the current user and token.
     *
     * @param  array $options
     * @return \SierraTecnologia\Customer
     */
    public function createOrGetSierraTecnologiaCustomer(array $options = [])
    {
        if ($this->sierratecnologia_id) {
            return $this->asSierraTecnologiaCustomer();
        }

        return $this->createAsSierraTecnologiaCustomer($options);
    }

    /**
     * Get the SierraTecnologia customer for the model.
     *
     * @return \SierraTecnologia\Customer
     */
    public function asSierraTecnologiaCustomer()
    {
        return SierraTecnologiaCustomer::retrieve($this->sierratecnologia_id, Cashier::sierratecnologiaOptions());
    }

    /**
     * Get the SierraTecnologia supported currency used by the entity.
     *
     * @return string
     */
    public function preferredCurrency()
    {
        return Cashier::usesCurrency();
    }

    /**
     * Get the tax percentage to apply to the subscription.
     *
     * @return int|float
     */
    public function taxPercentage()
    {
        return 0;
    }
}
