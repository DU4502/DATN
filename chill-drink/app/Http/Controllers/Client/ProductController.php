<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Support\ProductCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    /**
     * Display product list
     */
    public function index(Request $request)
    {
        $query = Product::where('status', true)->with('category');
        $hasSkuColumn = Schema::hasColumn('products', 'sku');

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Search by name
        $searchQuery = trim((string) $request->input('search', ''));
        if ($searchQuery !== '') {
            $query->where(function ($q) use ($searchQuery, $hasSkuColumn) {
                $q->where('name', 'like', '%'.$searchQuery.'%');

                if ($hasSkuColumn) {
                    $q->orWhere('sku', 'like', '%'.$searchQuery.'%');
                }
            });
        }

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $demoCategoryMap = collect([
            'tra-sua' => 'Trà sữa',
            'ca-phe' => 'Cà phê',
            'nuoc-ep' => 'Nước ép',
            'sinh-to' => 'Sinh tố',
            'tra-trai-cay' => 'Trà trái cây',
        ]);
        $demoProducts = collect([
            ['name' => 'Trà Sữa Trân Châu Đường Đen', 'category' => 'tra-sua', 'price' => 75450, 'slug' => 'tra-sua-tran-chau-duong-den', 'description' => 'Trà sữa thơm béo hòa cùng đường đen và trân châu mềm.', 'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85'],
            ['name' => 'Trà Sữa Trân Châu', 'category' => 'tra-sua', 'price' => 62000, 'slug' => 'tra-sua-tran-chau-demo', 'description' => 'Trà sữa đậm vị cùng trân châu mềm, lựa chọn quen thuộc dễ uống.', 'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=85'],
            ['name' => 'Matcha Latte Đá', 'category' => 'tra-sua', 'price' => 57000, 'slug' => 'matcha-latte-da', 'description' => 'Matcha thơm nhẹ kết hợp sữa tươi béo mịn.', 'image' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=85'],
            ['name' => 'Cà Phê Sữa Đá', 'category' => 'ca-phe', 'price' => 24971, 'slug' => 'ca-phe-sua-da', 'description' => 'Cà phê phin đậm vị, sữa đặc béo ngậy.', 'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85'],
            ['name' => 'Cà Phê Ủ Lạnh', 'category' => 'ca-phe', 'price' => 52000, 'slug' => 'cold-brew-arctic', 'description' => 'Cà phê ủ lạnh êm vị, uống cùng đá viên lớn.', 'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=700&q=85'],
            ['name' => 'Nước Ép Cam Chanh Dây', 'category' => 'nuoc-ep', 'price' => 49000, 'slug' => 'citrus-sunset', 'description' => 'Cam, chanh dây và soda tạo vị chua ngọt sảng khoái.', 'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=700&q=85'],
            ['name' => 'Sinh Tố Dâu', 'category' => 'sinh-to', 'price' => 45000, 'slug' => 'sinh-to-dau', 'description' => 'Dâu tươi chín ngọt xay mịn với sữa, vị thanh mát.', 'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=700&q=85'],
            ['name' => 'Trà Trái Cây Nhiệt Đới', 'category' => 'tra-trai-cay', 'price' => 59000, 'slug' => 'tropical-frost', 'description' => 'Xoài, thanh long và trà xanh tạo một ly trái cây rực rỡ.', 'image' => 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=700&q=85'],
        ]);

        if ($products->count() === 0) {
            $demoProducts = $demoProducts
                ->when($request->filled('category'), fn ($items) => $items->where('category', $request->category))
                ->when($searchQuery !== '', fn ($items) => $items->filter(fn ($item) => str_contains(mb_strtolower($item['name']), mb_strtolower($searchQuery))))
                ->values();

            $categories = $demoCategoryMap->map(fn ($name, $id) => (object) ['id' => $id, 'name' => $name])->values();
        }

        return view('client.products.index', compact('products', 'categories', 'searchQuery', 'demoProducts'));
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
                    'category' => 'Sinh Tố',
                ],
                'sinh-to-dau' => [
                    'name' => 'Sinh Tố Dâu',
                    'price' => 45000,
                    'image' => 'https://images.unsplash.com/photo-1553530666-ba11a7da3888?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Dâu tươi chín ngọt xay mịn với sữa, vị chua ngọt thanh mát.',
                    'category' => 'Sinh Tố',
                ],
                'matcha-latte-da' => [
                    'name' => 'Matcha Latte Đá',
                    'price' => 57000,
                    'image' => 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Matcha thơm nhẹ kết hợp sữa tươi béo mịn, hợp cho ngày cần tỉnh táo.',
                    'category' => 'Trà Sữa',
                ],
                'citrus-sunset' => [
                    'name' => 'Nước Ép Cam Chanh Dây',
                    'price' => 49000,
                    'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Cam, chanh dây và soda tạo vị chua ngọt sảng khoái.',
                    'category' => 'Nước Ép',
                ],
                'tra-sua-tran-chau-demo' => [
                    'name' => 'Trà Sữa Trân Châu',
                    'price' => 62000,
                    'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Trà sữa đậm vị cùng trân châu mềm, lựa chọn quen thuộc dễ uống.',
                    'category' => 'Trà Sữa',
                ],
                'tra-sua-tran-chau-duong-den' => [
                    'name' => 'Trà Sữa Trân Châu Đường Đen',
                    'price' => 75450,
                    'image' => 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Trà sữa thơm béo hòa cùng đường đen và trân châu mềm, vị ngọt đậm nhưng vẫn dễ uống.',
                    'category' => 'Trà Sữa',
                ],
                'ca-phe-sua-da' => [
                    'name' => 'Cà Phê Sữa Đá',
                    'price' => 24971,
                    'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Cà phê phin đậm vị, sữa đặc béo ngậy, ly sữa đá quen thuộc mọi buổi sáng.',
                    'category' => 'Cà Phê',
                ],
                'cold-brew-arctic' => [
                    'name' => 'Cà Phê Ủ Lạnh',
                    'price' => 52000,
                    'image' => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Cà phê ủ lạnh êm vị, uống cùng đá viên lớn cực mát.',
                    'category' => 'Cà Phê',
                ],
                'tropical-frost' => [
                    'name' => 'Trà Trái Cây Nhiệt Đới',
                    'price' => 59000,
                    'image' => 'https://images.unsplash.com/photo-1622597467836-f3285f2131b8?auto=format&fit=crop&w=1000&q=85',
                    'description' => 'Xoài, thanh long và trà xanh tạo một ly trái cây rực rỡ.',
                    'category' => 'Trà Trái Cây',
                ],
            ]);

            abort_unless($demoProducts->has($slug), 404);

            $item = $demoProducts->get($slug);
            $codes = ProductCatalog::codesFor($item['name'], $item['category']);
            $product = (object) [
                'id' => 'demo-'.$slug,
                'name' => $item['name'],
                'slug' => $codes['slug'],
                'sku' => $codes['sku'],
                'price' => $item['price'],
                'image' => $item['image'],
                'image_url' => $item['image'],
                'gallery_images' => [$item['image']],
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
