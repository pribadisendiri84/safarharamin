<?php

namespace App\Services;

use App\Models\City;
use App\Models\Package;
use App\Models\PackageKind;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PackageCsvImporter
{
    /** @var list<string> */
    public const REQUIRED_COLUMNS = [
        'judul',
        'jenis',
        'tipe_paket',
        'embarkasi',
        'tanggal',
        'durasi',
        'harga_quad',
        'harga_triple',
        'harga_double',
        'seat_total',
        'seat_sisa',
    ];

    /**
     * @return array{created: int, errors: list<array{row: int, message: string}>}
     */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ['created' => 0, 'errors' => [['row' => 0, 'message' => 'File CSV tidak bisa dibaca.']]];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return ['created' => 0, 'errors' => [['row' => 0, 'message' => 'CSV kosong.']]];
        }

        $columns = $this->mapHeader($header);
        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($columns));
        if ($missing !== []) {
            fclose($handle);

            return [
                'created' => 0,
                'errors' => [['row' => 0, 'message' => 'Kolom wajib kurang: '.implode(', ', $missing).'. Unduh contoh CSV.']],
            ];
        }

        $created = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isBlankRow($row)) {
                continue;
            }

            $data = $this->rowAssoc($columns, $row);
            $error = $this->createFromRow($data);

            if ($error === null) {
                $created++;
            } else {
                $errors[] = ['row' => $rowNumber, 'message' => $error];
            }
        }

        fclose($handle);

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function mapHeader(array $header): array
    {
        $map = [];

        foreach ($header as $index => $label) {
            $key = Str::of((string) $label)
                ->trim()
                ->lower()
                ->replace([' ', '-'], '_')
                ->toString();

            $aliases = [
                'title' => 'judul',
                'type' => 'jenis',
                'departure_city' => 'embarkasi',
                'kota' => 'embarkasi',
                'departure_date' => 'tanggal',
                'date' => 'tanggal',
                'departure_date_end' => 'tanggal_selesai',
                'end_date' => 'tanggal_selesai',
                'departure_date_display' => 'tampilan_tanggal',
                'show_seats' => 'tampilkan_seat',
                'duration_days' => 'durasi',
                'hari' => 'durasi',
                'quad' => 'harga_quad',
                'triple' => 'harga_triple',
                'double' => 'harga_double',
                'package_kind' => 'tipe_paket',
                'kind' => 'tipe_paket',
                'seats_total' => 'seat_total',
                'seats_left' => 'seat_sisa',
                'original_price' => 'harga_coret',
                'price_note' => 'catatan_harga',
                'price_label' => 'teks_harga',
                'label_harga' => 'teks_harga',
                'hotel_makkah' => 'hotel_makkah',
                'hotel_madinah' => 'hotel_madinah',
                'facilities' => 'fasilitas',
                'exclusions' => 'exclude',
                'tidak_terminc' => 'exclude',
                'description' => 'deskripsi',
                'is_featured' => 'unggulan',
                'is_hot' => 'kuota_terbatas',
            ];

            $key = $aliases[$key] ?? $key;
            $map[$key] = $index;
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $columns
     * @param  list<string|null>  $row
     * @return array<string, string>
     */
    private function rowAssoc(array $columns, array $row): array
    {
        $data = [];

        foreach ($columns as $key => $index) {
            $data[$key] = trim((string) ($row[$index] ?? ''));
        }

        return $data;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn (?string $cell) => trim((string) $cell) === '');
    }

    /**
     * @param  array<string, string>  $data
     */
    private function createFromRow(array $data): ?string
    {
        $title = trim($data['judul'] ?? '');
        if ($title === '') {
            return 'Judul kosong.';
        }

        if (mb_strlen($title) > 180) {
            return 'Judul terlalu panjang (max 180 karakter).';
        }

        $type = trim($data['jenis'] ?? '');
        if ($type === '' || ! array_key_exists($type, Package::TYPES)) {
            return 'Jenis tidak valid (umroh, umroh_plus, haji_plus).';
        }

        $kind = PackageKind::findActiveByLabel($data['tipe_paket'] ?? '');
        if ($kind === null) {
            return 'Tipe paket tidak valid (Arafah, Mina, Muzdalifah).';
        }

        $city = trim($data['embarkasi'] ?? '');
        if ($city === '' || ! City::query()->where('slug', $city)->whereNull('deleted_at')->exists()) {
            return 'Embarkasi tidak valid. Pakai slug kota, mis. jakarta.';
        }

        $dateRaw = trim($data['tanggal'] ?? '');
        if ($dateRaw === '') {
            return 'Tanggal kosong.';
        }

        try {
            $departureDate = Carbon::parse($dateRaw)->toDateString();
        } catch (\Throwable) {
            return 'Format tanggal tidak valid.';
        }

        $dateDisplay = strtolower(trim($data['tampilan_tanggal'] ?? 'single')) ?: 'single';
        if (! array_key_exists($dateDisplay, Package::DEPARTURE_DATE_DISPLAYS)) {
            return 'Tampilan tanggal tidak valid (single, range, hidden).';
        }

        $departureDateEnd = null;
        if ($dateDisplay === 'range') {
            $dateEndRaw = trim($data['tanggal_selesai'] ?? '');
            if ($dateEndRaw === '') {
                return 'Tanggal selesai wajib untuk tampilan range.';
            }

            try {
                $departureDateEnd = Carbon::parse($dateEndRaw)->toDateString();
            } catch (\Throwable) {
                return 'Format tanggal selesai tidak valid.';
            }

            if ($departureDateEnd < $departureDate) {
                return 'Tanggal selesai tidak boleh sebelum tanggal berangkat.';
            }
        }

        $duration = $this->parseRequiredInt($data['durasi'] ?? '', 7, 45);
        if ($duration === null) {
            return 'Durasi harus angka 7–45.';
        }

        $priceQuad = $this->parseMoney($data['harga_quad'] ?? '');
        $priceTriple = $this->parseMoney($data['harga_triple'] ?? '');
        $priceDouble = $this->parseMoney($data['harga_double'] ?? '');

        $seatsTotal = $this->parseRequiredInt($data['seat_total'] ?? '', 1, 999);
        $seatsLeft = $this->parseRequiredInt($data['seat_sisa'] ?? '', 0, 999);

        if ($seatsTotal === null || $seatsLeft === null || $seatsLeft > $seatsTotal) {
            return 'Seat total/sisa tidak valid.';
        }

        $slug = Package::uniqueSlug($title);
        if (Package::withTrashed()->where('slug', $slug)->exists()) {
            return 'Judul/slug sudah dipakai paket lain.';
        }

        $status = strtolower(trim($data['status'] ?? 'draft'));
        if ($status === '') {
            $status = 'draft';
        }
        if (! array_key_exists($status, Package::STATUSES)) {
            return 'Status tidak valid (draft, published, full).';
        }
        if ($status === 'published') {
            return 'Status published tidak boleh dari import — upload flyer dulu, ubah manual ke Tayang.';
        }

        $originalPrice = $this->parseOptionalMoney($data['harga_coret'] ?? '');
        $isHot = $this->parseBool($data['kuota_terbatas'] ?? '');

        Package::query()->create([
            'title' => $title,
            'slug' => $slug,
            'type' => $type,
            'package_kind_id' => $kind->id,
            'departure_city' => $city,
            'departure_date' => $departureDate,
            'departure_date_end' => $departureDateEnd,
            'departure_date_display' => $dateDisplay,
            'duration_days' => $duration,
            'price' => $priceQuad ?? $priceTriple ?? $priceDouble ?? 0,
            'price_quad' => $priceQuad,
            'price_triple' => $priceTriple,
            'price_double' => $priceDouble,
            'original_price' => $originalPrice,
            'price_note' => $this->optionalString($data['catatan_harga'] ?? '', 180),
            'price_label' => $this->optionalString($data['teks_harga'] ?? '', 40),
            'hotel_makkah' => $this->optionalString($data['hotel_makkah'] ?? '', 120),
            'hotel_makkah_setaraf' => $this->parseBool($data['hotel_makkah_setaraf'] ?? ''),
            'hotel_madinah' => $this->optionalString($data['hotel_madinah'] ?? '', 120),
            'hotel_madinah_setaraf' => $this->parseBool($data['hotel_madinah_setaraf'] ?? ''),
            'airline' => $this->optionalString($data['maskapai'] ?? '', 80),
            'room_type' => $priceQuad !== null ? 'quad' : ($priceTriple !== null ? 'triple' : ($priceDouble !== null ? 'double' : 'quad')),
            'seats_total' => $seatsTotal,
            'seats_left' => $seatsLeft,
            'show_seats' => trim($data['tampilkan_seat'] ?? '') === ''
                ? true
                : $this->parseBool($data['tampilkan_seat']),
            'facilities' => $this->parseList($data['fasilitas'] ?? ''),
            'exclusions' => $this->parseList($data['exclude'] ?? ''),
            'itinerary' => $this->nullableString($data['itinerary'] ?? ''),
            'description' => $this->nullableString($data['deskripsi'] ?? ''),
            'images' => [],
            'is_featured' => $this->parseBool($data['unggulan'] ?? ''),
            'is_hot' => $isHot,
            'card_badge_preset' => $isHot ? 'kuota_terbatas' : null,
            'card_badge_icon' => $isHot ? 'hourglass' : null,
            'card_badge_text' => $isHot ? 'Kuota Terbatas' : null,
            'card_badge_position' => $isHot ? 'top_left' : null,
            'status' => $status,
        ]);

        return null;
    }

    private function parseMoney(string $value): ?int
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '' || (int) $digits < 1) {
            return null;
        }

        return (int) $digits;
    }

    private function parseOptionalMoney(string $value): ?int
    {
        if (trim($value) === '') {
            return null;
        }

        return $this->parseMoney($value);
    }

    private function parseRequiredInt(string $value, int $min, int $max): ?int
    {
        if (trim($value) === '' || ! ctype_digit(trim($value))) {
            return null;
        }

        $number = (int) trim($value);

        if ($number < $min || $number > $max) {
            return null;
        }

        return $number;
    }

    /**
     * @return list<string>
     */
    private function parseList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return collect(preg_split('/\||\r\n|\r|\n|;/', $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function parseBool(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'ya', 'yes'], true);
    }

    private function optionalString(string $value, int $max): ?string
    {
        $value = trim($value);

        return $value === '' ? null : Str::limit($value, $max, '');
    }

    private function nullableString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public static function templateCsv(): string
    {
        $header = implode(',', [
            'judul',
            'jenis',
            'tipe_paket',
            'embarkasi',
            'tanggal',
            'tanggal_selesai',
            'tampilan_tanggal',
            'durasi',
            'harga_quad',
            'harga_triple',
            'harga_double',
            'maskapai',
            'hotel_makkah',
            'hotel_madinah',
            'hotel_makkah_setaraf',
            'hotel_madinah_setaraf',
            'seat_total',
            'seat_sisa',
            'tampilkan_seat',
            'catatan_harga',
            'fasilitas',
            'exclude',
            'deskripsi',
            'status',
            'unggulan',
            'kuota_terbatas',
        ]);

        $sample = implode(',', [
            'Paket Muzdalifah 9D - 30 Jan 2025',
            'umroh',
            'Muzdalifah',
            'jakarta',
            '2025-01-30',
            '2025-02-05',
            'range',
            '9',
            '35100000',
            '36200000',
            '38500000',
            'Garuda Indonesia',
            'Hilton',
            'Hilton',
            '1',
            '1',
            '40',
            '40',
            '1',
            'Harga dapat berubah sesuai kebijakan',
            'Tiket pesawat PP|Perlengkapan & handling|Hotel + makan 3x|Bus & snack|Zamzam 5L|Kereta cepat MED-MEK',
            'Paspor|Vaksin|Pengeluaran pribadi|Tiket add-on PP',
            '',
            'draft',
            '0',
            '0',
        ]);

        return $header."\n".$sample."\n";
    }
}
