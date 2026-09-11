<?php

namespace App\Services\Arminareka;

use DOMDocument;
use DOMElement;
use DOMXPath;

class ArminarekaJadwalParser
{
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
    public function parseHtml(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html);
        libxml_clear_errors();

        if ($loaded === false) {
            return [];
        }

        $xpath = new DOMXPath($document);
        /** @var \DOMNodeList<DOMElement> $rows */
        $rows = $xpath->query('//tr[@data-key]');

        if ($rows === false || $rows->length === 0) {
            return [];
        }

        $parsed = [];

        foreach ($rows as $row) {
            $cells = [];
            foreach ($row->getElementsByTagName('td') as $cell) {
                $cells[] = trim(html_entity_decode($cell->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            if (count($cells) < 8) {
                continue;
            }

            $parsed[] = [
                'external_id' => (string) $row->getAttribute('data-key'),
                'row_number' => (int) preg_replace('/\D+/', '', $cells[0]),
                'periode' => $cells[1],
                'departure_date_label' => $cells[2],
                'price_quad' => $this->parseMoney($cells[3]),
                'price_triple' => $this->parseMoney($cells[4]),
                'price_double' => $this->parseMoney($cells[5]),
                'quota' => (int) preg_replace('/\D+/', '', $cells[6]),
                'seats_left' => (int) (preg_replace('/[^\d-]/', '', $cells[7]) ?? '0'),
            ];
        }

        return $parsed;
    }

    private function parseMoney(string $value): ?int
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '' || (int) $digits < 1) {
            return null;
        }

        return (int) $digits;
    }
}
