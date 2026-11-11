<?php

namespace App\Http\Controllers;

use App\Models\FeeSetting;
use App\Models\AuditLog;
use App\Notifications\AppNotice;
use Illuminate\Http\Request;

class AdminFeeController extends Controller
{
    // key => [label, validation rule]
    private const SETTINGS = [
        'commission_rate' => ['Commission rate (0 to 1, example 0.12 = 12%)', 'numeric|between:0,1'],
        'free_image_limit' => ['Free images per unit', 'integer|min:1'],
        'invoice_due_days' => ['Days to pay an invoice', 'integer|min:1'],
        'max_featured_per_page' => ['Featured units per results page', 'integer|min:1'],
        'featured_price_week' => ['Featured listing price (7 days)', 'numeric|min:1'],
        'featured_price_month' => ['Featured listing price (30 days)', 'numeric|min:1'],
        'free_unit_limit' => ['Free units per host', 'integer|min:1'],
        'unit_slot_price' => ['Price of one extra unit slot', 'numeric|min:1'],
        'platform_paypal_name' => ['Platform PayPal account name', 'string|max:100'],
        'platform_paypal_email' => ['Platform PayPal email', 'email|max:100'],
        'booking_hold_hours' => ['Hours a guest has to upload a proof of payment', 'integer|min:1'],
        'stale_paid_hours' => ['Hours before a paid booking is flagged as not confirmed', 'integer|min:1'],
    ];

    public function index()
    {
        return view('admin.fees', ['settings' => self::SETTINGS, 'values' => FeeSetting::all()->keyBy('setting_key')]);
    }

    public function update(Request $request)
    {
        $rules = collect(self::SETTINGS)->mapWithKeys(fn ($s, $key) => ["settings.$key" => 'required|'.$s[1]])->all();
        $data = $request->validate($rules)['settings'];

        $changed = [];
        foreach ($data as $key => $value) {
            // updated_at is filled in by Laravel, updated_by is the admin
            $rows = FeeSetting::where('setting_key', $key)->where('setting_value', '!=', $value)
                ->update(['setting_value' => $value, 'updated_by' => $request->user()->user_id]);

            if ($rows) {
                $changed[] = "$key = $value";
            }
        }
        if ($changed) {
            AuditLog::record('Fee settings changed', null, implode(', ', $changed));
        }

        return ['message' => 'Fee settings saved.'];
    }
}