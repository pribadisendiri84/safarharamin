# Panduan Operasional SafarHaramin

Dokumen ini untuk staf admin yang mengelola **keberangkatan, jamaah, grouping kamar, dan pembukuan** setelah calon jamaah closing.

**Akses:** `/admin/login` → sidebar **Operasional**

| Menu | Fungsi |
|------|--------|
| **Ringkasan** | Dashboard: statistik jamaah, room, dan pembayaran |
| **Keberangkatan** | Buat/edit program per tanggal berangkat |
| **Jamaah** | Data jamaah, pembukuan DP/pelunasan, bukti bayar |

Grouping room dan rekap keberangkatan diakses dari **Keberangkatan** atau **Ringkasan** (bukan menu terpisah di sidebar).

---

## Alur kerja standar

```
Pengajuan closing  →  Keberangkatan  →  Jamaah  →  Grouping room  →  Pembukuan
     (Pendaftaran)      (buat program)   (input data)  (assign kamar)    (DP, pelunasan)
```

1. **Pengajuan** — follow-up calon jamaah dari website/form daftar. Set status **Closing** jika deal.
2. **Keberangkatan** — buat program operasional (mis. *Umroh Ramadhan 15 Mar 2026*).
3. **Jamaah** — input data jamaah (manual atau import dari pengajuan closing).
4. **Grouping room** — kelompokkan jamaah ke room Quad / Triple / Double (+ Double Plus untuk haji).
5. **Pembukuan** — catat DP, pelunasan, porsi (haji), upload bukti, cetak invoice.

---

## 1. Ringkasan Keberangkatan

**Menu:** Operasional → **Ringkasan**

Halaman ini menampilkan gambaran umum seluruh operasi:

### Statistik atas
- **Total jamaah** per tipe kamar (Quad, Triple, Double, Double Plus)
- **Total room**, **Room full**, **Room belum penuh**, **Belum group**
- **Lunas / Cicilan / Belum bayar** — status pembayaran semua jamaah
- **Lebih bayar** — jika ada jamaah yang total bayarnya melebihi harga paket

### Panel bawah
- **Jamaah lebih bayar** — daftar yang perlu dicek (refund atau penyesuaian harga)
- **Detail room belum penuh** — room yang masih ada slot kosong; klik **Grouping** untuk melengkapi
- **Keberangkatan terbaru** — shortcut ke **Grouping** dan **Rekap** per program

### Tombol cepat
- **+ Keberangkatan** — buat program baru
- **+ Jamaah** — tambah jamaah baru

---

## 2. Keberangkatan

**Menu:** Operasional → **Keberangkatan**

Keberangkatan = program operasional internal (bisa terhubung ke paket katalog, tapi tidak wajib).

### Buat keberangkatan baru

Klik **Tambah keberangkatan**, isi form:

| Field | Keterangan |
|-------|------------|
| **Paket katalog** | Opsional. Pilih paket website jika ingin terhubung ke katalog. Bisa juga **Manual** |
| **Nama program** | Wajib. Contoh: *Umroh Reguler Maret 2026*, *Haji Khusus 1447* |
| **Jenis** | **Umroh** atau **Haji** — menentukan tipe kamar & field hotel |
| **Tanggal keberangkatan** | Tanggal berangkat |
| **Maskapai** | Nama maskapai, mis. Saudia, Garuda |
| **Nomor penerbangan** | Opsional |
| **Hotel Makkah / Madinah** | Nama hotel di Makkah & Madinah |
| **Hotel Transit / Maktab** | Hanya muncul jika jenis = **Haji** |
| **Catatan** | Catatan internal |

Klik **Simpan**.

### Setelah keberangkatan dibuat

Dari daftar keberangkatan, setiap baris punya:

| Tombol | Fungsi |
|--------|--------|
| **Grouping** | Assign jamaah ke room |
| **Rekap** | Lihat ringkasan room + status bayar per jamaah |
| **Edit** | Ubah data keberangkatan |
| **Hapus** | Soft delete (bisa dipulihkan dari tab Terhapus) |

### Filter
- Cari program, maskapai, atau nomor penerbangan
- Filter **jenis** (Umroh / Haji)

---

## 3. Jamaah

**Menu:** Operasional → **Jamaah**

### Daftar jamaah

Kolom utama: **Nama**, **Jenis** (umroh/haji), **HP**, **Keberangkatan**, **Kamar** (tipe + nomor room), **Group**, **Bayar**.

**Tab jenis:** Semua · Umroh · Haji

**Filter:**
- Cari nama, HP, ID/porsi haji
- Keberangkatan tertentu
- Tipe kamar (Quad / Triple / Double / Double Plus)
- Status group: Grouped / Belum group
- Status bayar: Lunas / Cicilan / Belum bayar

**Status bayar:**
- **0%** — belum ada pembayaran
- **xx%** — cicilan (belum lunas)
- **100%** — lunas
- **Lebih bayar** — total bayar melebihi harga paket

