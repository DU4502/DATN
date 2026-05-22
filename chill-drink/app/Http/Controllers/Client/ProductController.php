<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    /**
     * Display product list
     */
    public function index(Request $request)
    {
        $query = Product::where('status', true)->with('category');

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(12);
        $categories = Category::orderBy('name')->get();

        return view('client.products.index', compact('products', 'categories'));
    }

    /**
     * Display product detail
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('status', true)
            ->with('category')
            ->first();

        if (! $product) {
            $demoProducts = collect([
                'wild-berry-bliss' => [
                    'name' => 'Sinh Tố Dâu Rừng',
                    'price' => 65000,
                    'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Sinh tố dâu rừng mát lạnh, vị chua ngọt dịu và hương trái cây tươi.',
                    'category' => 'Sinh tố',
                ],
                'matcha-latte-da' => [
                    'name' => 'Matcha Latte Đá',
                    'price' => 57000,
                    'image' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Matcha thơm nhẹ kết hợp sữa tươi béo mịn, hợp cho ngày cần tỉnh táo.',
                    'category' => 'Trà sữa',
                ],
                'citrus-sunset' => [
                    'name' => 'Nước Ép Cam Chanh Dây',
                    'price' => 49000,
                    'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Cam, chanh dây và soda tạo vị chua ngọt sảng khoái.',
                    'category' => 'Nước ép',
                ],
                'tra-sua-tran-chau-demo' => [
                    'name' => 'Trà Sữa Trân Châu',
                    'price' => 62000,
                    'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Trà sữa đậm vị cùng trân châu mềm, lựa chọn quen thuộc dễ uống.',
                    'category' => 'Trà sữa',
                ],
                'cold-brew-arctic' => [
                    'name' => 'Cà Phê Ủ Lạnh',
                    'price' => 52000,
                    'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Cà phê ủ lạnh êm vị, uống cùng đá viên lớn cực mát.',
                    'category' => 'Cà phê',
                ],
                'tropical-frost' => [
                    'name' => 'Trà Trái Cây Nhiệt Đới',
                    'price' => 59000,
                    'image' => 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Xoài, thanh long và trà xanh tạo một ly trái cây rực rỡ.',
                    'category' => 'Trà trái cây',
                ],
            ]);

            abort_unless($demoProducts->has($slug), 404);

            $item = $demoProducts->get($slug);
            $product = (object) [
                'id' => 'demo-' . $slug,
                'name' => $item['name'],
                'slug' => $slug,
                'price' => $item['price'],
                'image' => $item['image'],
                'description' => $item['description'],
                'stock' => 20,
                'category' => (object) ['name' => $item['category']],
            ];

            $relatedProducts = new Collection();

            return view('client.products.show', compact('product', 'relatedProducts'));
        }

        // Get related products
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', true)
            ->take(4)
            ->get();

        return view('client.products.show', compact('product', 'relatedProducts'));
    }
}
