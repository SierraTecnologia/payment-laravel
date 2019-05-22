<?php

namespace SierraTecnologia\Cashier\Tests\Integration;

use SierraTecnologia\Plan;
use SierraTecnologia\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use SierraTecnologia\Cashier\Http\Controllers\WebhookController;

class WebhooksTest extends IntegrationTestCase
{
    /**
     * @var string
     */
    protected static $productId;

    /**
     * @var string
     */
    protected static $planId;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$productId = static::$sierratecnologiaPrefix.'product-1'.Str::random(10);
        static::$planId = static::$sierratecnologiaPrefix.'monthly-10-'.Str::random(10);

        Product::create([
            'id' => static::$productId,
            'name' => 'Laravel Cashier Test Product',
            'type' => 'service',
        ]);

        Plan::create([
            'id' => static::$planId,
            'nickname' => 'Monthly $10',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 1000,
            'product' => static::$productId,
        ]);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        static::deleteSierraTecnologiaResource(new Plan(static::$planId));
        static::deleteSierraTecnologiaResource(new Product(static::$productId));
    }

    public function test_subscription_is_marked_as_cancelled_when_deleted_in_sierratecnologia()
    {
        $user = $this->createCustomer('subscription_is_marked_as_cancelled_when_deleted_in_sierratecnologia');
        $subscription = $user->newSubscription('main', static::$planId)->create('tok_visa');

        $response = (new CashierTestControllerStub)->handleWebhook(
            Request::create('/', 'POST', [], [], [], [], json_encode([
                'id' => 'foo',
                'type' => 'customer.subscription.deleted',
                'data' => [
                    'object' => [
                        'id' => $subscription->sierratecnologia_id,
                        'customer' => $user->sierratecnologia_id,
                    ],
                ],
            ]))
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($user->fresh()->subscription('main')->cancelled());
    }
}

class CashierTestControllerStub extends WebhookController
{
    public function __construct()
    {
        // Prevent setting middleware...
    }
}
