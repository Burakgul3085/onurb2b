<?php

namespace App\Http\Controllers;

use App\Actions\Stock\SaveWarehouse;
use App\Http\Requests\Stock\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        return view('warehouses.index', [
            'warehouses' => Warehouse::query()->orderBy('name')->paginate(15),
            'canManage' => request()->user()?->can('create', Warehouse::class) ?? false,
        ]);
    }

    public function store(WarehouseRequest $request, SaveWarehouse $saveWarehouse): RedirectResponse
    {
        $saveWarehouse->execute($request->validated());

        return redirect()->route('warehouses.index')->with('status', __('Warehouse saved.'));
    }

    public function edit(Warehouse $warehouse): View
    {
        $this->authorize('update', $warehouse);

        return view('warehouses.edit', ['warehouse' => $warehouse]);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse, SaveWarehouse $saveWarehouse): RedirectResponse
    {
        $saveWarehouse->execute($request->validated(), $warehouse);

        return redirect()->route('warehouses.index')->with('status', __('Warehouse saved.'));
    }
}
