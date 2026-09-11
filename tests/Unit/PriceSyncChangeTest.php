<?php

namespace Tests\Unit;

use App\Models\PriceSyncChange;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceSyncChangeTest extends TestCase
{
    #[Test]
    public function test_bedanya_rows_for_changed_status_uses_diff_detail_rows(): void
    {
        $change = new PriceSyncChange([
            'change_status' => PriceSyncChange::STATUS_CHANGED,
            'existing_snapshot' => [
                'price_quad' => 38800000,
                'seats_left' => 45,
            ],
            'incoming_snapshot' => [
                'price_quad' => 39000000,
                'seats_left' => 40,
            ],
            'diff_fields' => ['price_quad', 'seats_left'],
        ]);

        $rows = $change->bedanyaRows();

        $this->assertCount(2, $rows);
        $this->assertSame('diff', $rows[0]['type']);
        $this->assertSame('Harga quad', $rows[0]['label']);
        $this->assertSame('Rp38.800.000', $rows[0]['from']);
        $this->assertSame('Rp39.000.000', $rows[0]['to']);
    }

    #[Test]
    public function test_bedanya_rows_for_new_status_summarizes_incoming_snapshot(): void
    {
        $change = new PriceSyncChange([
            'change_status' => PriceSyncChange::STATUS_NEW,
            'incoming_snapshot' => [
                'periode_full' => 'Mina JT CGK 12D JED (07/12/2026)',
                'title' => 'Mina JT CGK 12D JED',
                'departure_date' => '2026-12-07',
                'price_quad' => 39000000,
                'seats_left' => 45,
                'seats_total' => 45,
            ],
            'diff_fields' => [],
        ]);

        $rows = $change->bedanyaRows();

        $this->assertNotEmpty($rows);
        $this->assertSame('note', $rows[0]['type']);
        $this->assertStringContainsString('Mina JT CGK 12D JED', $rows[0]['text']);
    }

    #[Test]
    public function test_db_labels_use_existing_snapshot(): void
    {
        $change = new PriceSyncChange([
            'existing_snapshot' => [
                'title' => 'Mina GA CGK 9D JED',
                'departure_date' => '2026-11-30',
            ],
        ]);

        $this->assertSame('Mina GA CGK 9D JED', $change->dbPeriodeLabel());
        $this->assertSame('30 Nov 2026', $change->dbDepartureDateLabel());
    }
}
