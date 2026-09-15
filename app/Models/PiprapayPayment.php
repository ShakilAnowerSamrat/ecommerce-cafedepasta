<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PiprapayPayment extends Model
{
    use HasFactory;

    protected $table = 'piprapay_payments';

    protected $fillable = [
        'order_id',
        'combined_order_id',
        'user_id',
        'payment_type',
        'provider',
        'pp_id',
        'provider_transaction_id',
        'amount',
        'currency',
        'fee',
        'discount_amount',
        'net_amount',
        'status',
        'metadata',
        'checkout_url',
        'refund_status',
        'provider_response',
        'webhook_payload',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function combined_order()
    {
        return $this->belongsTo(CombinedOrder::class, 'combined_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPaid(): bool
    {
        return in_array(strtolower($this->status), ['completed', 'paid']);
    }

    public function isPending(): bool
    {
        return strtolower($this->status) === 'pending';
    }

    public function isRefunded(): bool
    {
        return strtolower($this->status) === 'refunded' || strtolower($this->refund_status ?? '') === 'refunded';
    }

    /**
     * Self-healing table assurance for environments where artisan migrate has not yet run.
     */
    public static function ensureTableExists(): void
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
}
