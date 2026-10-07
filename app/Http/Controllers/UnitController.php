<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\SaveUnit;
use App\Http\Requests\Catalog\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageCatalog');

        return view('units.index', [
            'units' => Unit::query()->with('parent')->orderBy('name')->get(),
        ]);
    }

    public function store(UnitRequest $request, SaveUnit $saveUnit): RedirectResponse
    {
        $saveUnit->execute($request->validated());

        return redirect()->route('units.index')->with('status', __('Unit saved.'));
    }

    public function edit(Unit $unit): View
    {
        $this->authorize('manageCatalog');

        return view('units.edit', [
            'unit' => $unit,
            'units' => Unit::query()->whereKeyNot($unit->id)->orderBy('name')->get(),
        ]);
    }

    public function update(UnitRequest $request, Unit $unit, SaveUnit $saveUnit): RedirectResponse
    {
        $saveUnit->execute($request->validated(), $unit);

        return redirect()->route('units.index')->with('status', __('Unit saved.'));
    }
}
