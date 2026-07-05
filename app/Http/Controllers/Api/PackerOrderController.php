<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class PackerOrderController extends Controller
{
    public function index()
    {
        return Order::with(['items.product'])
            ->whereIn('status', ['pending', 'packing', 'issue'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->orderBy('pickup_deadline')
            ->paginate(20);
    }

    public function show(Order $order)
    {
        return $order->load(['items.product', 'photos']);
    }

    public function start(Request $request, Order $order)
    {
        if (!in_array($order->status, ['pending', 'issue'])) {
            return response()->json([
                'message' => 'This order cannot be started.',
            ], 422);
        }

        $fromStatus = $order->status;

        $order->update([
            'status' => 'packing',
            'packer_id' => optional($request->user())->id,
            'packing_started_at' => now(),
        ]);

        $order->logStatus($fromStatus, 'packing', optional($request->user())->id, 'Packing started');

        return $order->load(['items.product', 'photos']);
    }

    public function confirmItem(Request $request, OrderItem $item)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'packer_note' => ['nullable', 'string'],
        ]);

        $order = $item->order;

        if ($order->status !== 'packing') {
            return response()->json([
                'message' => 'Order is not in packing status.',
            ], 422);
        }

        if ($item->is_confirmed) {
            return response()->json([
                'message' => 'This item is already fully confirmed.',
            ], 422);
        }

        $item->markConfirmed($data['quantity']);

        if (!empty($data['packer_note'])) {
            $item->update([
                'packer_note' => $data['packer_note'],
            ]);
        }

        return $item->fresh()->load('product');
    }

    public function uploadPhoto(Request $request, Order $order)
    {
        if ($order->status !== 'packing') {
            return response()->json([
                'message' => 'Photo can be uploaded only during packing.',
            ], 422);
        }

        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'note' => ['nullable', 'string'],
        ]);

        $path = $request->file('photo')->store('packing-photos', 'public');

        $photo = $order->photos()->create([
            'uploaded_by' => optional($request->user())->id,
            'photo_path' => $path,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json($photo, 201);
    }

    public function complete(Request $request, Order $order)
    {
        if ($order->status !== 'packing') {
            return response()->json([
                'message' => 'Only orders in packing can be completed.',
            ], 422);
        }

        if (!$order->isFullyConfirmed()) {
            return response()->json([
                'message' => 'All items must be confirmed before completing packing.',
            ], 422);
        }

        if (!$order->hasPackingPhoto()) {
            return response()->json([
                'message' => 'At least one open-box photo is required.',
            ], 422);
        }

        $fromStatus = $order->status;

        $order->update([
            'status' => 'packed',
            'packed_at' => now(),
        ]);

        $order->logStatus($fromStatus, 'packed', optional($request->user())->id, 'Packing completed');

        return $order->load(['items.product', 'photos']);
    }

    public function flagIssue(Request $request, Order $order)
    {
        if ($order->status !== 'packing') {
            return response()->json([
                'message' => 'Only orders currently being packed can be flagged with an issue.',
            ], 422);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $fromStatus = $order->status;

        $order->update([
            'status' => 'issue',
        ]);

        $order->logStatus($fromStatus, 'issue', optional($request->user())->id, $data['reason']);

        return $order->load(['items.product', 'photos']);
    }
}
