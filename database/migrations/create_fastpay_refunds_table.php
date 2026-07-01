<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fastpay_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('fastpay_payments')->cascadeOnDelete();
            $table->string('invoice_id')->nullable();
            $table->string('msisdn');
            $table->decimal('amount', 12, 2);
            $table->boolean('refunded')->default(false)->index();
            $table->timestamp('status_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fastpay_refunds');
    }
};
