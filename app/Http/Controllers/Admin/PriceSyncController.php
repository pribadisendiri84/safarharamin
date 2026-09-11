<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceSyncChange;
use App\Models\PriceSyncRun;
use App\Services\Arminareka\ArminarekaPriceSyncService;
use Illuminate\Http\Request;
use Throwable;

class PriceSyncController extends Controller
{
    public function index()
    {
        $runs = PriceSyncRun::query()
            ->with('user')
            ->latest()
            ->paginate(15);

        $pendingRun = PriceSyncRun::query()
            ->where('status', PriceSyncRun::STATUS_WAITING)
            ->latest()
            ->first();

        return view('admin.price-sync.index', compact('runs', 'pendingRun'));
    }

    public function store(ArminarekaPriceSyncService $sync)
    {
        try {
            $run = $sync->runManual(auth()->id());
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.price-sync.index')
                ->with('err', 'Sync gagal: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.price-sync.show', $run)
            ->with('ok', 'Data jadwal berhasil diambil. Review perubahan sebelum update.');
    }

    public function show(PriceSyncRun $priceSyncRun)
    {
        $priceSyncRun->load(['changes.package.packageKind', 'user']);

        $changes = $priceSyncRun->changes;
        if (request()->boolean('only_changes')) {
            $changes = $changes->whereIn('change_status', [
                PriceSyncChange::STATUS_NEW,
                PriceSyncChange::STATUS_NEW_DEPARTURE,
                PriceSyncChange::STATUS_CHANGED,
                PriceSyncChange::STATUS_REMOVED,
            ]);
        }

        return view('admin.price-sync.show', [
            'run' => $priceSyncRun,
            'changes' => $changes,
        ]);
    }

    public function apply(Request $request, PriceSyncRun $priceSyncRun, ArminarekaPriceSyncService $sync)
    {
        $validated = $request->validate([
            'change_ids' => ['required', 'array', 'min:1'],
            'change_ids.*' => ['integer', 'exists:price_sync_changes,id'],
        ]);

        $changeIds = collect($validated['change_ids'])
            ->map(fn ($id) => (int) $id)
            ->all();

        try {
            $applied = $sync->applySelected($priceSyncRun, $changeIds);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.price-sync.show', $priceSyncRun)
                ->with('err', $exception->getMessage());
        }

        return redirect()
            ->route('admin.price-sync.show', $priceSyncRun)
            ->with('ok', $applied.' perubahan berhasil diterapkan.');
    }

    public function destroy(PriceSyncRun $priceSyncRun)
    {
        $priceSyncRun->delete();

        return redirect()
            ->route('admin.price-sync.index')
            ->with('ok', 'Riwayat sync '.$priceSyncRun->reference().' berhasil dihapus.');
    }
}
