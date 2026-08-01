<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BusinessHours;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BusinessHoursController extends Controller
{
    public function show(BusinessHours $businessHours)
    {
        return $businessHours->availability();
    }

    public function update(Request $request, BusinessHours $businessHours)
    {
        $data = $request->validate([
            'mode' => 'nullable|string|in:auto,force_open,force_closed',
            'schedule' => 'nullable|array',
            'schedule.weekday.open' => 'required_with:schedule|string|date_format:H:i',
            'schedule.weekday.close' => 'required_with:schedule|string|date_format:H:i',
            'schedule.sunday.open' => 'required_with:schedule|string|date_format:H:i',
            'schedule.sunday.close' => 'required_with:schedule|string|date_format:H:i',
        ]);

        if (isset($data['schedule'])) {
            foreach (['weekday', 'sunday'] as $dayType) {
                if (($data['schedule'][$dayType]['open'] ?? '00:00') >= ($data['schedule'][$dayType]['close'] ?? '00:00')) {
                    throw ValidationException::withMessages([
                        'schedule' => ['Closing time must be later than opening time.'],
                    ]);
                }
            }

            $businessHours->setSchedule($data['schedule']);
        }

        if (isset($data['mode'])) {
            return $businessHours->setOverrideMode($data['mode']);
        }

        return $businessHours->availability();
    }
}
