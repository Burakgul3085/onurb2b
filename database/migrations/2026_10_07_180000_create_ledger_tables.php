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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('dealer_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('method')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->date('document_date');
            $table->date('due_on')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('reverses_entry_id')->nullable()->constrained('ledger_entries')->restrictOnDelete();
            $table->timestamps();

            $table->unique('delivery_id');
            $table->unique('reverses_entry_id');
            $table->index(['dealer_id', 'document_date']);
            $table->index('due_on');
        });

        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('dealer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('ledger_entry_id')->unique()->constrained()->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_line_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('pieces');
            $table->timestamps();

            $table->unique(['order_return_id', 'order_line_id']);
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->unsignedInteger('returned_pieces')->default(0)->after('delivered_pieces');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn('returned_pieces');
        });

        Schema::dropIfExists('order_return_lines');
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('ledger_entries');
    }
};
