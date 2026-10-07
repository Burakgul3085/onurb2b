<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('dealer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->string('province');
            $table->string('district');
            $table->text('delivery_address');
            $table->text('note')->nullable();
            $table->decimal('document_discount_percent', 5, 2);
            $table->decimal('net', 15, 2);
            $table->decimal('vat', 15, 2);
            $table->decimal('gross', 15, 2);
            $table->decimal('discount_amount', 15, 2);
            $table->decimal('payable', 15, 2);
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['dealer_id', 'status']);
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('sku');
            $table->string('product_name');
            $table->string('unit_name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('pieces_per_unit');
            $table->unsignedInteger('requested_pieces');
            $table->unsignedInteger('approved_pieces')->default(0);
            $table->unsignedInteger('delivered_pieces')->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percent', 5, 2);
            $table->boolean('prices_include_vat');
            $table->string('vat_rate');
            $table->decimal('net', 15, 2);
            $table->decimal('vat', 15, 2);
            $table->decimal('gross', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
