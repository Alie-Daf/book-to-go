<?php

namespace App\Http\Controllers;

use App\Models\FeaturedListing;
use App\Models\FeeSetting;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HostFeaturedController extends Controller
{
    public function index(Request $request)
    {
        $host = $request->user();

        return view('host.featured', [
            'units' => $host->units()->where('listing_status', 'Approved')->orderBy('title')->get(),
            'listings' => FeaturedListing::with(['unit', 'invoice'])
                ->whereHas('unit', fn ($q) => $q->where('host_id', $host->user_id))->latest('featured_id')->get(),
            'weekPrice' => FeeSetting::get('featured_price_week'),
            'monthPrice' => FeeSetting::get('featured_price_month'),
        ]);
    }

    public function store(Request $request)
    {
        $host = $request->user();
        abort_if($host->is_on_hold, 422, User::HOLD_MESSAGE);

        $data = $request->validate([
            'unit_id' => 'required|integer',
            'start_date' => 'required|date|after_or_equal:today',
            'days' => 'required|in:7,30',
        ]);

        $unit = $host->units()->where('unit_id', $data['unit_id'])->first();
        abort_unless($unit && $unit->listing_status === 'Approved', 422, 'Only approved units can be featured.');

        // the price comes from the fee settings
        $price = FeeSetting::get($data['days'] == 7 ? 'featured_price_week' : 'featured_price_month');
        $start = Carbon::parse($data['start_date']);

        DB::transaction(function () use ($host, $unit, $data, $price, $start) {
            $invoice = BillingService::createInvoice($host->user_id, 'Featured', $price);

            FeaturedListing::create([
                'unit_id' => $unit->unit_id,
                'invoice_id' => $invoice->invoice_id,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($data['days'] - 1)->toDateString(),
            ]);
        });

        return ['message' => 'Request sent. Pay the invoice in Billing, then wait for the admin to approve it.'];
    }
}