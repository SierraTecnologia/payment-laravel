<?php

namespace SierraTecnologia\Cashier\Exceptions;

use Exception;
use SierraTecnologia\Subscription;

class SubscriptionCreationFailed extends Exception
{
    /**
     * @return static
     */
    public static function incomplete(Subscription $subscription): self
    {
        return new static("The attempt to create a subscription for plan \"{$subscription->plan->nickname}\" for customer \"{$subscription->customer}\" failed because the subscription was incomplete. For more information on incomplete subscriptions, see https://sierratecnologia.com/docs/billing/lifecycle#incomplete");
    }
}
