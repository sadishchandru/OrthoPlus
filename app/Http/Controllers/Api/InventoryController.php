<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineStock;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $medicines = Medicine::with(['stock'])
            ->where('status', 'active')
            ->when($request->q, fn($q, $search) => $q->where('name', like_operator(), "%$search%")
                ->orWhere('generic_name', like_operator(), "%$search%"))
            ->paginate(30);

        return response()->json($medicines);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'medicine_id'     => 'required|exists:medicines,id',
            'type'            => 'required|in:in,out',
            'qty'             => 'required|integer|min:1',
            'reason'          => 'nullable|string',
            'ref_id'          => 'nullable|integer',
            'batch_number'    => 'nullable|string',
            'expiry_date'     => 'nullable|date',
            'storage_location'=> 'nullable|string',
        ]);

        DB::transaction(function () use ($data) {
            // Update or create stock record
            if ($data['type'] === 'in') {
                $stock = MedicineStock::firstOrNew([
                    'medicine_id'  => $data['medicine_id'],
                    'batch_number' => $data['batch_number'] ?? 'DEFAULT',
                ]);
                $stock->quantity_in_stock = ($stock->quantity_in_stock ?? 0) + $data['qty'];
                if (!empty($data['expiry_date'])) $stock->expiry_date = $data['expiry_date'];
                if (!empty($data['storage_location'])) $stock->storage_location = $data['storage_location'];
                $stock->save();
            } else {
                // Deduct from available stock
                $stock = MedicineStock::where('medicine_id', $data['medicine_id'])
                    ->where('quantity_in_stock', '>', 0)
                    ->first();

                if (!$stock || $stock->quantity_in_stock < $data['qty']) {
                    abort(422, 'Insufficient stock.');
                }

                $stock->decrement('quantity_in_stock', $data['qty']);
            }

            InventoryLog::create([
                'medicine_id' => $data['medicine_id'],
                'type'        => $data['type'],
                'qty'         => $data['qty'],
                'reason'      => $data['reason'] ?? null,
                'ref_id'      => $data['ref_id'] ?? null,
                'created_by'  => Auth::id(),
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function logs(Request $request)
    {
        $logs = InventoryLog::with('medicine:id,name,generic_name')
            ->when($request->medicine_id, fn($q) => $q->where('medicine_id', $request->medicine_id))
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($logs);
    }
}
