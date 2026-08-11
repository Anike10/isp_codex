<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function update(Request $request, ProductCategory $productCategory)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:product_categories,id', Rule::notIn([$productCategory->id])],
            'name' => ['required', 'string', 'max:100'],
        ]);

        $parent = filled($data['parent_id'] ?? null) ? ProductCategory::findOrFail($data['parent_id']) : null;
        $ancestor = $parent;

        while ($ancestor) {
            if ($ancestor->id === $productCategory->id) {
                throw ValidationException::withMessages(['parent_id' => 'A category cannot be moved below one of its own children.']);
            }

            $ancestor = $ancestor->parent;
        }

        $duplicate = ProductCategory::query()
            ->whereKeyNot($productCategory->id)
            ->where('name', $data['name'])
            ->when($parent, fn ($query) => $query->where('parent_id', $parent->id), fn ($query) => $query->whereNull('parent_id'))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'A category with this name already exists under the selected parent.']);
        }

        $productCategory->update(['name' => $data['name'], 'parent_id' => $parent?->id]);

        return redirect()->route('product-categories.index')->with('success', 'Product category updated successfully.');
    }

    public function destroy(ProductCategory $productCategory)
    {
        if ($productCategory->children()->exists() || $productCategory->products()->exists()) {
            return back()->withErrors(['category' => 'Only an empty category with no child categories or products can be deleted.']);
        }

        $productCategory->delete();

        return redirect()->route('product-categories.index')->with('success', 'Product category deleted successfully.');
    }
}
