<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ImpactCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminImpactCategoryController extends Controller
{
    public function index(): View
    {
        return view('modules.esg.admin.categories.index', [
            'categories' => ImpactCategory::withCount('metrics')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('modules.esg.admin.categories.form', ['category' => new ImpactCategory(['weight' => 0.25])]);
    }

    public function store(Request $request): RedirectResponse
    {
        ImpactCategory::create($this->validated($request));

        return to_route('admin.impact-categories.index')->with('success', 'Catégorie d’impact créée.');
    }

    public function edit(ImpactCategory $impactCategory): View
    {
        return view('modules.esg.admin.categories.form', ['category' => $impactCategory]);
    }

    public function update(Request $request, ImpactCategory $impactCategory): RedirectResponse
    {
        $impactCategory->update($this->validated($request, $impactCategory));

        return to_route('admin.impact-categories.index')->with('success', 'Catégorie d’impact mise à jour.');
    }

    public function destroy(ImpactCategory $impactCategory): RedirectResponse
    {
        $impactCategory->delete();

        return back()->with('success', 'Catégorie supprimée avec ses métriques.');
    }

    private function validated(Request $request, ?ImpactCategory $category = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('impact_categories')->ignore($category?->id)],
            'name' => 'required|string|max:100',
            'unit' => 'required|string|max:30',
            'weight' => 'required|numeric|min:0|max:1',
            'description' => 'nullable|string|max:1000',
        ]);
    }
}
