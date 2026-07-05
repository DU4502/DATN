<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    /**
     * Display homepage
     */
    public function index()
    {
        // Get featured products
        $featuredProducts = Product::where('status', true)
            ->latest()
            ->take(8)
            ->get();

        // Get all categories
        $categories = Category::orderBy('name')->get();

        $discoverProductSlugs = [
            'nuoc-ep-cam',
            'sinh-to-bo',
            'tra-dao-cam-sa',
            'ca-phe-u-lanh',
        ];

        $discoverProductsBySlug = Product::query()
            ->with('category')
            ->where('status', true)
            ->whereIn('slug', $discoverProductSlugs)
            ->get()
            ->keyBy('slug');

        $discoverProducts = collect($discoverProductSlugs)
            ->map(fn (string $slug) => $discoverProductsBySlug->get($slug))
            ->filter()
            ->values();

        return view('client.home', compact(
            'featuredProducts',
            'categories',
            'discoverProducts'
        ));
    }
}
