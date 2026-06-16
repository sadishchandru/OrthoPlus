<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty'    => 'required|numeric|min:1',
            'items.*.rate'   => 'required|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'tax'            => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'insurance_claim'=> 'nullable|array',
            'series'         => 'nullable|string',
            'type'           => 'nullable|in:clinical,pharmacy',
        ]);

        $series = $data['series'] ?? 'INV';
        $items = collect($data['items'])->map(function ($item) {
            return array_merge($item, ['amount' => $item['qty'] * $item['rate']]);
        })->all();

        $subtotal = collect($items)->sum('amount');
        $discount = $data['discount'] ?? 0;
        $tax      = $data['tax'] ?? 0;
        $total    = $subtotal - $discount + $tax;

        $invoice = Invoice::create([
            'patient_id'     => $data['patient_id'],
            'series'         => $series,
            'type'           => $data['type'] ?? 'clinical',
            'invoice_no'     => Invoice::generateInvoiceNo($series),
            'items'          => $items,
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'tax'            => $tax,
            'total'          => $total,
            'status'         => 'pending',
            'payment_method' => $data['payment_method'] ?? null,
            'insurance_claim'=> $data['insurance_claim'] ?? null,
            'created_by'     => Auth::id(),
        ]);

        return response()->json($invoice, 201);
    }

    public function print(Invoice $invoice)
    {
        $invoice->load('patient');
        return response()->json([
            'invoice' => $invoice,
            'clinic'  => config('clinic', [
                'name'    => 'OrthoPlus Clinic',
                'address' => '',
                'phone'   => '',
            ]),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status'         => 'nullable|in:paid,due,pending',
            'payment_method' => 'nullable|string',
        ]);

        $invoice->update($data);

        return response()->json($invoice);
    }
}
