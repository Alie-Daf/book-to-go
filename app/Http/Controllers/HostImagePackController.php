<?php

namespace App\Http\Controllers;

use App\Models\FeeSetting;
use App\Models\ImagePack;
use App\Models\Unit;
use App\Models\UnitImagePack;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HostImagePackController extends Controller
{
    public function index(Request $request)
    {
        return view('host.image-packs', [
            'packs' => ImagePack::orderBy('price')->get(),
            'units' => $request->user()->units()->with('images')->orderBy('title')->get(),
            'freeLimit' => FeeSetting::get('free_image_limit', 7),
        ]);
    }

    // Choosing a pack creates an Unpaid invoice. The extra images unlock when the admin marks it Paid.
    public function buy(Request $request)
    {
        $data = $request->validate(['unit_id' => 'required|integer', 'pack_id' => 'required|exists:image_packs,pack_id']);

        $unit = Unit::findOrFail($data['unit_id']);
        abort_unless($unit->host_id == $request->user()->user_id, 403);
        $pack = ImagePack::findOrFail($data['pack_id']);

        DB::transaction(function () use ($request, $unit, $pack) {
            $invoice = BillingService::createInvoice($request->user()->user_id, 'Image Pack', $pack->price);
            UnitImagePack::create(['unit_id' => $unit->unit_id, 'pack_id' => $pack->pack_id, 'invoice_id' => $invoice->invoice_id]);
        });

        return ['message' => 'Invoice created. Pay it in Billing to unlock the extra images.', 'redirect' => route('host.billing')];
    }
}