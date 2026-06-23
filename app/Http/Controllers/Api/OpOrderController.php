<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpOrder;
use App\Models\OpFitting;
use Illuminate\Http\Request;

class OpOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = OpOrder::query()
            ->with('patient:id,name,op_number')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('order_type'), fn($q) => $q->where('order_type', $request->order_type))
            ->when($request->filled('patient_id'), fn($q) => $q->where('patient_id', $request->patient_id))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'    => 'required|exists:patients,id',
            'order_type'    => 'required|in:orthotic,prosthetic',
            'device_name'   => 'required|string|max:255',
            'affected_limb' => 'nullable|string|max:100',
            'measurements'  => 'nullable|array',
            'scan_file_url' => 'nullable|string',
            'cad_file_url'  => 'nullable|string',
            'material'      => 'nullable|string|max:100',
            'fitting_date'  => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'cost'          => 'nullable|numeric|min:0',
            'status'        => 'nullable|in:ordered,fabricating,fitting,delivered',
            'notes'         => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        $data['status'] = $data['status'] ?? 'ordered';
        return response()->json(OpOrder::create($data)->load('patient:id,name,op_number'), 201);
    }

    public function show(OpOrder $op_order)
    {
        return response()->json($op_order->load(['patient', 'fittings' => fn($q) => $q->latest('fitting_date')]));
    }

    public function update(Request $request, OpOrder $op_order)
    {
        $data = $request->validate([
            'device_name'   => 'sometimes|required|string|max:255',
            'order_type'    => 'nullable|in:orthotic,prosthetic',
            'affected_limb' => 'nullable|string|max:100',
            'measurements'  => 'nullable|array',
            'scan_file_url' => 'nullable|string',
            'cad_file_url'  => 'nullable|string',
            'material'      => 'nullable|string|max:100',
            'fitting_date'  => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'cost'          => 'nullable|numeric|min:0',
            'status'        => 'nullable|in:ordered,fabricating,fitting,delivered',
            'notes'         => 'nullable|string',
        ]);

        $op_order->update($data);
        return response()->json($op_order);
    }

    public function destroy(OpOrder $op_order)
    {
        $op_order->delete();
        return response()->json(['deleted' => true]);
    }

    /** Record a fitting session against an O&P order. */
    public function addFitting(Request $request, OpOrder $op_order)
    {
        $data = $request->validate([
            'fitting_date'      => 'required|date',
            'adjustments_made'  => 'nullable|string',
            'outcome'           => 'nullable|string',
            'next_fitting_date' => 'nullable|date',
        ]);

        $data['op_order_id'] = $op_order->id;
        $data['patient_id']  = $op_order->patient_id;
        $data['created_by']  = auth()->id();

        $fitting = OpFitting::create($data);

        // Move the order into the fitting stage if still upstream.
        if (in_array($op_order->status, ['ordered', 'fabricating'], true)) {
            $op_order->update(['status' => 'fitting']);
        }

        return response()->json($fitting, 201);
    }
}
