<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class HostAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $units = $request->user()->units()->orderBy('title')->get();
        $unit = $units->firstWhere('unit_id', $request->unit) ?? $units->first();

        $key = preg_match('/^\d{4}-\d{2}$/', (string) $request->month) ? $request->month : now()->format('Y-m');
        $month = Carbon::parse($key.'-01');

        $ranges = collect();
        $blocked = collect();

        if ($unit) {
            // booked and blocked ranges that touch this month
            $ranges = $unit->availability()->whereIn('availability_status', ['Booked', 'Blocked'])
                ->where('start_date', '<=', $month->copy()->endOfMonth()->toDateString())
                ->where('end_date', '>=', $month->toDateString())->get();

            $blocked = $unit->availability()->where('availability_status', 'Blocked')
                ->where('end_date', '>=', today()->toDateString())->orderBy('start_date')->get();
        }

        return view('host.availability', compact('units', 'unit', 'month', 'ranges', 'blocked'));
    }

    // Close dates (host unavailable)
    public function block(Request $request, Unit $unit)
    {
        abort_unless($unit->host_id == $request->user()->user_id, 403);

        $data = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|max:500',
        ]);

        DB::transaction(function () use ($unit, $data) {
            Unit::whereKey($unit->unit_id)->lockForUpdate()->first();   // same lock as the booking form

            // isAvailable() expects a check-out date, so pass the day after the last blocked night
            if (! $unit->isAvailable($data['start_date'], Carbon::parse($data['end_date'])->addDay()->toDateString())) {
                throw ValidationException::withMessages(['start_date' => 'Some of those dates are already booked or blocked.']);
            }

            $unit->availability()->create([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'availability_status' => 'Blocked',
                'notes' => $data['notes'] ?? 'Closed by host',
            ]);
        });

        return ['message' => 'Dates blocked.'];
    }

    public function unblock(UnitAvailability $availability)
    {
        $unit = Unit::findOrFail($availability->unit_id);
        abort_unless($unit->host_id == auth()->id() && $availability->availability_status === 'Blocked', 403);

        $availability->delete();

        return ['message' => 'Dates are open again.'];
    }
}