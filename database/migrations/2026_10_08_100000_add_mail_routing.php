<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('notification_email')->nullable()->after('footnote');
        });

        DB::table('company_settings')->whereNull('notification_email')->update([
            'notification_email' => 'burakgul3085@gmail.com',
        ]);

        Schema::table('message_threads', function (Blueprint $table) {
            $table->string('mail_token', 16)->nullable()->unique()->after('subject');
        });

        foreach (DB::table('message_threads')->whereNull('mail_token')->pluck('id') as $id) {
            DB::table('message_threads')->where('id', $id)->update([
                'mail_token' => strtolower(Str::random(10)),
            ]);
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('sender_name')->nullable()->after('user_id');
            $table->string('sender_email')->nullable()->after('sender_name');
            $table->string('external_message_id')->nullable()->unique()->after('body');
        });

        $templates = [
            [
                'key' => 'dealer_application_office',
                'name' => 'Başvuru firma kopyası',
                'subject' => 'Yeni bayi başvurusu: {company}',
                'body' => "Sayın {contact},\n\n{company} firmasından {email} adresiyle yeni bir bayi başvurusu geldi. Telefon: {phone}. Başvuruyu panelden inceleyebilirsiniz.",
            ],
            [
                'key' => 'order_placed_office',
                'name' => 'Sipariş firma kopyası',
                'subject' => 'Yeni sipariş: {order_number}',
                'body' => "Sayın {contact},\n\n{company} firması {order_number} numaralı siparişi verdi. Siparişi panelden inceleyebilirsiniz.",
            ],
            [
                'key' => 'due_date_office',
                'name' => 'Vade firma kopyası',
                'subject' => 'Vadesi yaklaşan cari: {order_number}',
                'body' => "Sayın {contact},\n\n{company} firmasının {order_number} numaralı siparişinde {amount} tutarındaki borcun vadesi {due_on}.",
            ],
        ];

        foreach ($templates as $template) {
            DB::table('mail_templates')->updateOrInsert(
                ['key' => $template['key']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique(['external_message_id']);
            $table->dropColumn(['sender_name', 'sender_email', 'external_message_id']);
        });

        Schema::table('message_threads', function (Blueprint $table) {
            $table->dropUnique(['mail_token']);
            $table->dropColumn('mail_token');
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('notification_email');
        });
    }
};
