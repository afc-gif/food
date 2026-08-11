<?php

use App\Http\Controllers\ProfileController;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;

// Serve static assets directly
Route::get('/styles.css', function () {
    return response()->file(public_path('styles.css'), ['Content-Type' => 'text/css']);
});

Route::get('/script.js', function () {
    return response()->file(public_path('script.js'), ['Content-Type' => 'application/javascript']);
});

// Admin preview of the public site
Route::get('/live.html', function () {
    return response()->file(public_path('live.html'), ['Content-Type' => 'text/html']);
});

Route::get('/assets/{file}', function ($file) {
    $publicPath = public_path('assets/' . $file);
    if (file_exists($publicPath) && is_file($publicPath)) {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
        return response()->file($publicPath, ['Content-Type' => $mimeType]);
    }
    return abort(404);
})->where('file', '.*');

// SEO: Sitemap
Route::get('/sitemap.xml', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Home page - highest priority
    $xml .= '  <url>' . "\n";
    $xml .= '    <loc>https://www.afc.com.ng/</loc>' . "\n";
    $xml .= '    <lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>' . "\n";
    $xml .= '    <changefreq>daily</changefreq>' . "\n";
    $xml .= '    <priority>1.0</priority>' . "\n";
    $xml .= '  </url>' . "\n";

    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
});

Route::get('/', function () {
    try {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        $menuItems = MenuItem::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $featured = $menuItems->take(3);
    } catch (QueryException $e) {
        // When migrations haven't run yet, keep the site up with empty data.
        report($e);
        $categories = collect();
        $menuItems = collect();
        $featured = collect();
    }

    return view('home', compact('categories', 'menuItems', 'featured'));
})->name('home');

Route::get('/receipt/{order:code}', function (Order $order) {
    return view('print-receipt', [
        'order' => $order->load(['items', 'payments']),
        'publicReceipt' => true,
    ]);
})->name('public-receipt');

Route::middleware('auth')->group(function () {
    Route::view('/admin', 'admin')->middleware(['active', 'role:admin'])->name('admin');
    Route::view('/pos', 'pos')->middleware(['active', 'role:admin|pos'])->name('pos');
    Route::view('/staff', 'staff')->middleware(['active', 'role:admin|staff'])->name('staff');
    Route::get('/kitchen', function () {
        $orders = Order::with(['items', 'payments', 'creator'])
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        return view('kitchen', ['initialOrders' => $orders]);
    })->middleware(['active', 'role:admin|kitchen'])->name('kitchen');
    Route::get('/print/{order}', function (Order $order) {
        return view('print-receipt', ['order' => $order->load(['items', 'payments'])]);
    })->middleware(['active', 'role:admin|pos|staff'])->name('print-receipt');
    Route::get('/profile', [ProfileController::class, 'edit'])->middleware('active')->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('active')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('active')->name('profile.destroy');
});

require __DIR__.'/auth.php';