### Tambah jamaah

Klik **Tambah jamaah**, isi form:

| Field | Keterangan |
|-------|------------|
| **Keberangkatan** | Wajib. Pilih program yang sudah dibuat |
| **Nama lengkap** | Wajib |
| **Nomor HP** | Opsional |
| **Jenis kelamin** | Opsional |
| **Tipe kamar** | Wajib. Quad (4 org), Triple (3), Double (2). **Double Plus** hanya untuk keberangkatan **Haji** |
| **Harga paket (Rp)** | Harga yang disepakati untuk jamaah ini. Dipakai hitung sisa/lunas |

#### Field khusus haji

Muncul otomatis jika keberangkatan berjenis **Haji**:

| Field | Keterangan |
|-------|------------|
| **ID / catatan haji** | Nomor registrasi atau catatan internal |
| **Nomor porsi** | Nomor porsi haji |
| **Hotel Makkah / Madinah / Transit / Maktab** | Disimpan **per keberangkatan** — berlaku untuk semua jamaah di program yang sama. Mengisi di form jamaah akan mengupdate data keberangkatan |

**Catatan:** Jika jamaah sudah masuk room, muncul peringatan. Keluarkan dulu dari grouping sebelum mengubah keberangkatan atau tipe kamar.

### Detail jamaah

Klik **Detail** untuk melihat:

1. **Profil jamaah** — data pribadi, keberangkatan, room, hotel (haji)
2. **Pembukuan** — harga paket, total dibayar, sisa / lebih bayar, tanggal DP & pelunasan
3. **Catat transaksi** — form input pembayaran baru
4. **Riwayat transaksi** — semua pembayaran + bukti + invoice

---

## 4. Grouping Room

**Akses:** Keberangkatan → **Grouping**, atau dari detail jamaah / Ringkasan

Grouping = menempatkan jamaah ke room berdasarkan **tipe kamar** yang dipilih saat input jamaah.

### Kapasitas room

| Tipe | Kapasitas | Kode room |
|------|-----------|-----------|
| Quad | 4 jamaah | Q1, Q2, … |
| Triple | 3 jamaah | T1, T2, … |
| Double | 2 jamaah | D1, D2, … |
| Double Plus | 2 jamaah | DP1, DP2, … (hanya haji) |

### Tab tipe kamar

Di atas halaman grouping ada tab **QUAD · TRIPLE · DOUBLE** (dan **DOUBLE PLUS** untuk haji). Setiap tab hanya menampilkan room & jamaah dengan tipe kamar tersebut.

### Cara grouping manual

1. **Buat room** — klik **+ Buat room QUAD** (atau tipe aktif)
2. **Tambah jamaah ke room** — pilih jamaah dari dropdown di kartu room, klik **Tambah ke room**
3. **Keluarkan jamaah** — klik **×** di samping nama di dalam room
4. **Pindah room** — pilih jamaah + room tujuan, klik **Pindahkan**
5. **Hapus room kosong** — hanya bisa jika room belum berisi jamaah

Panel kanan **Belum group** menampilkan jamaah yang belum punya room (per tipe kamar aktif).

### Auto Group

Klik **Auto Group** di kanan atas. Sistem akan:

1. Mengisi room yang masih ada slot kosong
2. Membuat room baru otomatis jika masih ada jamaah belum group
3. Mengurutkan jamaah berdasarkan nama

Auto Group **tidak** mengubah jamaah yang sudah grouped. Aman dijalankan ulang.

### Status room

- **Full** — kapasitas terpenuhi
- **Belum penuh** — masih ada slot kosong
- **Kosong** — belum ada jamaah

---

## 5. Rekap Keberangkatan

**Akses:** Keberangkatan → **Rekap**

Halaman read-only untuk review sebelum berangkat:

- Statistik jamaah & room per tipe kamar
- Daftar room per tipe dengan **nama jamaah + status bayar** (Lunas / xx% / Belum bayar)
- Daftar **Belum group** per tipe kamar

Gunakan rekap untuk cek: apakah semua jamaah sudah grouped? Siapa yang belum lunas?

---

## 6. Pembukuan & Transaksi

**Akses:** Jamaah → **Detail** → bagian **Catat transaksi**

### Jenis transaksi

**Umroh:**

| Jenis | Kapan dipakai |
|-------|---------------|
| **DP** | Uang muka / cicilan awal |
| **Pelunasan** | Pembayaran sisa hingga lunas |
| **Penyesuaian** | Koreksi nominal (naik/turun harga) |
| **Lain-lain** | Biaya tambahan: vaksin, handling, dll. |

**Haji** (tambahan):

| Jenis | Kapan dipakai |
|-------|---------------|
| **Porsi** | Pembayaran porsi haji (terpisah dari DP/pelunasan paket) |

