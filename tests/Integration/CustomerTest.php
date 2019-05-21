<?php

namespace Laravel\Cashier\Tests\Integration;

class CustomerTest extends IntegrationTestCase
{
    public function test_customers_in_sierratecnologia_can_be_updated()
    {
        $user = $this->createCustomer('customers_in_sierratecnologia_can_be_updated');
        $user->createAsSierraTecnologiaCustomer();

        $customer = $user->updateSierraTecnologiaCustomer(['description' => 'Mohamed Said']);

        $this->assertEquals('Mohamed Said', $customer->description);
    }
}
