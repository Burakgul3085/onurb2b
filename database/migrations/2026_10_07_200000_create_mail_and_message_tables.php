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
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $templates = [
            ['dealer_application', 'Bayi başvurusu', 'Başvurunuz alındı', "Merhaba {contact},\n{company} başvurunuz alındı. Onay sonrası giriş bilgileriniz iletilecek."],
            ['dealer_approved', 'Bayi onayı', 'Başvurunuz onaylandı', "Merhaba {contact},\n{company} başvurunuz onaylandı."],
            ['dealer_rejected', 'Bayi ret', 'Başvurunuz sonuçlandı', "Merhaba {contact},\n{company} başvurunuz reddedildi.\n{reason}"],
            ['account_activated', 'Hesap aktivasyonu', 'Hesabınız açıldı', "Merhaba {contact},\n{company} için giriş hesabınız açıldı. E-posta adresinizle giriş yapabilirsiniz."],
            ['order_placed', 'Sipariş alındı', 'Siparişiniz alındı', "Merhaba {contact},\n{order_number} numaralı siparişiniz alındı."],
            ['order_approved', 'Sipariş onaylandı', 'Siparişiniz onaylandı', "Merhaba {contact},\n{order_number} numaralı siparişiniz onaylandı."],
            ['order_preparing', 'Hazırlanıyor', 'Siparişiniz hazırlanıyor', "Merhaba {contact},\n{order_number} numaralı siparişiniz hazırlanıyor."],
            ['order_out_for_delivery', 'Teslimata çıktı', 'Siparişiniz yola çıktı', "Merhaba {contact},\n{order_number} numaralı siparişiniz teslimata çıktı."],
            ['order_delivered', 'Teslim edildi', 'Teslimat tamamlandı', "Merhaba {contact},\n{order_number} numaralı siparişinizin teslimatı tamamlandı."],
            ['collection_recorded', 'Tahsilat işlendi', 'Tahsilatınız işlendi', "Merhaba {contact},\n{company} hesabına {amount} tahsilat işlendi."],
            ['due_date_approaching', 'Vade yaklaşması', 'Vade tarihi yaklaşıyor', "Merhaba {contact},\n{company} hesabında {due_on} vadeli bir kayıt yaklaşıyor."],
            ['new_message', 'Yeni mesaj', 'Yeni mesaj: {subject}', "Merhaba {contact},\n{sender} yeni bir mesaj yazdı.\n{subject}\n{body}"],
        ];

        foreach ($templates as [$key, $name, $subject, $body]) {
            DB::table('mail_templates')->insert([
                'key' => $key,
                'name' => $name,
                'subject' => $subject,
                'body' => $body,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_template_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('recipient');
            $table->string('subject');
            $table->text('body');
            $table->string('status');
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('subject');
            $table->timestamps();

            $table->index(['dealer_id', 'id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_thread_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_threads');
        Schema::dropIfExists('mail_logs');
        Schema::dropIfExists('mail_templates');
    }
};
