# SafarHaramin

Agensi haji & umroh — product baru, terpisah dari RumahSiap dan FashionDialZena.

Pola katalog sama seperti RumahSiap (listing, filter, detail, inquiry WhatsApp, admin). Isi penawaran mengikuti contoh [Umroh Arminareka](https://pribadisendiri84.github.io/umroharminareka/): Home, Paket, Gallery, Testimoni, plus daftar & tabungan.

Brand placeholder: **SafarHaramin**. Bukan merek Arminareka.

## Stack

- Laravel 13 (PHP ≥ 8.3)
- SQLite (lokal) / MySQL (Sumopod / produksi)

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

## Deploy ke Sumopod (VPS)

Pola sama seperti ALZena: Ubuntu, Nginx, MySQL, PHP-FPM. Kode di `/var/www/safarharamin`.

Repo ada di **GitHub** (bukan GitLab):

`git@github.com:pribadisendiri84/safarharamin.git`

Sementara **tanpa domain** — akses lewat IP publik VPS (`http://IP_VPS`). SSL/Certbot ditunda sampai ada domain.

Kalau Alzena sudah jalan di VPS yang sama: database, folder, dan `.env` **jangan digabung**. Supaya `http://IP` membuka situs ini (bukan Alzena), jadikan Nginx site SafarHaramin sebagai `default_server`. Alzena tetap lewat domainnya.

### 1. Stack (lewati jika VPS sudah dipakai Alzena)

```bash
apt update
apt install -y nginx mysql-server unzip git curl
```

PHP 8.3+ (di VPS Alzena biasanya sudah **8.4**):

```bash
apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-gd php8.4-zip php8.4-bcmath
```

Composer: https://getcomposer.org/download/

MySQL — user/database **baru** (ganti password):

```sql
CREATE DATABASE safarharamin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'safarharamin'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON safarharamin.* TO 'safarharamin'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Deploy key + clone / pull

Di VPS, jika belum ada kunci SSH:

```bash
ssh-keygen -t ed25519 -C "vps-safarharamin" -f /root/.ssh/id_ed25519 -N ""
cat /root/.ssh/id_ed25519.pub
```

Tempel public key di GitHub → repo **safarharamin** → **Settings → Deploy keys → Add** (read-only cukup).

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
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | sesuai langkah 1 |
| `SESSION_DOMAIN` | `null` |

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

### 4. Nginx (document root = `public/`)

`/etc/nginx/sites-available/safarharamin`:

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name IP_VPS _;
    root /var/www/safarharamin/public;

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

### 5. Update setelah `git pull`

```bash
cd /var/www/safarharamin
git fetch origin
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
chown -R www-data:www-data storage bootstrap/cache
systemctl reload php8.4-fpm
```

Tidak perlu reload Nginx kecuali config Nginx berubah.

### 6. Nanti kalau sudah ada domain

1. A record domain ke `IP_VPS`.
2. Ganti `server_name` di Nginx, boleh lepas `default_server`.
3. `certbot --nginx -d domain.id`
4. `APP_URL=https://domain.id` lalu `php artisan config:cache`.