### Cara catat transaksi

1. Pilih **Jenis**
2. Isi **Nominal (Rp)**
3. Isi **Tanggal bayar**
4. **Catatan** — opsional; wajib diisi jelas untuk jenis Lain-lain
5. **Bukti pembayaran** — upload JPG, PNG, WEBP, atau PDF (maks. 5 MB)
6. Klik **Simpan transaksi**

Sistem otomatis:
- Menghitung **total dibayar** dan **sisa**
- Mengisi **tanggal DP** (transaksi DP pertama) dan **tanggal pelunasan** (saat lunas)
- Membuat **nomor invoice** (format `INV-YYYYMM-0001`)
- Menandai **Lebih bayar** jika total melebihi harga paket

### Riwayat transaksi

Setiap baris menampilkan tanggal, jenis, nominal, catatan, bukti, dan petugas input.

| Tombol | Fungsi |
|--------|--------|
| **Lihat** | Buka bukti pembayaran |
| **Print** | Cetak invoice / kwitansi (tab baru) |
| **Hapus** | Hapus transaksi (total bayar dihitung ulang) |

---

## 7. Import dari Pengajuan Closing

**Menu:** Pendaftaran → **Pengajuan** → buka detail pengajuan

Alur import jamaah dari penjualan:

### Langkah 1 — Closing di pengajuan

1. Buka pengajuan calon jamaah
2. Ubah status menjadi **Closing**
3. Isi **Jumlah jamaah**, **Nilai closing (Rp)**, **Tanggal closing**
4. Simpan

Seat paket katalog akan berkurang otomatis.

### Langkah 2 — Pindah ke Jamaah

Setelah closing, muncul panel **Pindah ke Jamaah**:

1. Pilih **Keberangkatan** tujuan (harus sudah dibuat di menu Keberangkatan)
2. Pilih **Tipe kamar** default untuk semua jamaah import
3. Isi **Nama jamaah** (satu field per orang; nama kontak pengajuan otomatis di jamaah pertama)
4. Klik **Pindah ke Jamaah**

Sistem membuat jamaah sebanyak jumlah closing dengan:
- Harga paket = nilai closing ÷ jumlah jamaah
- HP & catatan dari pengajuan (jamaah pertama)

Setelah import, lanjut ke **Grouping room** dan **Pembukuan** (catat DP jika sudah diterima).

> Pengajuan yang sudah di-import tidak bisa di-import ulang. Gunakan tombol **Lihat jamaah** / **Grouping room** di panel yang sama.

---

## 8. Tips operasional

### Urutan yang disarankan
1. Buat **Keberangkatan** dulu
2. Input / import **Jamaah** (pastikan tipe kamar benar)
3. Isi **Harga paket** di setiap jamaah
4. Jalankan **Auto Group** atau grouping manual
5. Catat **DP** saat uang masuk
6. Cek **Rekap** & **Ringkasan** sebelum keberangkatan

### Umroh vs Haji

| | Umroh | Haji |
|---|-------|------|
| Tipe kamar | Quad, Triple, Double | + Double Plus |
| Hotel | Makkah, Madinah | + Transit, Maktab |
| Transaksi | DP, Pelunasan, Penyesuaian, Lain-lain | + Porsi |
| Field jamaah | Standar | + ID haji, nomor porsi |

### Mengubah tipe kamar / keberangkatan
Jamaah yang sudah di room **tidak bisa** langsung di-edit keberangkatan/tipe kamarnya. Keluarkan dulu dari room (Grouping → klik **×**), baru edit di form jamaah.

### Lebih bayar
Jika muncul status **Lebih bayar**:
- Periksa apakah harga paket sudah benar
- Catat **Penyesuaian** negatif, atau
- Proses refund manual & catat di **Lain-lain** dengan catatan jelas

### Upload bukti gagal
Pastikan file ≤ 5 MB, format JPG/PNG/WEBP/PDF. Di server produksi, batas upload Nginx/PHP harus ≥ 12 MB (lihat `README.md` bagian deploy).

---

## 9. Ringkasan menu terkait

Modul operasional terhubung dengan menu lain:

| Menu | Hubungan dengan operasi |
|------|-------------------------|
| **Pengajuan** | Sumber jamaah closing → import ke operasi |
| **Paket** | Referensi katalog; seat berkurang saat closing |
| **Log Aktivitas** | Audit perubahan data admin |

---

## Akses cepat URL

| Halaman | URL |
|---------|-----|
| Ringkasan | `/admin/operasi` |
| Keberangkatan | `/admin/operasi/keberangkatan` |
| Jamaah | `/admin/operasi/jamaah` |
| Grouping | `/admin/operasi/keberangkatan/{id}/grouping` |
| Rekap | `/admin/operasi/keberangkatan/{id}/rekap` |

Ganti `{id}` dengan ID keberangkatan dari daftar Keberangkatan.
