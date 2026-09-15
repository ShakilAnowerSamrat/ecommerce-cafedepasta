<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('piprapay_payments')) {
            Schema::create('piprapay_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('combined_order_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('payment_type', 50)->default('cart_payment')->index();
                $table->string('provider', 50)->default('piprapay');
                $table->string('pp_id', 100)->nullable()->unique();
                $table->string('provider_transaction_id', 100)->nullable()->index();
                $table->decimal('amount', 20, 2)->default(0.00);
                $table->string('currency', 10)->default('BDT');
                $table->decimal('fee', 20, 2)->default(0.00);
                $table->decimal('discount_amount', 20, 2)->default(0.00);
                $table->decimal('net_amount', 20, 2)->default(0.00);
                $table->string('status', 50)->default('pending')->index();
                $table->text('metadata')->nullable();
                $table->text('checkout_url')->nullable();
                $table->string('refund_status', 50)->nullable();
                $table->longText('provider_response')->nullable();
                $table->longText('webhook_payload')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('piprapay_payments');
    }
};
