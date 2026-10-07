# Onur B2B

Eskişehir kırtasiye dağıtımı için bayi ve dağıtım yönetim sistemi. Ürün kararları `PROJE-KURGU.md` dosyasındadır.

## Yerel çalıştırma

- PHP 8.3+ (yerelde 8.5 çalışır; bağımlılıklar 8.3 platformuna kilitlidir)
- MariaDB 10.11 veya MySQL 8, veritabanı `onurb2b`, bağlantı `127.0.0.1`
- Kuyruk sürücüsü yerelde `database`. Redis production içindir.

```bash
composer install
npm install
npm run build
php artisan migrate
php artisan db:seed
php artisan serve
```

Sağlık ucu: `/up`
