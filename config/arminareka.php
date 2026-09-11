<?php

return [

    'jadwal' => [
        'base_url' => env('ARMINAREKA_JADWAL_URL', 'https://jamaah.arminarekaperdana.com/site/jadwal'),
        'page_size' => (int) env('ARMINAREKA_JADWAL_PAGE_SIZE', 15),
        'max_rows' => (int) env('ARMINAREKA_JADWAL_MAX_ROWS', 100),
        'max_pages' => (int) env('ARMINAREKA_JADWAL_MAX_PAGES', 10),
        'request_delay_ms' => (int) env('ARMINAREKA_JADWAL_DELAY_MS', 350),
        'timeout_seconds' => (int) env('ARMINAREKA_JADWAL_TIMEOUT', 20),
    ],

    /*
    | Map kode bandara embarkasi (Indonesia) ke slug kota master safarharamin.
    */
    'origin_airports' => [
        'CGK' => 'jakarta',
        'PDG' => 'padang',
        'SUB' => 'surabaya',
        'PKU' => 'pekanbaru',
        'PLM' => 'palembang',
        'KNO' => 'medan',
        'UPG' => 'makassar',
        'BPN' => 'balikpapan',
        'YIA' => 'yogyakarta',
    ],

    /** @deprecated Use origin_airports */
    'airport_cities' => [
        'CGK' => 'jakarta',
        'PDG' => 'padang',
        'SUB' => 'surabaya',
        'PKU' => 'pekanbaru',
        'PLM' => 'palembang',
        'KNO' => 'medan',
    ],

    /*
    | Map kode bandara tujuan (Arab Saudi) ke slug tujuan keberangkatan.
    */
    'destination_airports' => [
        'JED' => 'jeddah',
        'MED' => 'madinah',
    ],

    /*
    | Map kode maskapai singkat di string Periode ke nama master maskapai.
    */
    'airline_codes' => [
        'GA' => 'Garuda Indonesia',
        'JT' => 'Lion Air',
        'SV' => 'Saudia',
        'BATIK' => 'Batik Air',
    ],

];
