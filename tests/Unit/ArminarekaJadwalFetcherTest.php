<?php

namespace Tests\Unit;

use App\Services\Arminareka\ArminarekaJadwalFetcher;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArminarekaJadwalFetcherTest extends TestCase
{
    #[Test]
    public function test_it_loops_pages_until_short_page_and_deduplicates_overflow(): void
    {
        config([
            'arminareka.jadwal.page_size' => 2,
            'arminareka.jadwal.max_rows' => 100,
            'arminareka.jadwal.max_pages' => 10,
            'arminareka.jadwal.request_delay_ms' => 0,
        ]);

        $pageOne = <<<'HTML'
<table><tbody>
<tr data-key="1"><td>1</td><td>Mina GA CGK 9D JED (30/11/2026)</td><td>30 Nov 2026</td><td>10</td><td>20</td><td>30</td><td>45</td><td>45</td></tr>
<tr data-key="2"><td>2</td><td>Mina GA CGK 9D JED (25/11/2026)</td><td>25 Nov 2026</td><td>11</td><td>21</td><td>31</td><td>45</td><td>45</td></tr>
</tbody></table>
HTML;

        $pageTwo = <<<'HTML'
<table><tbody>
<tr data-key="3"><td>3</td><td>Mina GA CGK 9D JED (20/11/2026)</td><td>20 Nov 2026</td><td>12</td><td>22</td><td>32</td><td>45</td><td>45</td></tr>
</tbody></table>
HTML;

        $duplicatePage = <<<'HTML'
<table><tbody>
<tr data-key="3"><td>3</td><td>Mina GA CGK 9D JED (20/11/2026)</td><td>20 Nov 2026</td><td>12</td><td>22</td><td>32</td><td>45</td><td>45</td></tr>
</tbody></table>
HTML;

        Http::fake([
            '*page=1*' => Http::response($pageOne, 200),
            '*page=2*' => Http::response($pageTwo, 200),
            '*page=3*' => Http::response($duplicatePage, 200),
        ]);

        $rows = (new ArminarekaJadwalFetcher)->fetchAll();

        $this->assertCount(3, $rows);
        $this->assertSame(['1', '2', '3'], array_column($rows, 'external_id'));
    }

    #[Test]
    public function test_it_respects_max_rows_cap(): void
    {
        config([
            'arminareka.jadwal.page_size' => 2,
            'arminareka.jadwal.max_rows' => 2,
            'arminareka.jadwal.max_pages' => 10,
            'arminareka.jadwal.request_delay_ms' => 0,
        ]);

        $page = <<<'HTML'
<table><tbody>
<tr data-key="1"><td>1</td><td>Mina GA CGK 9D JED (30/11/2026)</td><td>30 Nov 2026</td><td>10</td><td>20</td><td>30</td><td>45</td><td>45</td></tr>
<tr data-key="2"><td>2</td><td>Mina GA CGK 9D JED (25/11/2026)</td><td>25 Nov 2026</td><td>11</td><td>21</td><td>31</td><td>45</td><td>45</td></tr>
</tbody></table>
HTML;

        Http::fake(['*' => Http::response($page, 200)]);

        $rows = (new ArminarekaJadwalFetcher)->fetchAll();

        $this->assertCount(2, $rows);
    }
}
