<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tld;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Halaman depan situs.
     */
    public function home(): View
    {
        return view('public.home', $this->homeData());
    }

    public function homeBootstrap(): View
    {
        return view('public.home', $this->homeData());
    }

    private function homeData(): array
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
            ->take(3)
            ->get();

        // Kalau belum ada yang ditandai unggulan, tampilkan paket termurah
        // supaya halaman depan tidak kosong.
        if ($featured->isEmpty()) {
            // Tabel produk tidak punya kolom "price" tunggal — harga
            // tersimpan per siklus tagihan, jadi diurutkan dari harga
            // bulanan (jatuh ke tahunan bila bulanan tidak diisi).
            $featured = Product::active()
                ->with('category')
                ->orderByRaw('COALESCE(price_monthly, price_quarterly, price_semi_annually, price_annually) ASC')
                ->take(3)
                ->get();
        }

        // TLD populer untuk ditampilkan di bawah kotak pencarian domain.
        $popularTlds = Tld::where('is_active', true)
            ->where('register_price', '>', 0)
            ->orderBy('register_price')
            ->take(6)
            ->get();

        $announcements = Announcement::live()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $banners = \App\Models\PromoBanner::live()->forPage('home')->orderBy('sort_order')->get();

        return compact('categories', 'featured', 'popularTlds', 'announcements', 'banners');
    }

    public function index(): View
    {
        return view('public.catalog.index', $this->indexData());
    }

    public function indexBootstrap(): View
    {
        return view('public.catalog.index', $this->indexData());
    }

    private function indexData(): array
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

        $banners = \App\Models\PromoBanner::live()->forPage('catalog')->orderBy('sort_order')->get();

        return compact('categories', 'featured', 'banners');
    }

    public function category(string $slug): View
    {
        return view('public.catalog.category', $this->categoryData($slug));
    }

    public function categoryBootstrap(string $slug): View
    {
        return view('public.catalog.category', $this->categoryData($slug));
    }

    private function categoryData(string $slug): array
    {
        $category = ProductCategory::active()->where('slug', $slug)->firstOrFail();

        $products = $category->products()
            ->active()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);

        return compact('category', 'products');
    }

    public function product(string $categorySlug, string $productSlug): View
    {
        return view('public.catalog.product', $this->productData($categorySlug, $productSlug));
    }

    public function productBootstrap(string $categorySlug, string $productSlug): View
    {
        return view('public.catalog.product', $this->productData($categorySlug, $productSlug));
    }

    private function productData(string $categorySlug, string $productSlug): array
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

        return compact('category', 'product', 'related');
    }
}
