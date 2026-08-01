<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class BusinessHours
{
    private const TIMEZONE = 'Africa/Lagos';
    private const OVERRIDE_KEY = 'order_availability_mode';
    private const SCHEDULE_KEY = 'order_schedule';
    private const MODE_AUTO = 'auto';
    private const MODE_FORCE_OPEN = 'force_open';
    private const MODE_FORCE_CLOSED = 'force_closed';
    private const DEFAULT_SCHEDULE = [
        'weekday' => ['open' => '08:00', 'close' => '22:00'],
        'sunday' => ['open' => '12:00', 'close' => '22:00'],
    ];

    public function availability(?Carbon $now = null): array
    {
        $now = ($now ?? Carbon::now(self::TIMEZONE))->copy()->setTimezone(self::TIMEZONE);
        $mode = $this->overrideMode();
        $schedule = $this->scheduleFor($now);
        $scheduledOpen = $now->greaterThanOrEqualTo($schedule['opens_at'])
            && $now->lessThan($schedule['closes_at']);

        $isOpen = match ($mode) {
            self::MODE_FORCE_OPEN => true,
            self::MODE_FORCE_CLOSED => false,
            default => $scheduledOpen,
        };

        $nextOpenAt = $isOpen ? null : $this->nextOpenAt($now);

        return [
            'is_open' => $isOpen,
            'mode' => $mode,
            'timezone' => self::TIMEZONE,
            'schedule' => $this->schedule(),
            'opens_at' => $schedule['opens_at']->toIso8601String(),
            'closes_at' => $schedule['closes_at']->toIso8601String(),
            'next_open_at' => $nextOpenAt?->toIso8601String(),
            'message' => $this->message($isOpen, $mode, $nextOpenAt, $schedule['closes_at']),
        ];
    }

    public function setOverrideMode(string $mode): array
    {
        if (! in_array($mode, [self::MODE_AUTO, self::MODE_FORCE_OPEN, self::MODE_FORCE_CLOSED], true)) {
            $mode = self::MODE_AUTO;
        }

        DB::table('business_settings')->updateOrInsert(
            ['key' => self::OVERRIDE_KEY],
            ['value' => $mode, 'updated_at' => now(), 'created_at' => now()]
        );

        return $this->availability();
    }

    public function setSchedule(array $schedule): array
    {
        $normalized = [
            'weekday' => [
                'open' => $this->normalizeTime($schedule['weekday']['open'] ?? null, self::DEFAULT_SCHEDULE['weekday']['open']),
                'close' => $this->normalizeTime($schedule['weekday']['close'] ?? null, self::DEFAULT_SCHEDULE['weekday']['close']),
            ],
            'sunday' => [
                'open' => $this->normalizeTime($schedule['sunday']['open'] ?? null, self::DEFAULT_SCHEDULE['sunday']['open']),
                'close' => $this->normalizeTime($schedule['sunday']['close'] ?? null, self::DEFAULT_SCHEDULE['sunday']['close']),
            ],
        ];

        DB::table('business_settings')->updateOrInsert(
            ['key' => self::SCHEDULE_KEY],
            ['value' => json_encode($normalized), 'updated_at' => now(), 'created_at' => now()]
        );

        return $this->availability();
    }

    private function overrideMode(): string
    {
        try {
            $mode = DB::table('business_settings')->where('key', self::OVERRIDE_KEY)->value('value');
        } catch (Throwable) {
            return self::MODE_AUTO;
        }

        return in_array($mode, [self::MODE_AUTO, self::MODE_FORCE_OPEN, self::MODE_FORCE_CLOSED], true)
            ? $mode
            : self::MODE_AUTO;
    }

    private function scheduleFor(Carbon $date): array
    {
        $hours = $this->schedule()[$date->isSunday() ? 'sunday' : 'weekday'];
        [$openHour, $openMinute] = array_map('intval', explode(':', $hours['open']));
        [$closeHour, $closeMinute] = array_map('intval', explode(':', $hours['close']));

        $opensAt = $date->copy()->startOfDay()->setTime($openHour, $openMinute);
        $closesAt = $date->copy()->startOfDay()->setTime($closeHour, $closeMinute);

        return ['opens_at' => $opensAt, 'closes_at' => $closesAt];
    }

    private function schedule(): array
    {
        try {
            $raw = DB::table('business_settings')->where('key', self::SCHEDULE_KEY)->value('value');
            $saved = $raw ? json_decode($raw, true) : [];
        } catch (Throwable) {
            $saved = [];
        }

        return [
            'weekday' => [
                'open' => $this->normalizeTime($saved['weekday']['open'] ?? null, self::DEFAULT_SCHEDULE['weekday']['open']),
                'close' => $this->normalizeTime($saved['weekday']['close'] ?? null, self::DEFAULT_SCHEDULE['weekday']['close']),
            ],
            'sunday' => [
                'open' => $this->normalizeTime($saved['sunday']['open'] ?? null, self::DEFAULT_SCHEDULE['sunday']['open']),
                'close' => $this->normalizeTime($saved['sunday']['close'] ?? null, self::DEFAULT_SCHEDULE['sunday']['close']),
            ],
        ];
    }

    private function normalizeTime(?string $value, string $fallback): string
    {
        if (! is_string($value) || ! preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $fallback;
        }

        [$hour, $minute] = array_map('intval', explode(':', $value));
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return $fallback;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function nextOpenAt(Carbon $now): Carbon
    {
        for ($i = 0; $i <= 7; $i++) {
            $date = $now->copy()->addDays($i);
            $schedule = $this->scheduleFor($date);

            if ($now->lessThan($schedule['opens_at'])) {
                return $schedule['opens_at'];
            }
        }

        return $this->scheduleFor($now->copy()->addDay())['opens_at'];
    }

    private function message(bool $isOpen, string $mode, ?Carbon $nextOpenAt, Carbon $closesAt): string
    {
        if ($mode === self::MODE_FORCE_OPEN) {
            return 'Ordering is open right now.';
        }

        if ($isOpen) {
            return 'Ordering is open until '.$closesAt->format('g:ia').'.';
        }

        if ($mode === self::MODE_FORCE_CLOSED) {
            return 'We are currently closed and not accepting orders.';
        }

        $day = $nextOpenAt?->isToday() ? 'today' : $nextOpenAt?->format('l');
        $time = $nextOpenAt?->format('g:ia');
        if ($time === '12:00pm') {
            $time = '12noon';
        }

        return "We are currently closed and not accepting orders. Orders open {$day} at {$time}.";
    }
}
