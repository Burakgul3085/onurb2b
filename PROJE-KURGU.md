# Onur B2B — Kırtasiye Dağıtım ve Bayi Yönetim Sistemi

Bu dosya, kurulumdan önce kilitlenen ürün ve mimari kararlardır. Yeni sohbette önce bu dosyayı oku.

Çalışma klasörü: `C:\onurb2b` (boş, OneDrive dışında, 6 Ekim 2026’da doğrulandı).
Eski yol `C:\Users\90542\OneDrive\Desktop\onurb2b` artık yoktur. Oraya kurulum yapma.
`C:\Users\90542` altındaki ev dizini git deposuna dokunma. Git yalnızca `C:\onurb2b` içinde açılacak.

Şimdilik yalnızca FAZ 0 yapılacak. Sonraki faza geçme. Ürün, bayi, sipariş ve cari modüllerini bu fazda kodlama.

## 1. Amaç

Eskişehir’de faaliyet gösteren bir kırtasiye ana dağıtıcısı için web tabanlı B2B bayi ve dağıtım yönetim sistemi.

Ana dağıtıcı ürünleri kendi stoğunda tutar ve Eskişehir içindeki bayilere satış yapar.

Sistem şunları sağlayacak:

- bayi yönetimi
- ürün yönetimi
- stok yönetimi
- depo yönetimi
- fiyat yönetimi
- KDV yönetimi
- iskonto yönetimi
- B2B sipariş
- şehir içi teslimat
- cari hesap
- tahsilat
- operasyonel teslim/sevk belgeleri
- raporlama
- PDF ve Excel raporları
- e-posta
- bayi / dağıtıcı mesajlaşması
- bildirim
- dashboard
- kullanıcı, rol, yetki
- audit log

## 2. Kapsam dışı (kesinlikle yapılmayacak)

Bu sistem resmi muhasebe veya resmi e-belge sistemi değildir.

Yapılmayacaklar:

- e-Fatura, e-Arşiv, e-İrsaliye
- GİB ve diğer devlet sistemi entegrasyonları
- banka kredi sistemi
- kredi limiti (yüksek bakiye siparişi engellemez; bakiye yalnızca bilgi olarak görünür)
- çek ve senet
- kargo entegrasyonu, kargo takip, şehirler arası lojistik

Sistem kendi operasyonel kayıtlarını oluşturur: sipariş, satış kaydı, teslim/sevk belgesi, cari hareket, tahsilat, stok hareketi, rapor.

Teslim/sevk PDF belgesinde şu ifade açıkça yer alır:

> Bu belge resmi e-İrsaliye/e-belge yerine geçmez.

## 3. Teknoloji

- PHP 8.3+ (yerelde PHP 8.5.1 kurulu; sunucuda 8.3 veya 8.4. Kurulumda paket platform sınırını doğrula.)
- Laravel 12
- MySQL 8+ (yerelde XAMPP: `C:\xampp\mysql`, istemci PATH’te yok. Bağlantı `127.0.0.1`. Motor MySQL 8 veya MariaDB 10.6+ olmalı.)
- Blade, Livewire 3, Alpine.js, Tailwind CSS
- Chart.js (grafikler gerçek veritabanından; sabit veri yok)
- PDF: `barryvdh/laravel-dompdf` (Chromium / Browsershot kullanma; KVM 2 için ağır)
- Excel: Laravel Excel / PhpSpreadsheet (`maatwebsite/excel`)
- Kimlik: Laravel Breeze, Blade oturum
- Yetki: `spatie/laravel-permission` + Policy. Bayi izolasyonu izin kaydı değildir; sorguda `dealer_id` ile kesilir.
- Kuyruk: Laravel Queue. Yerelde `database` sürücüsü (Redis yok). Production’da Redis. Job sözleşmesi aynı kalır.
- Sunucu (ileride, FAZ 15): Ubuntu, Hostinger KVM 2, Nginx, Supervisor, Laravel scheduler
- İlk sürüm çok kiracılı değildir. Ayrı SPA veya ayrı frontend API kurma. İş kuralları servis katmanında durur ki ileride API eklenebilsin.
- Arayüz dili Türkçe. Enum, tablo ve kod İngilizce.
- Test: Pest. Stil: Pint. PSR, SOLID, DRY. Controller ince kalır. Action, Service, DTO, Policy, Form Request, Event, Listener, Job kullan.

