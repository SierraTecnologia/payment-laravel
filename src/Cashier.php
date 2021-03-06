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
     * Get the publishable SierraTecnologia API key.
     *
     * @return string
     */
    public static function sierratecnologiaKey()
    {
        if (static::$sierratecnologiaKey) {
            return static::$sierratecnologiaKey;
        }

        if ($key = getenv('SITECPAYMENT_KEY')) {
            return $key;
        }

        return \Illuminate\Support\Facades\Config::get('services.sierratecnologia.key');
    }

    /**
     * Set the publishable SierraTecnologia API key.
     *
     * @param  string $key
     * @return void
     */
    public static function setSierraTecnologiaKey($key)
    {
        static::$sierratecnologiaKey = $key;
    }

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
     * Set the secret SierraTecnologia API key.
     *
     * @param  string $key
     * @return void
     */
    public static function setSierraTecnologiaSecret($key)
    {
        static::$sierratecnologiaSecret = $key;
    }

    /**
     * Get the default SierraTecnologia API options.
     *
     * @param  array $options
     * @return array
     */
    public static function sierratecnologiaOptions(array $options = [])
    {
        return array_merge(
            [
            'api_key' => static::sierratecnologiaSecret(),
            'sitecpayment_version' => static::SITECPAYMENT_VERSION,
            ], $options
        );
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
     * Set the currency to be used when billing SierraTecnologia models.
     *
     * @param  string      $currency
     * @param  string|null $symbol
     * @return void
     * @throws \Exception
     */
    public static function useCurrency($currency, $symbol = null)
    {
        static::$currency = $currency;

        static::useCurrencySymbol($symbol ?: static::guessCurrencySymbol($currency));
    }

    /**
     * Guess the currency symbol for the given currency.
     *
     * @param  string $currency
     * @return string
     * @throws \Exception
     */
    protected static function guessCurrencySymbol($currency)
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
     * Get the currency currently in use.
     *
     * @return string
     */
    public static function usesCurrency()
    {
        return static::$currency;
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
     * Set the custom currency formatter.
     *
     * @param  callable $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
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

    /**
     * Configure Cashier to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }
}
