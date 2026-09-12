<?php

namespace SierraTecnologia\Cashier\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use SierraTecnologia\Cashier\Cashier;

class CashierTest extends TestCase
{
    protected function tearDown(): void
    {
        Cashier::useCurrency('usd');
        Cashier::useCurrencySymbol('$');
        Cashier::formatCurrencyUsing(null);
        parent::tearDown();
    }

    public function test_currency_can_be_configured()
    {
        Cashier::useCurrency('eur');
        $this->assertEquals('eur', Cashier::usesCurrency());
        $this->assertEquals('€', Cashier::usesCurrencySymbol());

        Cashier::useCurrency('gbp');
        $this->assertEquals('gbp', Cashier::usesCurrency());
        $this->assertEquals('£', Cashier::usesCurrencySymbol());

        Cashier::useCurrency('brl');
        $this->assertEquals('brl', Cashier::usesCurrency());
        $this->assertEquals('R$', Cashier::usesCurrencySymbol());

        Cashier::useCurrency('jpy', '¥');
        $this->assertEquals('jpy', Cashier::usesCurrency());
        $this->assertEquals('¥', Cashier::usesCurrencySymbol());
    }

    public function test_unknown_currency_throws_exception_without_explicit_symbol()
    {
        $this->expectException(Exception::class);
        Cashier::useCurrency('xyz');
    }

    public function test_format_amount()
    {
        Cashier::useCurrency('usd');
        $this->assertEquals('$10.00', Cashier::formatAmount(1000));
        $this->assertEquals('$0.50', Cashier::formatAmount(50));

        Cashier::useCurrency('brl');
        $this->assertEquals('R$10.00', Cashier::formatAmount(1000));
    }

    public function test_custom_currency_formatter()
    {
        Cashier::formatCurrencyUsing(function ($amount) {
            return strtoupper(Cashier::usesCurrency()) . ' ' . number_format($amount / 100, 2, ',', '.');
        });

        $this->assertEquals('USD 10,00', Cashier::formatAmount(1000));
    }

    public function test_sierratecnologia_options()
    {
        Cashier::setSierraTecnologiaSecret('sk_test_mock_secret');
        $options = Cashier::sierratecnologiaOptions(['custom_key' => 'custom_val']);

        $this->assertEquals('sk_test_mock_secret', $options['api_key']);
        $this->assertEquals(Cashier::SITECPAYMENT_VERSION, $options['sitecpayment_version']);
        $this->assertEquals('custom_val', $options['custom_key']);
    }
}
