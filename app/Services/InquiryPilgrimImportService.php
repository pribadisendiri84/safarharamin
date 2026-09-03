<?php

namespace App\Services;

use App\Models\Departure;
use App\Models\Inquiry;
use App\Models\Pilgrim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InquiryPilgrimImportService
{
    /**
     * @param  list<string>  $names
     * @return Collection<int, Pilgrim>
     */
    public function import(Inquiry $inquiry, Departure $departure, string $roomType, array $names): Collection
    {
        if (! $inquiry->isSold()) {
            throw new InvalidArgumentException('Hanya pengajuan closing yang bisa dipindah ke jamaah.');
        }

        if ($inquiry->pilgrimsImported()) {
            throw new InvalidArgumentException('Pengajuan ini sudah dipindah ke jamaah.');
        }

        $pax = $inquiry->soldPaxCount();
        $names = array_values(array_filter(array_map(
            fn ($name) => trim((string) $name),
            array_slice($names, 0, $pax)
        ), fn ($name) => $name !== ''));

        if (count($names) !== $pax) {
            throw new InvalidArgumentException('Jumlah nama jamaah harus sama dengan jamaah closing.');
        }

        $unitPrice = (int) round((int) $inquiry->sold_amount / max(1, $pax));
        $note = $this->buildNotes($inquiry);

        return DB::transaction(function () use ($inquiry, $departure, $roomType, $names, $unitPrice, $note) {
            $pilgrims = collect();

            foreach ($names as $index => $name) {
                $pilgrims->push(Pilgrim::query()->create([
                    'inquiry_id' => $inquiry->id,
                    'departure_id' => $departure->id,
                    'full_name' => $name,
                    'phone' => $index === 0 ? $inquiry->phone : null,
                    'room_type' => $roomType,
                    'package_price' => $unitPrice,
                    'paid_amount' => 0,
                    'notes' => $index === 0 ? $note : null,
                ]));
            }

            $inquiry->update([
                'pilgrims_imported_at' => now(),
                'status' => 'selesai',
            ]);

            return $pilgrims;
        });
    }

    private function buildNotes(Inquiry $inquiry): ?string
    {
        $parts = array_filter([
            $inquiry->notes,
            'Import dari pengajuan #'.$inquiry->id,
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }
}
