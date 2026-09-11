<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

class PriceSyncSchedule
{
    public const KEY_ENABLED = 'price_sync_schedule_enabled';

    public const KEY_FREQUENCY = 'price_sync_schedule_frequency';

    public const KEY_TIME = 'price_sync_schedule_time';

    public const KEY_DAYS = 'price_sync_schedule_days';

    public const KEY_UPDATE_MODE = 'price_sync_schedule_update_mode';

    public const KEY_TIMEZONE = 'price_sync_schedule_timezone';

    public const KEY_LAST_RUN_AT = 'price_sync_schedule_last_run_at';

    public const FREQUENCY_HOURLY = 'hourly';

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const MODE_MANUAL = 'manual_approval';

    public const MODE_AUTO = 'auto_update';

    /**
     * @return array{
     *     enabled: bool,
     *     frequency: string,
     *     time: string,
     *     days: list<int>,
     *     update_mode: string,
     *     timezone: string,
     *     last_run_at: string|null
     * }
     */
    public static function config(): array
    {
        $days = json_decode(Setting::getValue(self::KEY_DAYS, '[1]'), true);

        return [
            'enabled' => Setting::getValue(self::KEY_ENABLED, '0') === '1',
            'frequency' => Setting::getValue(self::KEY_FREQUENCY, self::FREQUENCY_DAILY),
            'time' => Setting::getValue(self::KEY_TIME, '02:00'),
            'days' => is_array($days) ? array_values(array_map('intval', $days)) : [1],
            'update_mode' => Setting::getValue(self::KEY_UPDATE_MODE, self::MODE_MANUAL),
            'timezone' => Setting::getValue(self::KEY_TIMEZONE, config('app.timezone', 'Asia/Jakarta')),
            'last_run_at' => Setting::getValue(self::KEY_LAST_RUN_AT) ?: null,
        ];
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     frequency?: string,
     *     time?: string,
     *     days?: list<int>,
     *     update_mode?: string,
     *     timezone?: string
     * }  $input
     */
    public static function save(array $input): void
    {
        Setting::setValue(self::KEY_ENABLED, ! empty($input['enabled']) ? '1' : '0');
        Setting::setValue(self::KEY_FREQUENCY, $input['frequency'] ?? self::FREQUENCY_DAILY);
        Setting::setValue(self::KEY_TIME, $input['time'] ?? '02:00');
        Setting::setValue(self::KEY_DAYS, json_encode(array_values($input['days'] ?? [1])) ?: '[1]');
        Setting::setValue(self::KEY_UPDATE_MODE, $input['update_mode'] ?? self::MODE_MANUAL);
        Setting::setValue(self::KEY_TIMEZONE, $input['timezone'] ?? config('app.timezone', 'Asia/Jakarta'));
    }

    public static function markRan(): void
    {
        Setting::setValue(self::KEY_LAST_RUN_AT, now()->toIso8601String());
    }

    public static function isAutoUpdate(): bool
    {
        return self::config()['update_mode'] === self::MODE_AUTO;
    }

    public static function isDue(?Carbon $now = null): bool
    {
        $config = self::config();

        if (! $config['enabled']) {
            return false;
        }

        $now ??= now()->timezone($config['timezone']);
        $lastRun = filled($config['last_run_at'])
            ? Carbon::parse($config['last_run_at'])->timezone($config['timezone'])
            : null;

        return match ($config['frequency']) {
            self::FREQUENCY_HOURLY => $lastRun === null || $lastRun->lte($now->copy()->subHour()),
            self::FREQUENCY_WEEKLY => self::isWeeklyDue($config, $now, $lastRun),
            default => self::isDailyDue($config, $now, $lastRun),
        };
    }

    public static function nextRunAt(): ?Carbon
    {
        $config = self::config();

        if (! $config['enabled']) {
            return null;
        }

        $timezone = $config['timezone'];
        $now = now()->timezone($timezone);

        return match ($config['frequency']) {
            self::FREQUENCY_HOURLY => $now->copy()->addHour()->startOfHour(),
            self::FREQUENCY_WEEKLY => self::nextWeeklyRun($config, $now),
            default => self::nextDailyRun($config, $now),
        };
    }

    public static function summaryLabel(): string
    {
        $config = self::config();

        if (! $config['enabled']) {
            return 'Scheduler nonaktif';
        }

        $time = $config['time'].' '.$config['timezone'];

        return match ($config['frequency']) {
            self::FREQUENCY_HOURLY => 'Hourly',
            self::FREQUENCY_WEEKLY => 'Weekly · '.self::dayLabels($config['days']).' · '.$time,
            default => 'Daily · '.$time,
        };
    }

    /**
     * @param  array{time: string, days: list<int>, timezone: string}  $config
     */
    private static function isDailyDue(array $config, Carbon $now, ?Carbon $lastRun): bool
    {
        [$hour, $minute] = self::timeParts($config['time']);
        $scheduled = $now->copy()->setTime($hour, $minute, 0);

        if ($now->lt($scheduled)) {
            return false;
        }

        return $lastRun === null || $lastRun->lt($scheduled);
    }

    /**
     * @param  array{time: string, days: list<int>, timezone: string}  $config
     */
    private static function isWeeklyDue(array $config, Carbon $now, ?Carbon $lastRun): bool
    {
        if (! in_array((int) $now->dayOfWeekIso, $config['days'], true)) {
            return false;
        }

        return self::isDailyDue($config, $now, $lastRun);
    }

    /**
     * @param  array{time: string, days: list<int>}  $config
     */
    private static function nextDailyRun(array $config, Carbon $now): Carbon
    {
        [$hour, $minute] = self::timeParts($config['time']);
        $candidate = $now->copy()->setTime($hour, $minute, 0);

        if ($now->gte($candidate)) {
            $candidate->addDay();
        }

        return $candidate;
    }

    /**
     * @param  array{time: string, days: list<int>}  $config
     */
    private static function nextWeeklyRun(array $config, Carbon $now): Carbon
    {
        [$hour, $minute] = self::timeParts($config['time']);
        $candidate = $now->copy()->setTime($hour, $minute, 0);

        for ($i = 0; $i < 8; $i++) {
            if (in_array((int) $candidate->dayOfWeekIso, $config['days'], true) && $candidate->gt($now)) {
                return $candidate;
            }

            $candidate->addDay()->setTime($hour, $minute, 0);
        }

        return $candidate;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function timeParts(string $time): array
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return [(int) $hour, (int) $minute];
    }

    /**
     * @param  list<int>  $days
     */
    private static function dayLabels(array $days): string
    {
        $labels = [
            1 => 'Sen',
            2 => 'Sel',
            3 => 'Rab',
            4 => 'Kam',
            5 => 'Jum',
            6 => 'Sab',
            7 => 'Min',
        ];

        return collect($days)
            ->map(fn (int $day) => $labels[$day] ?? (string) $day)
            ->implode(', ');
    }
}
