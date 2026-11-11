<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\UnitRate;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use App\Models\FeeSetting;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class HostUnitController extends Controller
{
    private const FIELDS = ['title', 'category', 'description', 'address_line', 'city', 'province',
                            'postal_code', 'max_guests', 'check_in_time', 'check_out_time', 'house_rules'];

    public function index(Request $request)
    {
        $units = $request->user()->units()->with(['images', 'rates'])->latest('unit_id')->get();

        return view('host.units.index', compact('units'));
    }

    public function create()
    {
        $unit = new Unit(['max_guests' => 2, 'check_in_time' => '14:00', 'check_out_time' => '12:00']);

        return $this->form($unit, collect([new UnitRate(['guest_count' => 2])]));
    }

    public function edit(Unit $unit)
    {
        $this->ownUnit($unit);
        $unit->load(['images', 'rates', 'amenities']);

        return $this->form($unit, $unit->rates->sortBy('guest_count')->values());
    }

    public function store(Request $request)
    {
        $host = $request->user();
        abort_if($host->is_on_hold, 422, User::HOLD_MESSAGE);   // no new listings while on hold
        abort_if($host->units()->where('listing_status', '!=', 'Inactive')->count() >= $host->unitLimit(), 422,
            'You reached your unit limit. Buy an extra unit slot in Billing.');

        $data = $request->validate($this->rules());
        $this->checkImageLimit((int) FeeSetting::get('free_image_limit', 7), 0, $request);

        $unit = $host->units()->create(Arr::only($data, self::FIELDS));   // starts as Pending
        $this->saveExtras($unit, $request, $data);

        return ['message' => 'Unit saved. It goes live after the admin approves it.', 'redirect' => route('host.units.index')];
    }

    public function update(Request $request, Unit $unit)
    {
        $this->ownUnit($unit);
        $data = $request->validate($this->rules($unit));
        $this->checkImageLimit($unit->imageLimit(), $unit->images()->count(), $request);

        // a unit that was never approved goes back to Pending so the admin can review it again
        $unit->update(Arr::only($data, self::FIELDS) + ($unit->approved_at ? [] : ['listing_status' => 'Pending']));
        $this->saveExtras($unit, $request, $data);

        return ['message' => 'Unit updated.', 'redirect' => route('host.units.index')];
    }

    // Block the upload when the unit would have more photos than it is allowed
    private function checkImageLimit(int $limit, int $existing, Request $request): void
    {
        if ($existing + count($request->file('photos', [])) > $limit) {
            throw ValidationException::withMessages(['photos' => 'You reached your image limit. Buy an image pack to add more.']);
        }
    }

    // Switch a live listing off (Inactive) or back on (Approved)
    public function toggle(Unit $unit)
    {
        $this->ownUnit($unit);
        abort_if($unit->listing_status === 'Pending' || ! $unit->approved_at, 422, 'This unit needs the admin approval first.');

        $unit->update(['listing_status' => $unit->listing_status === 'Approved' ? 'Inactive' : 'Approved']);

        return ['message' => 'Listing is now '.$unit->listing_status.'.'];
    }

    public function deletePhoto(UnitImage $image)
    {
        $this->ownUnit(Unit::findOrFail($image->unit_id));
        $image->delete();

        return ['message' => 'Photo removed.'];
    }

    // ---------- helpers ----------
    private function form(Unit $unit, $rates)
    {
        return view('host.units.form', [
            'unit' => $unit,
            'rates' => $rates,
            'amenities' => Amenity::orderBy('amenity_name')->get(),
            'selected' => $unit->exists ? $unit->amenities->pluck('amenity_id')->all() : [],
            'categories' => ['Room', 'Studio', 'Apartment', 'Condo', 'House'],
        ]);
    }

    private function rules(?Unit $unit = null): array
    {
        return [
            'title' => 'required|max:180',
            'category' => 'required|in:Room,Studio,Apartment,Condo,House',
            'description' => 'nullable|max:2000',
            'address_line' => 'required|max:255',
            'city' => 'required|max:100',
            'province' => 'required|max:100',
            'postal_code' => 'nullable|max:20',
            'max_guests' => 'required|integer|min:1|max:50',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i',
            'house_rules' => 'required|max:2000',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,amenity_id',
            'rates' => 'required|array|min:1',
            'rates.*.guest_count' => 'required|integer|min:1',
            'rates.*.nightly_rate' => 'required|numeric|min:1',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:4096',
            // proof that the host may rent out THIS unit (title) and, for condos, the building's permission
            'title_document' => [Rule::requiredIf(! $unit?->title_document), 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'permission_document' => [Rule::requiredIf(fn () => request('category') === 'Condo' && ! $unit?->permission_document),
                                      'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    // amenities, pax rates and photos
    private function saveExtras(Unit $unit, Request $request, array $data): void
    {
        $unit->amenities()->sync($data['amenities'] ?? []);

        $unit->rates()->delete();
        foreach ($data['rates'] as $rate) {
            $unit->rates()->create(['guest_count' => $rate['guest_count'], 'nightly_rate' => $rate['nightly_rate'], 'valid_from' => today()]);
        }

        foreach ($request->file('photos', []) as $photo) {
            $unit->images()->create([
                'image_url' => '/storage/'.$photo->store('units', 'public'),
                'alt_text' => $unit->title,
                'display_order' => $unit->images()->count(),
            ]);
        }
    $this->saveDocuments($unit, $request);
    }

        // title and condo permission go to a private folder
    private function saveDocuments(Unit $unit, Request $request): void
    {
        foreach (['title_document', 'permission_document'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            if ($unit->$field) {
                Storage::delete($unit->$field);
            }
            $unit->update([$field => $request->file($field)->store('unit-documents')]);

            // a new title on a live listing sends it back to the admin for review
            if ($field === 'title_document' && $unit->approved_at) {
                $unit->update(['listing_status' => 'Pending', 'approved_at' => null, 'approved_by' => null]);
            }
        }
    }
    
    private function ownUnit(Unit $unit): void
    {
        abort_unless($unit->host_id == auth()->id(), 403);
    }
}