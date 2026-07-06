<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

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

        return view('client.home', compact('featuredProducts', 'categories'));
    }
}
