<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($cat) => $cat->products_count > 0);

        $featured = Product::active()
            ->with('category')
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        return view('public.catalog.index', compact('categories', 'featured'));
    }

    public function category(string $slug): View
    {
        $category = ProductCategory::active()->where('slug', $slug)->firstOrFail();

        $products = $category->products()
            ->active()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);

        return view('public.catalog.category', compact('category', 'products'));
    }

    public function product(string $categorySlug, string $productSlug): View
    {
        $category = ProductCategory::active()->where('slug', $categorySlug)->firstOrFail();

        $product = Product::active()
            ->where('product_category_id', $category->id)
            ->where('slug', $productSlug)
            ->firstOrFail();

        $related = Product::active()
            ->where('product_category_id', $category->id)
            ->where('id', '!=', $product->id)
            ->take(3)
            ->get();

        return view('public.catalog.product', compact('category', 'product', 'related'));
    }
}
