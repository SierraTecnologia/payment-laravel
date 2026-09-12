<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table(
                'users', function (Blueprint $table) {
                    if (! Schema::hasColumn('users', 'sitecpayment_id')) {
                        if (config('database.default') == 'mysql') {
                            $table->string('sitecpayment_id')->nullable()->collation('utf8mb4_bin');
                        } else {
                            $table->string('sitecpayment_id')->nullable();
                        }
                    }
                    if (! Schema::hasColumn('users', 'card_brand')) {
                        $table->string('card_brand')->nullable();
                    }
                    if (! Schema::hasColumn('users', 'card_last_four')) {
                        $table->string('card_last_four', 4)->nullable();
                    }
                    if (! Schema::hasColumn('users', 'trial_ends_at')) {
                        $table->timestamp('trial_ends_at')->nullable();
                    }
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('users')) {
            $columns = array_values(array_filter([
                'sitecpayment_id',
                'card_brand',
                'card_last_four',
                'trial_ends_at',
            ], static fn (string $column): bool => Schema::hasColumn('users', $column)));

            if ($columns !== []) {
                Schema::table('users', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
}
