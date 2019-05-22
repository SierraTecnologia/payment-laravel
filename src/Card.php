<?php

namespace SierraTecnologia\Cashier;

use SierraTecnologia\Card as SierraTecnologiaCard;

class Card
{
    /**
     * The SierraTecnologia model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $owner;

    /**
     * The SierraTecnologia card instance.
     *
     * @var \SierraTecnologia\Card
     */
    protected $card;

    /**
     * Create a new card instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @param  \SierraTecnologia\Card  $card
     * @return void
     */
    public function __construct($owner, SierraTecnologiaCard $card)
    {
        $this->owner = $owner;
        $this->card = $card;
    }

    /**
     * Delete the card.
     *
     * @return \SierraTecnologia\Card
     */
    public function delete()
    {
        return $this->card->delete();
    }

    /**
     * Get the SierraTecnologia card instance.
     *
     * @return \SierraTecnologia\Card
     */
    public function asSierraTecnologiaCard()
    {
        return $this->card;
    }

    /**
     * Dynamically get values from the SierraTecnologia card.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->card->{$key};
    }
}
