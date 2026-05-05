<?php

namespace App\Http\Controllers;

use App\Models\InternetPackage;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        return view('packages.index', [
            'packages' => InternetPackage::latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return view('packages.create');
    }

    public function store(Request $request)
    {
        InternetPackage::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'speed' => ['required', 'string', 'max:100'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]));

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }
}
