<?php

namespace App\Services\Arminareka;

use App\Models\Package;
use App\Models\PackageKind;
use App\Models\PriceSyncChange;
use App\Models\PriceSyncRun;
use App\Support\PriceSyncSchedule;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ArminarekaPriceSyncService
{
    public function __construct(
        private ?ArminarekaJadwalFetcher $fetcher = null,
        private ?ArminarekaJadwalNormalizer $normalizer = null,
        private ?ArminarekaJadwalComparer $comparer = null,
    ) {
        $this->fetcher ??= new ArminarekaJadwalFetcher;
        $this->normalizer ??= new ArminarekaJadwalNormalizer;
        $this->comparer ??= new ArminarekaJadwalComparer;
    }

    public function runManual(?int $userId = null): PriceSyncRun
    {
        $run = PriceSyncRun::query()->create([
            'trigger' => PriceSyncRun::TRIGGER_MANUAL,
            'user_id' => $userId,
            'status' => PriceSyncRun::STATUS_WAITING,
            'started_at' => now(),
        ]);

        try {
            $rawRows = $this->fetcher->fetchAll();
            $incomingRows = $this->normalizer->normalizeMany($rawRows);
            $changes = $this->comparer->compare($incomingRows);

            foreach ($changes as $change) {
                $run->changes()->create($change);
            }

            $run->fill([
                'total_found' => count($incomingRows),
                'total_new' => $this->countStatus($changes, PriceSyncChange::STATUS_NEW),
                'total_changed' => $this->countStatus($changes, PriceSyncChange::STATUS_CHANGED),
                'total_unchanged' => $this->countStatus($changes, PriceSyncChange::STATUS_UNCHANGED),
                'total_removed' => $this->countStatus($changes, PriceSyncChange::STATUS_REMOVED),
                'ended_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $run->update([
                'status' => PriceSyncRun::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'ended_at' => now(),
            ]);

            throw $exception;
        }

        return $run->fresh(['changes']);
    }

    public function runScheduled(): PriceSyncRun
    {
        $run = PriceSyncRun::query()->create([
            'trigger' => PriceSyncRun::TRIGGER_SCHEDULED,
            'user_id' => null,
            'status' => PriceSyncRun::STATUS_WAITING,
            'started_at' => now(),
        ]);

        try {
            $rawRows = $this->fetcher->fetchAll();
            $incomingRows = $this->normalizer->normalizeMany($rawRows);
            $changes = $this->comparer->compare($incomingRows);

            foreach ($changes as $change) {
                $run->changes()->create($change);
            }

            $run->fill([
                'total_found' => count($incomingRows),
                'total_new' => $this->countStatus($changes, PriceSyncChange::STATUS_NEW),
                'total_changed' => $this->countStatus($changes, PriceSyncChange::STATUS_CHANGED),
                'total_unchanged' => $this->countStatus($changes, PriceSyncChange::STATUS_UNCHANGED),
                'total_removed' => $this->countStatus($changes, PriceSyncChange::STATUS_REMOVED),
                'ended_at' => now(),
            ])->save();

            if (PriceSyncSchedule::isAutoUpdate()) {
                $this->applyAllApplicable($run);
            }
        } catch (Throwable $exception) {
            $run->update([
                'status' => PriceSyncRun::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'ended_at' => now(),
            ]);

            throw $exception;
        }

        return $run->fresh(['changes']);
    }

    public function applyAllApplicable(PriceSyncRun $run): int
    {
        $changeIds = $run->changes()
            ->whereNull('applied_at')
            ->whereIn('change_status', [
                PriceSyncChange::STATUS_NEW,
                PriceSyncChange::STATUS_NEW_DEPARTURE,
                PriceSyncChange::STATUS_CHANGED,
                PriceSyncChange::STATUS_REMOVED,
            ])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($changeIds === []) {
            $run->update(['status' => PriceSyncRun::STATUS_APPLIED]);

            return 0;
        }

        return $this->applySelected($run, $changeIds);
    }

    /**
     * @param  list<int>  $changeIds
     */
    public function applySelected(PriceSyncRun $run, array $changeIds): int
    {
        if (! $run->isWaiting()) {
            throw new InvalidArgumentException('Sync run sudah tidak menunggu approval.');
        }

        $changes = $run->changes()
            ->whereIn('id', $changeIds)
            ->whereNull('applied_at')
            ->get();

        if ($changes->isEmpty()) {
            throw new InvalidArgumentException('Tidak ada perubahan valid yang dipilih.');
        }

        $applied = 0;

        DB::transaction(function () use ($changes, &$applied) {
            foreach ($changes as $change) {
                if (! $change->isApplicable()) {
                    continue;
                }

                $this->applyChange($change);
                $change->update(['applied_at' => now()]);
                $applied++;
            }
        });

        $pending = $run->changes()->whereNull('applied_at')->whereIn('change_status', [
            PriceSyncChange::STATUS_NEW,
            PriceSyncChange::STATUS_NEW_DEPARTURE,
            PriceSyncChange::STATUS_CHANGED,
            PriceSyncChange::STATUS_REMOVED,
        ])->count();

        $run->update([
            'total_applied' => $run->total_applied + $applied,
            'status' => $pending > 0 ? PriceSyncRun::STATUS_PARTIAL : PriceSyncRun::STATUS_APPLIED,
            'ended_at' => now(),
        ]);

        return $applied;
    }

    private function applyChange(PriceSyncChange $change): void
    {
        match ($change->change_status) {
            PriceSyncChange::STATUS_NEW => $this->applyNew($change),
            PriceSyncChange::STATUS_NEW_DEPARTURE => $this->applyNewDeparture($change),
            PriceSyncChange::STATUS_CHANGED => $this->applyChanged($change),
            PriceSyncChange::STATUS_REMOVED => $this->applyRemoved($change),
            default => throw new InvalidArgumentException('Perubahan tidak bisa diterapkan.'),
        };
    }

    private function applyNew(PriceSyncChange $change): void
    {
        if (filled($change->source_key)) {
            $existing = Package::withTrashed()
                ->where('source_key', $change->source_key)
                ->first();

            if ($existing !== null) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $this->applyIncomingToPackage($existing, $change);

                return;
            }
        }

        $incoming = $change->incoming_snapshot ?? [];
        $normalized = array_merge($incoming, [
            'external_id' => $change->external_id,
            'source_key' => $change->source_key,
            'seats_total' => $incoming['seats_total'] ?? 0,
            'seats_left' => $incoming['seats_left'] ?? 0,
            'warnings' => [],
            'raw' => [],
        ]);

        $attributes = $this->normalizer->toPackageAttributes($normalized);
        $attributes['source_key'] = $change->source_key;
        $attributes['slug'] = Package::uniqueSlug($attributes['title']);
        $attributes['status'] = 'draft';
        $attributes['images'] = [];
        $attributes = PackageKind::mergeMissingTemplateForKind($attributes['package_kind_id'] ?? null, $attributes);

        $package = Package::query()->create($attributes);
        $this->linkChangeToPackage($change, $package);
    }

    private function applyNewDeparture(PriceSyncChange $change): void
    {
        $incoming = $change->incoming_snapshot ?? [];
        $cloneFromId = (int) ($incoming['clone_from_package_id'] ?? 0);
        $source = $cloneFromId > 0
            ? Package::query()->find($cloneFromId)
            : null;

        if ($source === null) {
            $this->applyNew($change);

            return;
        }

        $copy = $source->replicate(['slug', 'source_key']);
        $copy->title = (string) ($incoming['title'] ?? $source->title);
        $copy->departure_date = $incoming['departure_date'] ?? null;
        $copy->departure_date_end = null;
        $copy->departure_date_display = 'single';
        $copy->type = $incoming['type'] ?? $source->type;
        $copy->package_kind_id = $incoming['package_kind_id'] ?? $source->package_kind_id;
        $copy->departure_city = $this->valueFromIncoming($incoming, 'departure_city', $source->departure_city);
        $copy->arrival_city = $this->valueFromIncoming($incoming, 'arrival_city', $source->arrival_city);
        $copy->duration_days = $this->valueFromIncoming($incoming, 'duration_days', $source->duration_days);
        $copy->airline = $this->valueFromIncoming($incoming, 'airline', $source->airline);
        $copy->price_quad = $incoming['price_quad'] ?? null;
        $copy->price_triple = $incoming['price_triple'] ?? null;
        $copy->price_double = $incoming['price_double'] ?? null;
        $copy->seats_total = $incoming['seats_total'] ?? $source->seats_total;
        $copy->seats_left = $incoming['seats_left'] ?? $source->seats_left;
        $copy->source_key = $change->source_key;
        $copy->slug = Package::uniqueSlug($copy->title.' '.$copy->departure_date?->format('Y-m-d'));
        $copy->status = 'draft';
        $copy->is_featured = false;
        $copy->home_sort = null;
        $copy->fill(PackageKind::mergeMissingTemplateForKind($copy->package_kind_id, [
            'description' => $copy->description,
            'hotel_makkah' => $copy->hotel_makkah,
            'hotel_makkah_setaraf' => $copy->hotel_makkah_setaraf,
            'hotel_madinah' => $copy->hotel_madinah,
            'hotel_madinah_setaraf' => $copy->hotel_madinah_setaraf,
            'facilities' => $copy->facilities ?? [],
            'exclusions' => $copy->exclusions ?? [],
            'cover_image' => $copy->cover_image,
        ]));
        if (filled($change->source_key)) {
            $conflict = Package::withTrashed()
                ->where('source_key', $change->source_key)
                ->whereKeyNot($copy->id ?? 0)
                ->first();

            if ($conflict !== null) {
                if ($conflict->trashed()) {
                    $conflict->restore();
                }

                $this->applyIncomingToPackage($conflict, $change);

                return;
            }
        }

        $copy->syncPrimaryPrice();
        $copy->save();
        $this->linkChangeToPackage($change, $copy);
    }

    private function applyChanged(PriceSyncChange $change): void
    {
        $this->applyIncomingToPackage($this->resolvePackage($change), $change);
    }

    private function applyIncomingToPackage(Package $package, PriceSyncChange $change): void
    {
        $incoming = $change->incoming_snapshot ?? [];

        $package->fill([
            'title' => $this->valueFromIncoming($incoming, 'title', $package->title),
            'type' => $this->valueFromIncoming($incoming, 'type', $package->type),
            'package_kind_id' => $this->valueFromIncoming($incoming, 'package_kind_id', $package->package_kind_id),
            'departure_city' => $this->valueFromIncoming($incoming, 'departure_city', $package->departure_city),
            'arrival_city' => $this->valueFromIncoming($incoming, 'arrival_city', $package->arrival_city),
            'departure_date' => $this->valueFromIncoming($incoming, 'departure_date', $package->departure_date),
            'duration_days' => $this->valueFromIncoming($incoming, 'duration_days', $package->duration_days),
            'airline' => $this->valueFromIncoming($incoming, 'airline', $package->airline),
            'price_quad' => $this->valueFromIncoming($incoming, 'price_quad', null),
            'price_triple' => $this->valueFromIncoming($incoming, 'price_triple', null),
            'price_double' => $this->valueFromIncoming($incoming, 'price_double', null),
            'seats_total' => $this->valueFromIncoming($incoming, 'seats_total', $package->seats_total),
            'seats_left' => $this->valueFromIncoming($incoming, 'seats_left', $package->seats_left),
            'source_key' => $change->source_key ?? $package->source_key,
        ]);
        $package->syncPrimaryPrice();
        $package->save();
        $this->linkChangeToPackage($change, $package);
    }

    private function linkChangeToPackage(PriceSyncChange $change, Package $package): void
    {
        if ((int) $change->package_id !== (int) $package->id) {
            $change->update(['package_id' => $package->id]);
        }
    }

    /**
     * @param  array<string, mixed>  $incoming
     */
    private function valueFromIncoming(array $incoming, string $field, mixed $fallback): mixed
    {
        return array_key_exists($field, $incoming) ? $incoming[$field] : $fallback;
    }

    private function applyRemoved(PriceSyncChange $change): void
    {
        $package = $this->resolvePackage($change);

        if ($package->status !== 'hide') {
            $package->update(['status' => 'hide']);
        }

        $this->linkChangeToPackage($change, $package);
    }

    private function resolvePackage(PriceSyncChange $change): Package
    {
        $package = $change->package;

        if ($package === null && $change->package_id !== null) {
            $package = Package::query()->find($change->package_id);
        }

        if ($package === null) {
            throw new RuntimeException('Paket untuk perubahan #'.$change->id.' tidak ditemukan.');
        }

        return $package;
    }

    /**
     * @param  list<array{change_status: string}>  $changes
     */
    private function countStatus(array $changes, string $status): int
    {
        return count(array_filter($changes, fn (array $change) => $change['change_status'] === $status));
    }
}
