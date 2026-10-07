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
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('document_discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('minimum_quantity')->default(1);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('prices_include_vat')->default(false);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['price_list_id', 'product_id', 'is_active']);
        });

        Schema::create('dealer_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('minimum_quantity')->default(1);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('prices_include_vat')->default(false);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['dealer_id', 'product_id', 'is_active']);
        });

        Schema::table('dealers', function (Blueprint $table) {
            $table->foreignId('price_list_id')->nullable()->after('payment_term_days')->constrained('price_lists')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_list_id');
        });

        Schema::dropIfExists('dealer_prices');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
    }
};
