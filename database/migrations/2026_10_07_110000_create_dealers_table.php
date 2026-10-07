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
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('phone', 30);
            $table->string('email');
            $table->string('tax_number', 11);
            $table->string('tax_office');
            $table->string('province');
            $table->string('district');
            $table->text('address');
            $table->text('delivery_address');
            $table->text('billing_address');
            $table->unsignedSmallInteger('payment_term_days')->default(0);
            $table->text('notes')->nullable();
            $table->string('application_status');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('email');
            $table->unique('tax_number');
            $table->index('application_status');
            $table->index('is_active');
            $table->index('district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
