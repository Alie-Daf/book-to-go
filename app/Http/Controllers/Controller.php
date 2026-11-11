<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    // "2026-10-01 to 2026-10-31" from a date-range box becomes [from, to]
    protected function dateRange(Request $request): array
    {
        [$from, $to] = array_pad(explode(' to ', (string) $request->input('range')), 2, null);

        return [$from ?: null, $to ?: $from ?: null];
    }
}