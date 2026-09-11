<?php

namespace App\Services\Arminareka;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ArminarekaJadwalFetcher
{
    public function __construct(
        private ?ArminarekaJadwalParser $parser = null,
    ) {
        $this->parser ??= new ArminarekaJadwalParser;
    }

    /**
     * @return list<array{
     *     external_id: string,
     *     row_number: int,
     *     periode: string,
     *     departure_date_label: string,
     *     price_quad: int|null,
     *     price_triple: int|null,
     *     price_double: int|null,
     *     quota: int,
     *     seats_left: int
     * }>
     */
    public function fetchAll(?int $maxRows = null): array
    {
        $maxRows ??= (int) config('arminareka.jadwal.max_rows', 100);
        $pageSize = (int) config('arminareka.jadwal.page_size', 15);
        $maxPages = (int) config('arminareka.jadwal.max_pages', 10);

        $all = [];
        $seen = [];
        $prevFirstKey = null;
        $page = 1;

        while ($page <= $maxPages && count($all) < $maxRows) {
            $rows = $this->fetchPage($page);

            if ($rows === []) {
                break;
            }

            $firstKey = $this->rowKey($rows[0]);

            if ($firstKey === $prevFirstKey) {
                break;
            }

            foreach ($rows as $row) {
                $key = $this->rowKey($row);
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $all[] = $row;

                if (count($all) >= $maxRows) {
                    break 2;
                }
            }

            if (count($rows) < $pageSize) {
                break;
            }

            $prevFirstKey = $firstKey;
            $page++;

            $delayMs = (int) config('arminareka.jadwal.request_delay_ms', 350);
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        return $all;
    }

    /**
     * @return list<array{
     *     external_id: string,
     *     row_number: int,
     *     periode: string,
     *     departure_date_label: string,
     *     price_quad: int|null,
     *     price_triple: int|null,
     *     price_double: int|null,
     *     quota: int,
     *     seats_left: int
     * }>
     */
    public function fetchPage(int $page = 1): array
    {
        $response = Http::timeout((int) config('arminareka.jadwal.timeout_seconds', 20))
            ->accept('text/html')
            ->get((string) config('arminareka.jadwal.base_url'), [
                'PeriodeSearch' => [
                    'Periode' => '',
                    'TglPergi' => '',
                    'Harga4' => '',
                    'Harga3' => '',
                    'Harga2' => '',
                    'Quota' => '',
                ],
                'page' => max(1, $page),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengambil jadwal Arminareka (HTTP '.$response->status().').');
        }

        return $this->parser->parseHtml($response->body());
    }

    /**
     * @param  array{external_id?: string, periode?: string, departure_date_label?: string}  $row
     */
    private function rowKey(array $row): string
    {
        $externalId = trim((string) ($row['external_id'] ?? ''));
        if ($externalId !== '') {
            return 'id:'.$externalId;
        }

        return trim((string) ($row['periode'] ?? '')).'|'.trim((string) ($row['departure_date_label'] ?? ''));
    }
}
