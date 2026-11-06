<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $term = mb_strtolower(trim((string) $request->input('search')));

        $invoices = Invoice::with('order.customer:id,name')
            ->when($term !== '', fn ($q) => $q->whereRaw('LOWER(invoice_number) LIKE ?', ['%'.$term.'%']))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['order.customer', 'order.items.product']),
            'company' => ['name' => config('app.name'), 'address' => 'Abu Dhabi, United Arab Emirates'],
        ]);
    }

    public function pay(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'unpaid') {
            return back()->with('error', 'Only unpaid invoices can be marked as paid.');
        }

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('success', 'Invoice marked as paid.');
    }
}