Yerel ortam (6 Ekim 2026):

- Composer 2.9.3, Node 22.14, npm 10.9.2, Git 2.49 mevcut
- Docker yok, Redis yok

## 4. Kilitlenen iş kuralları

### Para ve KDV

- Yalnızca TRY.
- MySQL `DECIMAL(15,2)`. Float ve double yok.
- Hesap `bcmath` ile, satır bazında half-up, 2 hane. Sunucu otoriterdir. İstemciden gelen toplama güvenilmez.
- KDV oranları: 0, 1, 10, 20.
- Fiyat KDV dahil veya hariç olabilir.
- KDV hariç örnek: net 100, KDV 20, toplam 120.
- KDV dahil örnek: brüt 100, oran 20 → net = yuvarla(100 × 100 / 120) = 83,33; KDV = 100 − 83,33 = 16,67.
- Sipariş satırı ürün adı, SKU, birim fiyat, KDV ve iskontoyu o anki değerle kilitler.

### Fiyat önceliği

1. Bayi + ürün özel fiyatı
2. Bayinin bağlı olduğu fiyat listesi
3. Genel ürün fiyatı

Aynı öncelikte: geçerli başlangıç/bitiş tarihi, sipariş miktarını karşılayan en yüksek minimum miktar, sonra en yeni başlangıç.

Fiyat kaydında gerekirse: başlangıç, bitiş, minimum miktar, KDV dahil/hariç, iskonto.

İskonto satır yüzdesidir. Belge geneli tek yüzde FAZ 5’te ele alınır.

### Bayi

Alanlar: firma adı, yetkili, telefon, e-posta, vergi numarası, vergi dairesi, adres, ilçe, teslimat adresi, fatura adresi, fiyat listesi, ödeme vadesi, not, aktif/pasif, başvuru durumu.

Başvuru beklemede başlayabilir. Admin onayından sonra aktifleşir.

İlk ekranda her bayi firmasına tek giriş kullanıcısı yeter. Veritabanında firma ile kullanıcı ayrı kalır; ikinci kişi sonradan yeni satırla eklenebilir, model değişmez. Her kullanıcı tek firmaya bağlıdır. Bir bayi başka bayinin hiçbir verisini göremez. Bu kontrol sunucuda yapılır.

### Roller

Kod adları: `super_admin`, `admin`, `warehouse`, `delivery`, `finance_ops`, `dealer`.

Ekranda Türkçe: sistem yöneticisi, yönetici, depo, teslimat, cari, bayi. Bir kişi birden fazla role sahip olabilir.

### Ürün, birim, depo

Ürün: SKU, barkod (birden fazla), ad, marka, kategori, alt kategori, birim, KDV oranı, alış fiyatı, genel satış fiyatı, minimum stok, kritik stok, açıklama, görsel, aktif/pasif.

Stok taban birimi adettir. Örnek dönüşüm: 1 koli = 20 paket, 1 paket = 10 adet. Sipariş anında miktar adede kilitlenir.

Depo şeması birden fazla depoya açıktır. Başlangıç verisi tek «Merkez Depo».

İl sabit Eskişehir. İlçeler: Odunpazarı, Tepebaşı, Alpu, Beylikova, Çifteler, Günyüzü, Han, İnönü, Mahmudiye, Mihalgazi, Mihalıççık, Sarıcakaya, Seyitgazi, Sivrihisar.

Firma kimliği tek ayar kaydıdır: unvan, vergi, adres, logo, belge dipnotu. Çok kiracılı paket yok.

### Stok

`available_stock = physical_stock - reserved_stock`

Hareket türleri: `PURCHASE`, `SALE`, `RETURN`, `ADJUSTMENT`, `TRANSFER`, `DAMAGE`, `COUNT`. Hareketler silinmez.

Kilitlenen zamanlama (işletme sahibi bunu seçti):

