<?php

namespace App\Support;

class CompanyProfile
{
    public const LEGAL_NAME = 'PT Arminareka Perdana';

    public const BRAND_NAME = 'Arminareka';

    public const FOUNDED = '9 Februari 1990';

    public const FOUNDED_YEAR = 1990;

    public const YOUTUBE_VIDEO_ID = 'k7tR0y4PFHY';

    public const YOUTUBE_START_SECONDS = 7;

    public const OFFICIAL_PROFILE_URL = 'https://www.arminarekaperdana.com/profil';

    /**
     * @return list<array{label: string, value: string, note: string|null}>
     */
    public static function licenses(): array
    {
        return [
            [
                'label' => 'Izin PPIU (Umrah)',
                'value' => 'U.511 Tahun 2021',
                'note' => 'Surat Keputusan Direktur Jenderal Penyelenggara Haji dan Umrah',
            ],
            [
                'label' => 'Izin PIHK (Haji Khusus)',
                'value' => '399 Tahun 2021',
                'note' => 'Surat Keputusan Direktur Jenderal Penyelenggara Haji dan Umrah',
            ],
            [
                'label' => 'NPWP',
                'value' => '01.342.510.3-432.000',
                'note' => 'Direktorat Jenderal Pajak',
            ],
            [
                'label' => 'AMPHURI',
                'value' => '075/AMPHURI/200',
                'note' => 'Asosiasi Muslim Penyelenggara Haji Umrah Republik Indonesia',
            ],
        ];
    }

    /**
     * @return list<array{title: string, issuer: string, period: string|null, description: string}>
     */
    public static function awards(): array
    {
        return [
            [
                'title' => 'Rekor MURI — Peserta Muhasabah Terbanyak',
                'issuer' => 'Museum Rekor Dunia Indonesia (MURI)',
                'period' => '2017',
                'description' => 'Penghargaan atas program muhasabah dengan partisipasi jamaah terbanyak.',
            ],
            [
                'title' => 'TOP AGENT — Middle East Routes',
                'issuer' => 'Garuda Indonesia',
                'period' => null,
                'description' => 'Penghargaan kinerja sebagai agen perjalanan ibadah pada rute Timur Tengah.',
            ],
        ];
    }

    /**
     * @return list<array{title: string, description: string, icon: string}>
     */
    public static function commitments(): array
    {
        return [
            [
                'title' => 'Pelayanan jamaah',
                'description' => 'Tim pendamping dan koordinasi perjalanan yang berfokus pada kebutuhan jamaah selama proses ibadah.',
                'icon' => 'bi-people',
            ],
            [
                'title' => 'Transparansi informasi',
                'description' => 'Informasi paket, fasilitas, dan ketentuan disampaikan secara jelas sebelum jamaah memutuskan.',
                'icon' => 'bi-info-circle',
            ],
            [
                'title' => 'Pendampingan perjalanan',
                'description' => 'Manasik, koordinasi lapangan, dan dukungan selama perjalanan sesuai program paket.',
                'icon' => 'bi-compass',
            ],
            [
                'title' => 'Kenyamanan jamaah',
                'description' => 'Perencanaan akomodasi, transportasi, dan itinerary yang mempertimbangkan kenyamanan dan kelancaran ibadah.',
                'icon' => 'bi-heart',
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function experienceFacts(): array
    {
        return [
            ['label' => 'Tahun berdiri', 'value' => self::FOUNDED],
            ['label' => 'Bidang usaha', 'value' => 'Perjalanan ibadah Umrah dan Haji Khusus'],
            ['label' => 'Badan usaha', 'value' => self::LEGAL_NAME],
            ['label' => 'Keanggotaan', 'value' => 'AMPHURI & terdaftar Kementerian Agama'],
            ['label' => 'Jaringan embarkasi', 'value' => 'Beberapa kota besar di Indonesia'],
        ];
    }
}
