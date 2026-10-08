<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('mail_templates')->updateOrInsert(
            ['key' => 'password_reset'],
            [
                'name' => 'Parola sıfırlama',
                'subject' => 'Parola sıfırlama bağlantınız',
                'body' => "Merhaba {contact},\n\nParolanızı seçmek için bağlantıyı açın. Bağlantı 60 dakika geçerlidir ve bir kez kullanılır.\n\n{reset_url}\n\nBu isteği siz yapmadıysanız bu postayı yok sayın.",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mail_templates')->where('key', 'password_reset')->delete();
    }
};
