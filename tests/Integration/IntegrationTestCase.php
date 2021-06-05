<?php

namespace SierraTecnologia\Cashier\Tests\Integration;

use SierraTecnologia\SierraTecnologia;
use SierraTecnologia\ApiResource;
use SierraTecnologia\Error\InvalidRequest;
use Orchestra\Testbench\TestCase;
use SierraTecnologia\Cashier\Tests\Fixtures\User;
use SierraTecnologia\Cashier\CashierServiceProvider;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class IntegrationTestCase extends TestCase
{
    /**
     * @var string
     */
    protected static $sierratecnologiaPrefix = 'cashier-test-';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        SierraTecnologia::setApiKey(getenv('SITECPAYMENT_SECRET'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        Eloquent::unguard();

        $this->loadLaravelMigrations();

        $this->artisan('migrate')->run();
    }

    protected function getPackageProviders($app)
    {
        return [CashierServiceProvider::class];
    }

    protected static function deleteSierraTecnologiaResource(ApiResource $resource)
    {
        try {
            $resource->delete();
        } catch (InvalidRequest $e) {
            //
        }
    }

    protected function createCustomer($description = 'taylor'): User
    {
        return User::create(
            [
            'email' => "{$description}@cashier-test.com",
            'name' => 'Ricardo Sierra',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ]
        );
    }
}
