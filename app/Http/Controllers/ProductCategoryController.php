<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function index()
    {
        return view('product_categories.index', [
            'categories' => ProductCategory::query()->with('children.children.children')->whereNull('parent_id')->orderBy('name')->get(),
            'allCategories' => ProductCategory::query()->with('parent.parent')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_categories', 'name')->where(function ($query) use ($request): void {
                    if ($request->filled('parent_id')) {
                        $query->where('parent_id', $request->input('parent_id'));

                        return;
                    }

                    $query->whereNull('parent_id');
                }),
            ],
        ]);

        ProductCategory::create($data);

        return redirect()->route('product-categories.index')->with('success', 'Product category saved successfully.');
    }
}
