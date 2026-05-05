<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return view('products.index', [
            'products' => Product::latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

    public function show(Request $request, Product $product)
    {
        $stockMovements = $product->stockMovements()
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('products.show', compact('product', 'stockMovements'));
    }

    public function store(Request $request)
    {
        Product::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'category' => ['nullable', 'string', 'max:100'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_alert' => ['required', 'integer', 'min:0'],
        ]));

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function moveStock(Request $request, Product $product, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $inventoryService->moveStock($product, $data['type'], (int) $data['quantity'], $data['reason'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('products.index')->with('success', 'Stock updated successfully.');
    }
}
