<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('creator:id,name,email')->orderByDesc('expense_date')->orderByDesc('id');

        if ($request->filled('date')) {
            $query->whereDate('expense_date', $request->input('date'));
        }

        if ($request->boolean('today_only', false)) {
            $query->whereDate('expense_date', Carbon::today());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $expenses = $query->get();
        $total = $expenses->sum('amount');

        return response()->json([
            'expenses' => $expenses,
            'total' => (float) $total,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'amount'         => 'nullable|numeric|min:0',
            'quantity'       => 'nullable|numeric|min:0',
            'unit'           => 'nullable|string|max:50',
            'price_per_unit' => 'nullable|numeric|min:0',
            'category'       => 'nullable|string|max:100',
            'buyer_name'     => 'nullable|string|max:255',
            'expense_date'   => 'nullable|date',
            'note'           => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Auto-calculate amount if quantity and price_per_unit are provided
        $quantity      = isset($data['quantity']) ? (float) $data['quantity'] : null;
        $pricePerUnit  = isset($data['price_per_unit']) ? (float) $data['price_per_unit'] : null;
        $amount        = isset($data['amount']) && $data['amount'] > 0
            ? (float) $data['amount']
            : ($quantity && $pricePerUnit ? round($quantity * $pricePerUnit, 2) : 0);

        $expense = Expense::create([
            'title'          => $data['title'],
            'amount'         => $amount,
            'quantity'       => $quantity,
            'unit'           => $data['unit'] ?? null,
            'price_per_unit' => $pricePerUnit,
            'category'       => $data['category'] ?? 'General',
            'buyer_name'     => $data['buyer_name'] ?? null,
            'expense_date'   => $data['expense_date'] ?? Carbon::today()->toDateString(),
            'logged_by'      => $user?->name ?? $user?->email ?? 'Manager',
            'created_by'     => $user?->id,
            'note'           => $data['note'] ?? null,
        ]);

        return response()->json($expense->load('creator:id,name,email'), 201);
    }

    public function destroy(Request $request, Expense $expense)
    {
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully']);
    }
}
