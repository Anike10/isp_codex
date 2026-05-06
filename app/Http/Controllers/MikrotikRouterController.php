<?php

namespace App\Http\Controllers;

use App\Models\MikrotikRouter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MikrotikRouterController extends Controller
{
    public function index(Request $request)
    {
        $routers = MikrotikRouter::query()
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('mikrotik_routers.index', compact('routers'));
    }

    public function create()
    {
        return view('mikrotik_routers.create');
    }

    public function store(Request $request)
    {
        MikrotikRouter::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip', 'max:45', 'unique:mikrotik_routers,ip_address'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('mikrotik-routers.index')->with('success', 'MikroTik router added successfully.');
    }

    public function show(MikrotikRouter $mikrotikRouter)
    {
        return view('mikrotik_routers.show', compact('mikrotikRouter'));
    }

    public function edit(MikrotikRouter $mikrotikRouter)
    {
        return view('mikrotik_routers.edit', compact('mikrotikRouter'));
    }

    public function update(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip', 'max:45', Rule::unique('mikrotik_routers', 'ip_address')->ignore($mikrotikRouter->id)],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $mikrotikRouter->update($data);

        return redirect()->route('mikrotik-routers.show', $mikrotikRouter)->with('success', 'MikroTik router updated successfully.');
    }
}
