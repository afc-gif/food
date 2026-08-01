<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BusinessHours;
use Illuminate\Http\Request;

class BusinessHoursController extends Controller
{
    public function show(BusinessHours $businessHours)
    {
        return $businessHours->availability();
    }

    public function update(Request $request, BusinessHours $businessHours)
    {
        $data = $request->validate([
            'mode' => 'required|string|in:auto,force_open,force_closed',
        ]);

        return $businessHours->setOverrideMode($data['mode']);
    }
}
