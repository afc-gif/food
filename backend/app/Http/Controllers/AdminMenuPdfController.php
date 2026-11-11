<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AdminMenuPdfController extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $items = MenuItem::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $itemsByCategory = $items->groupBy('category_id');
        $logoPath = public_path('assets/logo2.png');
        $logoContents = is_file($logoPath) && is_readable($logoPath)
            ? file_get_contents($logoPath)
            : false;

        $pdf = Pdf::loadView('menu-pdf', [
            'categories' => $categories,
            'itemsByCategory' => $itemsByCategory,
            'uncategorizedItems' => $items->filter(
                fn (MenuItem $item) => ! $item->category_id || ! $item->category?->is_active
            ),
            'generatedAt' => now(),
            'logoData' => $logoContents !== false
                ? 'data:image/png;base64,' . base64_encode($logoContents)
                : null,
        ])->setPaper('a4');

        return $pdf->download('acie-fraiche-menu-' . now()->format('Y-m-d') . '.pdf');
    }
}
