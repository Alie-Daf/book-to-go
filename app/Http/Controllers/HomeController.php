<?php

namespace App\Http\Controllers;

use App\Models\FeaturedListing;
use App\Models\FeeSetting;
use App\Models\Unit;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // category name => sample photo (Unsplash)
        $categories = [
            'Room' => 'photo-1522771739844-6a9f6d5f14af',
            'Studio' => 'photo-1522708323590-d24dbb6b0267',
            'Apartment' => 'photo-1502672260266-1c1ef2d93688',
            'Condo' => 'photo-1545324418-cc1a3fa10c00',
            'House' => 'photo-1564013799919-ab600027ffc6',
        ];

        // "Recommended for you": the Active Featured units
        $featured = Unit::with(['images', 'rates'])->withAvg('reviews', 'rating')
            ->whereIn('unit_id', FeaturedListing::activeUnitIds())
            ->take((int) FeeSetting::get('max_featured_per_page', 3))->get();

        $bookmarked = $request->user()->bookmarks()->pluck('units.unit_id')->all();

        $unitCount = Unit::where('listing_status', 'Approved')->count();

        return view('home', compact('categories', 'featured', 'bookmarked', 'unitCount'));
        
    }
}