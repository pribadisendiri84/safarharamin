# SafarHaramin

Agensi haji & umroh — product baru, terpisah dari RumahSiap dan FashionDialZena.

Pola katalog sama seperti RumahSiap (listing, filter, detail, inquiry WhatsApp, admin). Isi penawaran mengikuti contoh [Umroh Arminareka](https://pribadisendiri84.github.io/umroharminareka/): Home, Paket, Gallery, Testimoni, plus daftar & tabungan.

Brand placeholder: **SafarHaramin**. Bukan merek Arminareka.

## Stack

- Laravel 13 (PHP 8.2+)
- SQLite (lokal) / MySQL (Hostinger)

## Jalankan lokal

```bash
cd ~/bukalapak/Noted/safarharamin
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --port=8011
```

- Website: http://127.0.0.1:8011
- Admin: http://127.0.0.1:8011/admin/login
- Email: `admin@safarharamin.id`
- Password: `admin123`

## Halaman

| URL | Isi |
|-----|-----|
| `/` | Beranda + paket unggulan + testimoni + gallery |
| `/paket` | Katalog + filter tipe/kota/harga |
| `/paket/{slug}` | Detail, fasilitas, form tanya |
| `/daftar` | Pendaftaran jamaah |
| `/haji-plus` | Landing haji plus & furoda |
| `/tabungan` | Tabungan umroh |
| `/galeri` | Gallery keberangkatan |
| `/testimoni` | Testimoni jamaah |
| `/kalkulator-cicilan` | Simulasi setoran |
| `/admin` | Kelola paket, galeri, testimoni, pengajuan, nomor WA |
