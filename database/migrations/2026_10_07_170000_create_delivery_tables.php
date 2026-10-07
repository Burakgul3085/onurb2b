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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('dealer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('scheduled_on');
            $table->time('scheduled_time')->nullable();
            $table->string('status');
            $table->string('province');
            $table->string('district');
            $table->text('delivery_address');
            $table->string('recipient_name')->nullable();
            $table->text('note')->nullable();
            $table->string('proof_path')->nullable();
            $table->timestamp('departed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scheduled_on', 'sequence']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('delivery_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_line_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('shipped_pieces');
            $table->unsignedInteger('delivered_pieces')->default(0);
            $table->timestamps();

            $table->unique(['delivery_id', 'order_line_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_lines');
        Schema::dropIfExists('deliveries');
    }
};
