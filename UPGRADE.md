# Upgrade Guide

## Upgrading To 9.3 From 9.2

### Custom Subscription Creation Exception

[In their 2019-03-14 API update](https://sierratecnologia.com/docs/upgrades#2019-03-14), SierraTecnologia changed the way they handle new subscriptions when card payment fails. Instead of letting the creation of the subscription fail, the subscription is failed with an "incomplete" status. Because of this a Cashier customer will always get a successful subscription. Previously a card exception was thrown.

To accommodate for this new behavior from now on Cashier will cancel that subscription immediately and throw a custom `SubscriptionCreationFailed` exception when a subscription is created with an "incomplete" or "incomplete_expired" status. We've decided to do this because in general you want to let a customer only start using your product when payment was received.

If you were relying on catching the `\SierraTecnologia\Error\Card` exception before you should now rely on catching the `SierraTecnologia\Cashier\Exceptions\SubscriptionCreationFailed` exception instead. 

### Card Failure When Swapping Plans

Previously, when a user attempted to change subscription plans and their payment failed, the resulting exception bubbled up to the end user and the update to the subscription in the application was not performed. However, the subscription was still updated in SierraTecnologia itself resulting in the application and SierraTecnologia becoming out of sync.

However, Cashier will now catch the payment failure exception while allowing the plan swap to continue. The payment failure will be handled by SierraTecnologia and SierraTecnologia may attempt to retry the payment at a later time. If the payment fails during the final retry attempt, SierraTecnologia will execute the action you have configured in your billing settings: https://sierratecnologia.com/docs/billing/lifecycle#settings

Therefore, you should ensure you have configured Cashier to handle SierraTecnologia's webhooks. When configured properly, this will allow Cashier to mark the subscription as cancelled when the final payment retry attempt fails and SierraTecnologia notifies your application via a webhook request. Please refer to our [instructions for setting up SierraTecnologia webhooks with Cashier.](https://laravel.com/docs/master/billing#handling-sierratecnologia-webhooks).

## Upgrading To 9.0 From 8.0

### PHP & Laravel Version Requirements

Like the latest releases of the Laravel framework, Laravel Cashier now requires PHP >= 7.1.3. We encourage you to upgrade to the latest versions of PHP and Laravel before upgrading to Cashier 9.0.

### The `createAsSierraTecnologiaCustomer` Method

The `updateCard` call was extracted from the `createAsSierraTecnologiaCustomer` method on the `Billable` trait in PR [#588](https://github.com/laravel/cashier/pull/588). In addition, the `$token` parameter was removed.

If you were calling the `createAsSierraTecnologiaCustomer` method directly you now should call the `updateCard` method separately after calling the `createAsSierraTecnologiaCustomer` method. This provides the opportunity for more granularity when handling errors for the two calls.

### WebhookController Changes

Instead of calling the SierraTecnologia API to verify incoming webhook events, Cashier now only uses webhook signatures to verify that events it receives are authentic as of [PR #591](https://github.com/laravel/cashier/pull/591).

The `VerifyWebhookSignature` middleware is now automatically added to the `WebhookController` if the `services.sierratecnologia.webhook.secret` value is set in your `services.php` configuration file. By default, this configuration value uses the `SIERRATECNOLOGIA_WEBHOOK_SECRET` environment variable.

If you manually added the `VerifyWebhookSignature` middleware to your Cashier webhook route, you may remove it since it will now be added automatically.

If you were using the `CASHIER_ENV` environment variable to test incoming webhooks, you should set the `SIERRATECNOLOGIA_WEBHOOK_SECRET` environment variable to `null` to achieve the same behavior.

More information about verifying webhooks can be found [in the Cashier documentation](https://laravel.com/docs/5.7/billing#verifying-webhook-signatures).
