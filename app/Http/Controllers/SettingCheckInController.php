<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingCheckInController extends Controller
{
    public function edit(): View
    {
        $hours = AppSetting::getInt('checkin_availability_hours', 24);

        return view('settings.checkins.edit', [
            'hours' => $hours,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);

        AppSetting::setValue('checkin_availability_hours', $data['hours']);

        return redirect()->route('settings.checkins.edit')->with('status', 'Check-in availability updated.');
    }
}
