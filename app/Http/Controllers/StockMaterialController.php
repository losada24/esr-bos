<?php

namespace App\Http\Controllers;

use App\Enum\AreaEnum;
use App\Exports\StockMaterialExport;
use App\Models\StockMaterial;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class StockMaterialController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('StockMaterial/Index', $this->buildIndexData($request, 100));
    }

    public function pdf(Request $request)
    {
        $pdf = Pdf::loadView('pdf.stock-material', $this->buildIndexData($request))
            ->setPaper('A4', 'landscape');

        return $pdf->stream('stock-material-report.pdf');
    }

    public function excel(Request $request)
    {
        return Excel::download(
            new StockMaterialExport($this->buildIndexData($request)),
            'Stock Material Report.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function create(): Response
    {
        return Inertia::render('StockMaterial/Create', [
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $material = StockMaterial::create($this->normalizeDates($this->validated($request)) + [
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
        $stockMaterial->update($this->normalizeDates($this->validated($request)) + [
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('stock-material.edit', $stockMaterial)
            ->with('success', 'Stock material updated successfully.');
    }

    public function destroy(StockMaterial $stockMaterial): RedirectResponse
    {
        $stockMaterial->delete();

        return redirect()->route('stock-material.index')
            ->with('success', 'Stock material deleted successfully.');
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

    private function buildIndexData(Request $request, ?int $limit = null): array
    {
        $search = trim((string) $request->query('search', ''));

        $query = StockMaterial::query()
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
            ->latest();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return [
            'materials' => $query
                ->get()
                ->map(fn (StockMaterial $material) => $this->serialize($material))
                ->values(),
            'filters' => ['search' => $search],
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
        ];
    }

    private function normalizeDates(array $data): array
    {
        if (
            ! empty($data['quote_id'])
            && empty($data['quote_id_received_date'])
        ) {
            $data['quote_id_received_date'] = now()->toDateString();
        }

        return $data;
    }

    private function serialize(StockMaterial $material): array
    {
        return [
            'id' => $material->id,
            'name' => $material->name,
            'description' => $material->description,
            'cost' => $material->cost,
            'area' => $material->area,
            'requested_date' => $this->formatDate($material->requested_date),
            'quote_id' => $material->quote_id,
            'quote_id_received_date' => $this->formatDate($material->quote_id_received_date),
            'created_at' => optional($material->created_at)->toISOString(),
            'updated_at' => optional($material->updated_at)->toISOString(),
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse((string) $value)->format('Y-m-d');
    }
}
