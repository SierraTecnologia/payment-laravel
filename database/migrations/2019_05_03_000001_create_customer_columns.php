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
        Schema::table(
            'users', function (Blueprint $table) {
                if (config('database.default') == 'mysql') {
                    $table->string('sitecpayment_id')->nullable()->collation('utf8mb4_bin');
                } else {
                    $table->string('sitecpayment_id')->nullable();
                }
                $table->string('card_brand')->nullable();
                $table->string('card_last_four', 4)->nullable();
                $table->timestamp('trial_ends_at')->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            'users', function (Blueprint $table) {
                $table->dropColumn(
                    [
                    'sitecpayment_id',
                    'card_brand',
                    'card_last_four',
                    'trial_ends_at',
                    ]
                );
            }
        );
    }
}
