<?php

namespace SierraTecnologia\Cashier;

use Carbon\Carbon;

class InvoiceItem
{
    /**
     * The SierraTecnologia model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $owner;

    /**
     * The SierraTecnologia invoice item instance.
     *
     * @var \SierraTecnologia\InvoiceItem
     */
    protected $item;

    /**
     * Create a new invoice item instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model      $owner
     * @param  \SierraTecnologia\SierraTecnologiaObject $item
     * @return void
     */
    public function __construct($owner, $item)
    {
        $this->owner = $owner;
        $this->item = $item;
    }

    /**
     * Get a Carbon instance for the start date.
     *
     * @return Carbon|null
     */
    public function startDateAsCarbon(): ?Carbon
    {
        if ($this->isSubscription()) {
            return Carbon::createFromTimestampUTC($this->item->period->start);
        }
    }

    /**
     * Get a Carbon instance for the end date.
     *
     * @return Carbon|null
     */
    public function endDateAsCarbon(): ?Carbon
    {
        if ($this->isSubscription()) {
            return Carbon::createFromTimestampUTC($this->item->period->end);
        }
    }

    /**
     * Determine if the invoice item is for a subscription.
     *
     * @return bool
     */
    public function isSubscription()
    {
        return $this->item->type === 'subscription';
    }

    /**
     * Format the given amount into a string based on the owner model's preferences.
     *
     * @param  int $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount);
    }

    /**
     * Dynamically access the SierraTecnologia line item instance.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->item->{$key};
    }
}
