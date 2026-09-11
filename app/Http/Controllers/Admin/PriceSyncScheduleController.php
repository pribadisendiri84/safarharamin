<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PriceSyncSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriceSyncScheduleController extends Controller
{
    public function edit()
    {
        return view('admin.price-sync.schedule', [
            'schedule' => PriceSyncSchedule::config(),
            'summary' => PriceSyncSchedule::summaryLabel(),
            'nextRun' => PriceSyncSchedule::nextRunAt(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'frequency' => ['required', Rule::in([
                PriceSyncSchedule::FREQUENCY_HOURLY,
                PriceSyncSchedule::FREQUENCY_DAILY,
                PriceSyncSchedule::FREQUENCY_WEEKLY,
            ])],
            'time' => ['required', 'date_format:H:i'],
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'between:1,7'],
            'update_mode' => ['required', Rule::in([
                PriceSyncSchedule::MODE_MANUAL,
                PriceSyncSchedule::MODE_AUTO,
            ])],
            'timezone' => ['required', 'timezone'],
        ]);

        PriceSyncSchedule::save([
            'enabled' => $request->boolean('enabled'),
            'frequency' => $data['frequency'],
            'time' => $data['time'],
            'days' => array_values(array_map('intval', $data['days'] ?? [1])),
            'update_mode' => $data['update_mode'],
            'timezone' => $data['timezone'],
        ]);

        return redirect()
            ->route('admin.price-sync.schedule.edit')
            ->with('ok', 'Jadwal sync diperbarui.');
    }
}
