# SafarHaramin

Agensi haji & umroh — product baru, terpisah dari RumahSiap dan FashionDialZena.

Pola katalog sama seperti RumahSiap (listing, filter, detail, inquiry WhatsApp, admin). Isi penawaran mengikuti contoh [Umroh Arminareka](https://pribadisendiri84.github.io/umroharminareka/): Home, Paket, Gallery, Testimoni, plus daftar & tabungan.

Brand placeholder: **SafarHaramin**. Bukan merek Arminareka.

## Stack

- Laravel 13 (PHP ≥ 8.3)
- SQLite (lokal) / MySQL (Sumopod / produksi)

## Jalankan lokal

```bash
git clone git@github.com:pribadisendiri84/safarharamin.git
cd safarharamin
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --port=8011
```

HTTPS: `git clone https://github.com/pribadisendiri84/safarharamin.git`

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
| `/admin/operasi` | Ringkasan keberangkatan (grouping, pembukuan DP/pelunasan) |

### Admin — Operasional

Modul internal untuk agen perjalanan:

- **Keberangkatan** — program umroh/haji per tanggal berangkat
- **Jamaah** — data jamaah, status grouping & pembayaran (lunas / cicilan % / belum bayar)
- **Grouping room** — assign jamaah ke room quad/triple/double, auto-group
- **Rekap** — ringkasan room per keberangkatan + status bayar per jamaah
- **Pembukuan** — catat DP, pelunasan, porsi (haji), penyesuaian, lain-lain
- **Bukti bayar & kwitansi** — upload bukti (foto/PDF), nomor invoice otomatis per transaksi
- **Import dari pengajuan** — pengajuan status Closing bisa dipindah ke jamaah (pilih keberangkatan + nama jamaah)

Menu sidebar admin (bergrup):

| Grup | Menu |
|------|------|
| *(standalone)* | Dashboard |
| Website | Paket · Galeri · Testimoni |
| Pendaftaran | Pengajuan |
| Operasional | Ringkasan · Keberangkatan · Jamaah |
| Master Data | Kota Embarkasi |
| Sistem | Pengaturan · Log Aktivitas · Trafik Website · Pengguna |

## Deploy ke Sumopod (VPS)

Alzena dan SafarHaramin **satu VPS**. Yang dipakai bersama: Nginx, PHP-FPM, MySQL server. Yang **wajib terpisah**: folder, `.env`, dan database.

| | Alzena | SafarHaramin |
|---|---|---|
| Folder | `/var/www/fashiondialzena` (atau path Alzena yang sudah ada) | `/var/www/safarharamin` |
| Database / user | punya Alzena, jangan disentuh | `arminareka` / `arminareka` |
| Akses | domain Alzena | `http://IP_VPS` (site ini `default_server`) |

Repo: `git@github.com:pribadisendiri84/safarharamin.git` (GitHub, bukan GitLab).

Sementara **tanpa domain**. SSL/Certbot ditunda sampai ada domain.

### Cepat — sudah `git pull`?

Jalankan di VPS (folder `/var/www/safarharamin`):

```bash
cd /var/www/safarharamin
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
systemctl reload php8.4-fpm
```

**Jangan** jalankan `db:seed` lagi kalau sudah ada data produksi.

Cek singkat:

```bash
php artisan migrate:status   # semua migration harus Ran
ls -la public/storage        # symlink ke storage/app/public
```

Upload foto paket, galeri, dan bukti pembayaran jamaah disimpan di `storage/app/public/` — symlink `public/storage` **wajib** ada (lihat setup pertama).

### 1. Stack (hanya jika VPS masih kosong)

Kalau Alzena sudah jalan, **lewati install** di bawah. Lanjut ke buat database.

```bash
apt update
apt install -y nginx mysql-server unzip git curl
apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-gd php8.4-zip php8.4-bcmath
```

Composer: https://getcomposer.org/download/

### 1b. Database baru (jangan dilewati)

Jangan pakai `DB_DATABASE` / user Alzena. Ganti `PASSWORD_KUAT`:

```sql
CREATE DATABASE arminareka CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'arminareka'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON arminareka.* TO 'arminareka'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Deploy key + clone / pull

Kalau VPS sudah clone Alzena via SSH, **jangan** `ssh-keygen` lagi. Tampilkan key yang ada:

```bash
cat /root/.ssh/id_ed25519.pub
```

Kalau belum ada kunci:

```bash
ssh-keygen -t ed25519 -C "vps-safarharamin" -f /root/.ssh/id_ed25519 -N ""
cat /root/.ssh/id_ed25519.pub
```

Tempel public key di GitHub → repo **safarharamin** → **Settings → Deploy keys → Add** (read-only cukup). Key yang sama boleh dipakai di dua repo.

Clone pertama (ketik `yes` saat host key GitHub, bukan `y`):

```bash
mkdir -p /var/www
cd /var/www
git clone git@github.com:pribadisendiri84/safarharamin.git
cd safarharamin
```

Kalau folder sudah ada, mulai dari sini:

```bash
cd /var/www/safarharamin
git fetch origin
git pull origin main
```

`.env` **tidak** ikut Git. Jangan menimpa `.env` produksi saat pull.

### 3. `.env` produksi (akses IP)

```bash
cp .env.example .env
nano .env
```

Ganti `IP_VPS` dengan IP publik Sumopod (contoh `103.x.x.x`):

| Variabel | Produksi (IP dulu) |
|----------|-------------------|
| `APP_NAME` | `SafarHaramin` (bisa diganti di admin Pengaturan) |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `http://IP_VPS` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `arminareka` / `arminareka` / password langkah 1b |
| `SESSION_DRIVER` | `database` (butuh tabel sessions dari migration) |
| `SESSION_DOMAIN` | `null` |

Setup pertama (sekali saja):

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
chown -R www-data:www-data /var/www/safarharamin
chmod -R ug+rwx storage bootstrap/cache
```

`db:seed` **hanya sekali** (admin, kota, paket contoh). Jangan diulang setelah ada data nyata.

Admin: `http://IP_VPS/admin/login` — `admin@safarharamin.id` / `admin123`. Ganti password setelah login.

Verifikasi upload: buat/edit paket dengan foto, atau catat transaksi jamaah dengan bukti bayar — file harus bisa dibuka dari admin.

### 4. Nginx (document root = `public/`)

`/etc/nginx/sites-available/safarharamin`:

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name IP_VPS _;
    root /var/www/safarharamin/public;
    client_max_body_size 12M;

    add_header X-Frame-Options "SAMEORIGIN";
    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Hapus `default_server` dari site Alzena / `default` jika bentrok (`nginx -t` akan bilang).

```bash
ln -s /etc/nginx/sites-available/safarharamin /etc/nginx/sites-enabled/
# kalau ada default bawaan yang ikut default_server:
# rm /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

Cek sock PHP: `ls /run/php/php*-fpm.sock` — samakan dengan `fastcgi_pass`. CLI `php -v` harus versi yang sama dengan FPM.

**Upload foto (galeri, paket, bukti bayar):** batas default Nginx sering 1 MB → error **413 Request Entity Too Large**. Pastikan:

1. Nginx: `client_max_body_size 12M;` di dalam block `server { … }` (sudah ada di contoh di atas), lalu `nginx -t && systemctl reload nginx`
2. PHP-FPM — edit `/etc/php/8.4/fpm/php.ini` (atau `php -i | grep php.ini`):

```ini
upload_max_filesize = 12M
post_max_size = 12M
```

Lalu `systemctl reload php8.4-fpm`.

Aplikasi juga mengompres foto di browser sebelum unggah; tetap naikkan limit server untuk jaga-jaga.

### 5. Update rutin setelah `git pull`

Sama dengan [Cepat — sudah git pull?](#cepat--sudah-git-pull) di atas, plus pull dulu:

```bash
cd /var/www/safarharamin
git fetch origin
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
systemctl reload php8.4-fpm
```

| Perintah | Kapan |
|----------|-------|
| `migrate --force` | **Selalu** — schema berubah tiap fitur baru (operasi jamaah, bukti bayar, invoice, dll.) |
| `composer install` | **Selalu** — kalau `composer.lock` berubah |
| `storage:link` | Hanya jika `public/storage` belum ada / error 404 upload |
| `db:seed` | **Jangan** di produksi yang sudah berisi data |
| `optimize:clear` + `optimize` | **Selalu** — refresh config/route/view cache |

Tidak perlu reload Nginx kecuali config Nginx berubah.

**Migration penting (operasi jamaah):** kalau modul operasi error kolom tidak ada, pastikan migration ini sudah `Ran`:

- `create_operations_tables`
- `add_inquiry_pilgrim_import`
- `add_proof_and_invoice_to_pilgrim_transactions`

### 5b. Troubleshooting deploy

| Gejala | Solusi |
|--------|--------|
| 500 setelah pull | `tail -50 storage/logs/laravel.log` |
| `no such column` / SQL error | `php artisan migrate --force` lalu cek `migrate:status` |
| Foto/bukti bayar 404 | `php artisan storage:link` + `chown -R www-data:www-data storage` |
| **413 Request Entity Too Large** (upload galeri/paket) | Tambah `client_max_body_size 12M;` di Nginx + `upload_max_filesize` / `post_max_size` di PHP-FPM, reload keduanya |
| Upload gagal / permission denied | `chmod -R ug+rwx storage bootstrap/cache` |
| Perubahan `.env` tidak kebaca | `php artisan optimize:clear` lalu `php artisan config:cache` |
| Halaman admin CSS lama | Hard refresh browser (Ctrl+F5) — asset CSS di `public/css/` |

### 6. Nanti kalau sudah ada domain

1. A record domain ke `IP_VPS`.
2. Ganti `server_name` di Nginx, boleh lepas `default_server`.
3. `certbot --nginx -d domain.id`
4. `APP_URL=https://domain.id` lalu `php artisan config:cache`.
