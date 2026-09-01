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
        // Diatur lewat Admin -> Sistem -> Pengaturan -> Halaman Depan.
        // Dulu semua angka ini (3, 3, dan "tanpa batas" untuk kategori)
        // di-hardcode langsung di kode -- sekarang bisa diubah admin
        // tanpa perlu kirim kode baru tiap kali mau ganti jumlahnya.
        $categoriesLimit = (int) \App\Models\Setting::get('home_categories_limit', 6);
        $featuredLimit = max(1, (int) \App\Models\Setting::get('home_featured_limit', 3));
        $announcementsLimit = max(1, (int) \App\Models\Setting::get('home_announcements_limit', 3));

        $categories = ProductCategory::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($cat) => $cat->products_count > 0);

        // 0 berarti "tanpa batas" -- kalau tidak, potong sesuai
        // pengaturan admin.
        if ($categoriesLimit > 0) {
            $categories = $categories->take($categoriesLimit);
        }

        $featured = Product::active()
            ->with('category')
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->take($featuredLimit)
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
                ->take($featuredLimit)
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
            ->take($announcementsLimit)
            ->get();

        $banners = \App\Models\PromoBanner::live()->forPage('home')->orderBy('sort_order')->get();

        // Tampil/sembunyikan section tanpa perlu hapus datanya --
        // dicek satu per satu di public/home.blade.php.
        $homeSections = [
            'benefits'      => \App\Models\Setting::get('home_show_benefits', '1') === '1',
            'featured'      => \App\Models\Setting::get('home_show_featured', '1') === '1',
            'categories'    => \App\Models\Setting::get('home_show_categories', '1') === '1',
            'announcements' => \App\Models\Setting::get('home_show_announcements', '1') === '1',
        ];

        return compact('categories', 'featured', 'popularTlds', 'announcements', 'banners', 'homeSections');
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

    public function category(string $section, string $slug): View
    {
        return view('public.catalog.category', $this->categoryData($slug));
    }

    public function categoryBootstrap(string $section, string $slug): View
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

    public function product(string $section, string $categorySlug, string $productSlug): View
    {
        return view('public.catalog.product', $this->productData($categorySlug, $productSlug));
    }

    public function productBootstrap(string $section, string $categorySlug, string $productSlug): View
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
