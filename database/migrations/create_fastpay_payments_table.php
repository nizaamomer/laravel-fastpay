<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fastpay_payments', function (Blueprint $table) {
            $table->id();
            $table->string('store')->default('default');
            $table->string('order_id')->unique();
            $table->string('gw_transaction_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('IQD');
            $table->string('status')->default('Pending')->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile_number')->nullable();
            $table->string('redirect_uri')->nullable();
            $table->nullableMorphs('payable');
            $table->timestamp('validated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fastpay_payments');
    }
};
