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
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->timestamp('due_notified_at')->nullable()->after('due_on');
        });

        $now = now();
        $templates = [
            ['critical_stock', 'Kritik stok', 'Kritik stok', "Merhaba {contact},\nEşiğin altındaki stoklar:\n{lines}"],
            ['daily_report', 'Günlük rapor', 'Günlük operasyon özeti {date}', "Merhaba {contact},\n{date} satış toplamı {sales}. Sipariş sayısı {orders}."],
        ];

        foreach ($templates as [$key, $name, $subject, $body]) {
            DB::table('mail_templates')->updateOrInsert(
                ['key' => $key],
                [
                    'name' => $name,
                    'subject' => $subject,
                    'body' => $body,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropColumn('due_notified_at');
        });

        DB::table('mail_templates')->whereIn('key', ['critical_stock', 'daily_report'])->delete();
    }
};
