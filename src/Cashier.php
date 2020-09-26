<?php

namespace SierraTecnologia\Cashier;

use Exception;
use Illuminate\Support\Str;

class Cashier
{
    /**
     * The SierraTecnologia API version.
     *
     * @var string
     */
    const SITECPAYMENT_VERSION = '2019-03-14';

    /**
     * The publishable SierraTecnologia API key.
     *
     * @var string
     */
    protected static $sierratecnologiaKey;

    /**
     * The secret SierraTecnologia API key.
     *
     * @var string
     */
    protected static $sierratecnologiaSecret;

    /**
     * The current currency.
     *
     * @var string
     */
    protected static $currency = 'usd';

    /**
     * The current currency symbol.
     *
     * @var string
     */
    protected static $currencySymbol = '$';

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Indicates if Cashier migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Get the secret SierraTecnologia API key.
     *
     * @return string
     */
    public static function sierratecnologiaSecret()
    {
        if (static::$sierratecnologiaSecret) {
            return static::$sierratecnologiaSecret;
        }

        if ($key = getenv('SITECPAYMENT_SECRET')) {
            return $key;
        }

        return \Illuminate\Support\Facades\Config::get('services.sierratecnologia.secret');
    }

    /**
     * Get the class name of the billable model.
     *
     * @return string
     */
    public static function sierratecnologiaModel()
    {
        return getenv('SITECPAYMENT_MODEL') ?: \Illuminate\Support\Facades\Config::get('services.sierratecnologia.model', 'App\\User');
    }

    /**
     * Guess the currency symbol for the given currency.
     *
     * @param  string $currency
     * @return string
     * @throws \Exception
     */
    protected static function guessCurrencySymbol($currency): string
    {
        switch (strtolower($currency)) {
        case 'usd':
        case 'aud':
        case 'cad':
            return '$';
        case 'eur':
            return '€';
        case 'gbp':
            return '£';
        default:
            throw new Exception('Unable to guess symbol for currency. Please explicitly specify it.');
        }
    }

    /**
     * Set the currency symbol to be used when formatting currency.
     *
     * @param  string $symbol
     * @return void
     */
    public static function useCurrencySymbol($symbol)
    {
        static::$currencySymbol = $symbol;
    }

    /**
     * Get the currency symbol currently in use.
     *
     * @return string
     */
    public static function usesCurrencySymbol()
    {
        return static::$currencySymbol;
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param  int $amount
     * @return string
     */
    public static function formatAmount($amount)
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $amount);
        }

        $amount = number_format($amount / 100, 2);

        if (Str::startsWith($amount, '-')) {
            return '-'.static::usesCurrencySymbol().ltrim($amount, '-');
        }

        return static::usesCurrencySymbol().$amount;
    }
}
