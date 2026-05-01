<?php

namespace App\Http\Controllers;

use App\Enum\AreaEnum;
use App\Models\StockMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StockMaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $materials = StockMaterial::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function (Builder $builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('quote_id', 'like', $like)
                        ->orWhere('area', 'like', $like);
                });
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (StockMaterial $material) => $this->serialize($material))
            ->values();

        return Inertia::render('StockMaterial/Index', [
            'materials' => $materials,
            'filters' => ['search' => $search],
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('StockMaterial/Create', [
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $material = StockMaterial::create($this->validated($request) + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('stock-material.edit', $material)
            ->with('success', 'Stock material created successfully.');
    }

    public function edit(StockMaterial $stockMaterial): Response
    {
        return Inertia::render('StockMaterial/Edit', [
            'material' => $this->serialize($stockMaterial),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
        ]);
    }

    public function update(Request $request, StockMaterial $stockMaterial): RedirectResponse
    {
        $stockMaterial->update($this->validated($request) + [
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('stock-material.edit', $stockMaterial)
            ->with('success', 'Stock material updated successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'area' => ['nullable', 'string', Rule::in(array_column(AreaEnum::cases(), 'value'))],
            'requested_date' => ['nullable', 'date_format:Y-m-d'],
            'quote_id' => ['nullable', 'string', 'max:255'],
            'quote_id_received_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
    }

    private function serialize(StockMaterial $material): array
    {
        return [
            'id' => $material->id,
            'name' => $material->name,
            'description' => $material->description,
            'cost' => $material->cost,
            'area' => $material->area,
            'requested_date' => optional($material->requested_date)->format('Y-m-d'),
            'quote_id' => $material->quote_id,
            'quote_id_received_date' => optional($material->quote_id_received_date)->format('Y-m-d'),
            'created_at' => optional($material->created_at)->toISOString(),
            'updated_at' => optional($material->updated_at)->toISOString(),
        ];
    }
}