1. Bayi siparişi gönderir (`PENDING`). Stok ayrılmaz.
2. Admin onaylar (`APPROVED`). Onaylanan adet rezerve edilir. Eldeki stok yetmiyorsa kısmi onay veya ret zorunludur; fazlası sessizce ayrılmaz.
3. Mal araca binince (`OUT_FOR_DELIVERY`) sevk edilen adet fiziksel stoktan düşer. Kalan istenen miktar rezervede kalır.
4. Onaydan sonra, araç çıkmadan iptal: kalan rezerv serbest kalır.
5. Teslim başarısız olursa düşülen adet ters stok hareketiyle döner. Satır silinmez.
6. Araç çıktıktan sonra düz iptal yoktur; iade akışına gidilir.

Taslak (`DRAFT`) stoku etkilemez.

İstenen miktar ile teslim edilen miktar ayrı tutulur (örnek: 100 istenen, 60 teslim).

### Sipariş durumları

`DRAFT`, `PENDING`, `APPROVED`, `PREPARING`, `OUT_FOR_DELIVERY`, `DELIVERED`, `PARTIALLY_DELIVERED`, `CANCELLED`, `DELIVERY_FAILED`

Akış: bayi ürün seçer, miktar girer, sepet, teslimat adresi, sipariş.

### Teslimat

Kargo yoktur. Tüm teslimat Eskişehir içidir.

Teslimat: bayi, teslimat adresi, sipariş, teslimat personeli, teslimat sırası, tarih, saat, durum, teslim alan kişi, teslim notu, teslimat kanıtı.

Durumlar: `PREPARING`, `OUT_FOR_DELIVERY`, `DELIVERED`, `FAILED`.

### Operasyonel sevk belgesi

Sistem kendi PDF’ini üretir. Firma, bayi, belge numarası, tarih, sipariş numarası, ürünler, miktarlar, teslim alan, teslim eden. Resmi e-İrsaliye değildir. Dipnot zorunludur.

### Cari ve tahsilat

Çek, senet ve kredi limiti yoktur.

Cari borç, alacak, bakiye, vade, satış, iade, tahsilat ve ödeme üzerinden çalışır. Satış borç yazar. Tahsilat ve iade alacak yazar. Bakiye hareket toplamından hesaplanır, üzerine yazılmaz. Hareketler silinmez; yanlış işlem ters kayıtla düzeltilir.

Vade = belge tarihi + bayinin ödeme vade günü.

Tahsilat yöntemleri: nakit, havale, EFT, POS.

İade FAZ 9’dadır. Siparişe bağlı operasyonel iade: stok `RETURN` + cari alacak. Resmi belge değildir.

### Raporlar ve dashboard

Admin raporları: satış, sipariş, stok, stok hareket, bayi, bayi satış, cari, tahsilat, teslimat, ürün satış, kategori satış, marka satış.

Filtreler: tarih, bayi, ürün, kategori, marka, durum. Çıktı: web, PDF, Excel. Büyük raporlar kuyruktan üretilir.

Admin KPI: bugünkü satış, aylık satış, sipariş sayısı, aktif bayi, bekleyen sipariş, teslimatta olanlar, kritik stok, toplam cari alacak, yaklaşan vadeler.

Grafikler: zaman bazlı satış, sipariş durumları, en çok satış yapan bayiler, en çok satan ürünler, kritik stok, tahsilat/cari, teslimat durumları.

Tarih filtresi: bugün, son 7 gün, son 30 gün, bu ay, geçen ay, bu yıl, özel tarih.

Bayi dashboard yalnızca o bayiyi gösterir: cari bakiye, son sipariş, bekleyen sipariş, teslim edilen, toplam alış, yaklaşan vade, son mesajlar.

### Posta, mesaj, audit, dosya

SMTP. Gönderim kuyruktan. Şablonlar veritabanında yönetilir.

Örnek şablonlar: bayi başvurusu, onay, ret, hesap aktivasyonu, sipariş alındı, sipariş onaylandı, hazırlanıyor, teslimata çıktı, teslim edildi, tahsilat işlendi, vade yaklaşması, yeni mesaj.

Mail log: alıcı, konu, şablon, durum, gönderim zamanı, hata.

