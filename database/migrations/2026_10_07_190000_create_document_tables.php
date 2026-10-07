<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('tax_number')->nullable();
            $table->string('tax_office')->nullable();
            $table->text('address');
            $table->string('logo_path')->nullable();
            $table->text('footnote');
            $table->timestamps();
        });

        DB::table('company_settings')->insert([
            'legal_name' => 'Onur Kırtasiye',
            'tax_number' => null,
            'tax_office' => null,
            'address' => 'Eskişehir',
            'logo_path' => null,
            'footnote' => 'Bu belge resmi e-İrsaliye/e-belge yerine geçmez.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('delivery_documents', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('delivery_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('dealer_id')->constrained()->restrictOnDelete();
            $table->date('issued_on');
            $table->string('company_legal_name');
            $table->string('company_tax_number')->nullable();
            $table->string('company_tax_office')->nullable();
            $table->text('company_address');
            $table->text('company_footnote');
            $table->string('dealer_name');
            $table->string('dealer_tax_number')->nullable();
            $table->string('dealer_tax_office')->nullable();
            $table->string('order_number');
            $table->string('recipient_name');
            $table->string('driver_name');
            $table->string('province');
            $table->string('district');
            $table->text('delivery_address');
            $table->timestamps();
        });

        Schema::create('delivery_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_document_id')->constrained()->restrictOnDelete();
            $table->string('sku');
            $table->string('product_name');
            $table->string('unit_name');
            $table->unsignedInteger('pieces');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_document_lines');
        Schema::dropIfExists('delivery_documents');
        Schema::dropIfExists('company_settings');
    }
};
