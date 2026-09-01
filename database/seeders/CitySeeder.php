<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * 93 kota otonom + Jakarta (embarkasi) + 5 kota administrasi DKI.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function catalog(): array
    {
        return [
            ['jakarta', 'Jakarta'],
            ['jakarta-pusat', 'Jakarta Pusat'],
            ['jakarta-utara', 'Jakarta Utara'],
            ['jakarta-barat', 'Jakarta Barat'],
            ['jakarta-selatan', 'Jakarta Selatan'],
            ['jakarta-timur', 'Jakarta Timur'],
            ['ambon', 'Ambon'],
            ['balikpapan', 'Balikpapan'],
            ['banda-aceh', 'Banda Aceh'],
            ['bandar-lampung', 'Bandar Lampung'],
            ['bandung', 'Bandung'],
            ['banjar', 'Banjar'],
            ['banjarbaru', 'Banjarbaru'],
            ['banjarmasin', 'Banjarmasin'],
            ['batam', 'Batam'],
            ['batu', 'Batu'],
            ['baubau', 'Baubau'],
            ['bekasi', 'Bekasi'],
            ['bengkulu', 'Bengkulu'],
            ['bima', 'Bima'],
            ['binjai', 'Binjai'],
            ['bitung', 'Bitung'],
            ['blitar', 'Blitar'],
            ['bogor', 'Bogor'],
            ['bontang', 'Bontang'],
            ['bukittinggi', 'Bukittinggi'],
            ['cilegon', 'Cilegon'],
            ['cimahi', 'Cimahi'],
            ['cirebon', 'Cirebon'],
            ['denpasar', 'Denpasar'],
            ['depok', 'Depok'],
            ['dumai', 'Dumai'],
            ['gorontalo', 'Gorontalo'],
            ['gunungsitoli', 'Gunungsitoli'],
            ['jambi', 'Jambi'],
            ['jayapura', 'Jayapura'],
            ['kediri', 'Kediri'],
            ['kendari', 'Kendari'],
            ['kotamobagu', 'Kotamobagu'],
            ['kupang', 'Kupang'],
            ['langsa', 'Langsa'],
            ['lhokseumawe', 'Lhokseumawe'],
            ['lubuklinggau', 'Lubuklinggau'],
            ['madiun', 'Madiun'],
            ['magelang', 'Magelang'],
            ['makassar', 'Makassar'],
            ['malang', 'Malang'],
            ['manado', 'Manado'],
            ['mataram', 'Mataram'],
            ['medan', 'Medan'],
            ['metro', 'Metro'],
            ['mojokerto', 'Mojokerto'],
            ['padang', 'Padang'],
            ['padang-panjang', 'Padang Panjang'],
            ['padangsidimpuan', 'Padangsidimpuan'],
            ['pagar-alam', 'Pagar Alam'],
            ['palangka-raya', 'Palangka Raya'],
            ['palembang', 'Palembang'],
            ['palopo', 'Palopo'],
            ['palu', 'Palu'],
            ['pangkal-pinang', 'Pangkalpinang'],
            ['pariaman', 'Pariaman'],
            ['parepare', 'Parepare'],
            ['pasuruan', 'Pasuruan'],
            ['payakumbuh', 'Payakumbuh'],
            ['pekanbaru', 'Pekanbaru'],
            ['pekalongan', 'Pekalongan'],
            ['pematangsiantar', 'Pematangsiantar'],
            ['pontianak', 'Pontianak'],
            ['prabumulih', 'Prabumulih'],
            ['probolinggo', 'Probolinggo'],
            ['sabang', 'Sabang'],
            ['salatiga', 'Salatiga'],
            ['samarinda', 'Samarinda'],
            ['sawahlunto', 'Sawahlunto'],
            ['semarang', 'Semarang'],
            ['serang', 'Serang'],
            ['sibolga', 'Sibolga'],
            ['singkawang', 'Singkawang'],
            ['solok', 'Solok'],
            ['sorong', 'Sorong'],
            ['subulussalam', 'Subulussalam'],
            ['sukabumi', 'Sukabumi'],
            ['sungai-penuh', 'Sungai Penuh'],
            ['surabaya', 'Surabaya'],
            ['surakarta', 'Surakarta'],
            ['tangerang', 'Tangerang'],
            ['tangerang-selatan', 'Tangerang Selatan'],
            ['tanjung-pinang', 'Tanjung Pinang'],
            ['tanjungbalai', 'Tanjungbalai'],
            ['tarakan', 'Tarakan'],
            ['tasikmalaya', 'Tasikmalaya'],
            ['tebing-tinggi', 'Tebing Tinggi'],
            ['tegal', 'Tegal'],
            ['ternate', 'Ternate'],
            ['tidore-kepulauan', 'Tidore Kepulauan'],
            ['tomohon', 'Tomohon'],
            ['tual', 'Tual'],
            ['yogyakarta', 'Yogyakarta'],
        ];
    }

    public function run(): void
    {
        $now = now();

        foreach (self::catalog() as $index => [$slug, $name]) {
            DB::table('cities')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
