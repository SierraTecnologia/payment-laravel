<?php

namespace Laravel\Cashier;

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
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @param  \SierraTecnologia\SierraTecnologiaObject  $item
     * @return void
     */
    public function __construct($owner, $item)
    {
        $this->owner = $owner;
        $this->item = $item;
    }

    /**
     * Get the total for the line item.
     *
     * @return string
     */
    public function total()
    {
        return $this->formatAmount($this->amount);
    }

    /**
     * Get a human readable date for the start date.
     *
     * @return string
     */
    public function startDate()
    {
        if ($this->isSubscription()) {
            return $this->startDateAsCarbon()->toFormattedDateString();
        }
    }

    /**
     * Get a human readable date for the end date.
     *
     * @return string
     */
    public function endDate()
    {
        if ($this->isSubscription()) {
            return $this->endDateAsCarbon()->toFormattedDateString();
        }
    }

    /**
     * Get a Carbon instance for the start date.
     *
     * @return \Carbon\Carbon
     */
    public function startDateAsCarbon()
    {
        if ($this->isSubscription()) {
            return Carbon::createFromTimestampUTC($this->item->period->start);
        }
    }

    /**
     * Get a Carbon instance for the end date.
     *
     * @return \Carbon\Carbon
     */
    public function endDateAsCarbon()
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
     * @param  int  $amount
     * @return string
     */
    protected function formatAmount($amount)
    {
        return Cashier::formatAmount($amount);
    }

    /**
     * Get the underlying SierraTecnologia invoice item.
     *
     * @return \SierraTecnologia\SierraTecnologiaObject
     */
    public function asSierraTecnologiaInvoiceItem()
    {
        return $this->item;
    }

    /**
     * Dynamically access the SierraTecnologia line item instance.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->item->{$key};
    }
}