Mesajlar thread’dir. Bir siparişe bağlanabilir. Yeni mesajda e-posta bildirimi kuyruğa gidebilir.

Audit: giriş, çıkış, ürün, fiyat, stok düzeltme, sipariş iptali, bayi onayı, cari, tahsilat, kullanıcı, yetki. Alanlar: kullanıcı, eylem, varlık, varlık no, eski değer, yeni değer, IP, user agent, zaman.

Kritik ticari kayıt fiziksel silinmez. Sipariş `CANCELLED`, ürün `INACTIVE`, bayi `INACTIVE`.

Güvenlik: HTTPS, CSRF, XSS, SQL injection koruması, Form Request, Policy, rate limit, güvenli parola ve oturum, dosya yüklemede MIME, uzantı ve boyut. Çalıştırılabilir dosya kabul edilmez. SVG kabul edilmez. IDOR/BOLA özellikle kapanacak.

Yedek (FAZ 15): örneğin her gün 03:00, 7 gün saklama.

Zamanlanmış işler: kuyruk, vade bildirimi, kritik stok, günlük rapor, yedek, temizlik.

## 5. Katman

İstek: Form Request doğrular, Policy yetki ve bayi kapsamını keser, controller ince kalır. Action veya Service hesaplar. Stok ve cari aynı veritabanı transaction’ında yazılır.

Klasör iskeleti:

- `app/Enums`
- `app/Actions`
- `app/Services`
- `app/Policies`
- `app/Support/Money`

Modüller faz ilerledikçe bu ağacın altında büyür. InnoDB, utf8mb4, strict mode, foreign key, unique, index. Liste sorguları sayfalanır ve eager load edilir.

## 6. Faz sırası

Her fazdan önce mevcut kodu analiz et, mimariyi bozma, çakışmayı ve güvenlik etkisini çıkar. Faz sonunda syntax, migration, route, yetki ve testleri kontrol et; hatayı düzelt; kısa rapor ver. Çalışan özelliği gereksiz yere yeniden yazma.

0. Proje kurulumu ve mimari (şimdi yalnızca bu)
1. Authentication, kullanıcı, rol, yetki
2. Bayi yönetimi
3. Ürün, kategori, marka, birim, barkod
4. Depo ve stok
5. Fiyat, KDV, iskonto
6. Bayi kataloğu, hızlı sipariş, sepet
7. Sipariş ve sipariş akışı
8. Eskişehir içi teslimat
9. Cari, tahsilat, vade, operasyonel iade
10. Operasyonel belge
11. Mail ve sistem içi mesajlaşma
12. Raporlama
13. Admin ve bayi dashboard
14. Audit ve güvenlik sertleştirme
15. Production deployment
16. Test ve QA
17. Son kullanıcı senaryoları ve production readiness

Rapor, belge ve dashboard faz numaraları spec’teki 10–13 sırasını korur. İş kuralı olarak iade FAZ 9’a bağlıdır.

## 7. FAZ 0 sınırı

Yapılacak:

- Laravel 12 kurulumu
- Breeze Blade iskeleti
- Livewire 3, Tailwind, Alpine
- Pest ve Pint
- `.env.example`: MySQL, yerel kuyruk `database`, production Redis, SMTP yer tutucuları
- Boş `app/Enums`, `app/Actions`, `app/Services`, `app/Policies`, `app/Support/Money`
- Türkçe yerleşim hazırlığı, güvenlik başlıkları, sağlık rotası
- Yalnızca `C:\onurb2b` içinde `git init` ve ignore
- Uygulama ayağa kalkar

Yapılmayacak:

- Ürün, bayi, sipariş, stok, cari, fiyat modülleri
- FAZ 1 yetki matrisi
- e-belge, çek, senet, kargo, kredi limiti, çok kiracı

FAZ 0 raporu şunları içerecek: değişen dosyalar, migration, route, testler, geçen testler, varsayımlar, kalan riskler.

## 8. Faz sonu rapor şablonu

Her faz sonunda kısa ve net:

- oluşturulan / değişen dosyalar
- migration’lar
- route’lar
- yazılan testler
- geçen testler
- varsayımlar
- kalan teknik riskler
