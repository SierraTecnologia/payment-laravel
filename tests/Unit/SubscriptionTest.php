<?php

namespace SierraTecnologia\Cashier\Tests\Unit;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use SierraTecnologia\Cashier\CashierServiceProvider;
use SierraTecnologia\Cashier\Subscription;
use SierraTecnologia\Cashier\Tests\Fixtures\User;

class SubscriptionTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [CashierServiceProvider::class];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_subscription_creation_and_attributes()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);

        $subscription = $user->subscriptions()->create([
            'name' => 'default',
            'sitecpayment_id' => 'sub_12345',
            'sitecpayment_plan' => 'plan_basic',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('default', $subscription->name);
        $this->assertEquals('sub_12345', $subscription->sitecpayment_id);
        $this->assertEquals('plan_basic', $subscription->sitecpayment_plan);
        $this->assertEquals(1, $subscription->quantity);
        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->ended());
    }

    public function test_subscription_trial_state()
    {
        $subscription = new Subscription([
            'trial_ends_at' => Carbon::now()->addDays(5),
            'ends_at' => null,
        ]);

        $this->assertTrue($subscription->onTrial());
        $this->assertTrue($subscription->active());
        $this->assertTrue($subscription->valid());

        $subscription->trial_ends_at = Carbon::now()->subDay();
        $this->assertFalse($subscription->onTrial());
    }

    public function test_subscription_cancelled_and_grace_period()
    {
        $subscription = new Subscription([
            'trial_ends_at' => null,
            'ends_at' => Carbon::now()->addDays(3),
        ]);

        $this->assertTrue($subscription->cancelled());
        $this->assertTrue($subscription->onGracePeriod());
        $this->assertTrue($subscription->active());
        $this->assertFalse($subscription->ended());

        $subscription->ends_at = Carbon::now()->subDays(2);
        $this->assertTrue($subscription->cancelled());
        $this->assertFalse($subscription->onGracePeriod());
        $this->assertFalse($subscription->active());
        $this->assertTrue($subscription->ended());
    }

    public function test_casts_mutated_dates_to_carbon_instances()
    {
        $subscription = new Subscription([
            'trial_ends_at' => '2026-09-15 12:00:00',
            'ends_at' => '2026-10-15 12:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $subscription->trial_ends_at);
        $this->assertInstanceOf(Carbon::class, $subscription->ends_at);
        $this->assertEquals('2026-09-15', $subscription->trial_ends_at->toDateString());
    }

    public function test_subscription_has_user_relation()
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret',
        ]);

        $subscription = $user->subscriptions()->create([
            'name' => 'premium',
            'sitecpayment_id' => 'sub_99999',
            'sitecpayment_plan' => 'plan_premium',
            'quantity' => 2,
        ]);

        $this->assertEquals($user->id, $subscription->user->id);
        $this->assertEquals($user->id, $subscription->owner->id);
    }
}
