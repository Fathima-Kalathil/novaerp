<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\StockService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::with('category:id,name')
            ->search($request->input('search'), ['name', 'sku'])
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->input('category')))
            ->when($request->input('status') === 'low', fn ($q) => $q->whereColumn('stock_on_hand', '<=', 'reorder_level'))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'category', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            'product' => null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'movements' => [],
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $data = $this->validated($request);
        $opening = (int) ($request->input('initial_stock') ?? 0);
        $request->validate(['initial_stock' => ['nullable', 'integer', 'min:0', 'max:1000000']]);

        $product = Product::create($data + ['stock_on_hand' => 0]);

        if ($opening > 0) {
            $stock->adjust($product, $opening, 'Opening stock', $request->user()->id);
        }

        return redirect('/products')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'movements' => $product->movements()->with('creator:id,name')->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect('/products')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect('/products')->with('success', 'Product deleted (kept in history).');
    }

    public function adjust(Request $request, Product $product, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $stock->adjust($product, (int) $data['quantity'], $data['note'] ?? null, $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stock adjusted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['boolean'],
        ]);
    }
}
