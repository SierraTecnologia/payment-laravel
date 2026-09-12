<?php

namespace SierraTecnologia\Cashier\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SierraTecnologia\Invoice as SierraTecnologiaInvoice;
use SierraTecnologia\Cashier\Invoice;
use SierraTecnologia\Cashier\Tests\Fixtures\User;

class InvoiceTest extends TestCase
{
    public function test_invoice_totals_and_calculations()
    {
        $user = new User;
        $mockInvoice = SierraTecnologiaInvoice::constructFrom([
            'id' => 'in_12345',
            'created' => 1700000000,
            'total' => 2000,
            'subtotal' => 2500,
            'tax' => 0,
            'starting_balance' => 0,
            'currency' => 'usd',
            'discount' => [
                'coupon' => [
                    'id' => 'COUPON50',
                    'name' => '50 OFF',
                    'percent_off' => 20,
                ],
            ],
            'lines' => [
                'data' => [
                    [
                        'id' => 'il_1',
                        'type' => 'invoiceitem',
                        'amount' => 2500,
                        'description' => 'Subscription fee',
                    ],
                ],
            ],
        ]);

        $invoice = new Invoice($user, $mockInvoice);

        $this->assertEquals('$20.00', $invoice->total());
        $this->assertEquals('$25.00', $invoice->subtotal());
        $this->assertTrue($invoice->hasDiscount());
        $this->assertEquals('$5.00', $invoice->discount());
        $this->assertEquals('COUPON50', $invoice->coupon());
        $this->assertFalse($invoice->hasStartingBalance());
        $this->assertInstanceOf(\Carbon\Carbon::class, $invoice->date());
        $this->assertSame($mockInvoice, $invoice->asSierraTecnologiaInvoice());
    }

    public function test_invoice_items_collection()
    {
        $user = new User;
        $mockInvoice = SierraTecnologiaInvoice::constructFrom([
            'id' => 'in_67890',
            'total' => 1000,
            'subtotal' => 1000,
            'currency' => 'usd',
            'lines' => [
                'data' => [
                    [
                        'id' => 'il_1',
                        'type' => 'invoiceitem',
                        'amount' => 1000,
                        'description' => 'Single item',
                    ],
                ],
            ],
        ]);

        $invoice = new Invoice($user, $mockInvoice);
        $items = $invoice->invoiceItems();

        $this->assertCount(1, $items);
        $this->assertEquals('$10.00', $items[0]->total());
        $this->assertEquals('Single item', $items[0]->description);
    }
}
