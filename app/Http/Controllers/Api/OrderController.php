<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {

        $orders = Order::with(['items.product', 'photos'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->platform, function ($query, $platform) {
                $query->where('platform', $platform);
            })
            ->latest()
            ->paginate(20);

        return $orders;
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:255', 'unique:orders,order_number'],
            'platform' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['nullable', 'string'],
            'pickup_deadline' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'shipping_label' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sku' => ['nullable', 'string', 'max:255'],
            'items.*.quantity_required' => ['required', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $order = Order::create([
                'order_number' => $data['order_number'],
                'platform' => $data['platform'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'pickup_deadline' => $data['pickup_deadline'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'shipping_label' => $data['shipping_label'] ?? null,
                'status' => 'pending',
                'created_by' => optional($request->user())->id,
            ]);

            foreach ($data['items'] as $item) {
                $product = null;

                if (!empty($item['sku'])) {
                    $product = Product::firstOrCreate(
                        ['sku' => $item['sku']],
                        [
                            'name' => $item['product_name'],
                        ]
                    );
                }

                if (!$product) {
                    $product = Product::firstOrCreate(
                        ['name' => $item['product_name']],
                        [
                            'sku' => $item['sku'] ?? null,
                        ]
                    );
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'] ?? null,
                    'quantity_required' => $item['quantity_required'],
                    'quantity_confirmed' => 0,
                    'is_confirmed' => false,
                ]);
            }

            $order->logStatus(null, 'pending', optional($request->user())->id, 'Order created');

            return $order;
        });

        return response()->json($order->load(['items.product', 'photos']), 201);
    }

    public function show(Order $order)
    {
        return $order->load(['items.product', 'photos.uploader', 'statusLogs.changedBy', 'packer', 'creator']);
    }

    public function update(Request $request, Order $order)
    {
        if (!in_array($order->status, ['pending', 'issue'])) {
            return response()->json([
                'message' => 'Only pending or issue orders can be edited.',
            ], 422);
        }

        $data = $request->validate([
            'platform' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'shipping_address' => ['nullable', 'string'],
            'pickup_deadline' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'shipping_label' => ['nullable', 'string'],
        ]);

        $order->update($data);

        return $order->load(['items.product', 'photos']);
    }

    public function destroy(Order $order)
    {
        if (!in_array($order->status, ['pending', 'cancelled'])) {
            return response()->json([
                'message' => 'Only pending or cancelled orders can be deleted.',
            ], 422);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }

    public function markReadyToShip(Request $request, Order $order)
    {
        if ($order->status !== 'packed') {
            return response()->json([
                'message' => 'Only packed orders can be marked ready to ship.',
            ], 422);
        }

        $fromStatus = $order->status;

        $order->update([
            'status' => 'ready_to_ship',
            'ready_to_ship_at' => now(),
        ]);

        $order->logStatus($fromStatus, 'ready_to_ship', optional($request->user())->id, 'Admin marked ready to ship');

        return $order->load(['items.product', 'photos']);
    }

    public function markShipped(Request $request, Order $order)
    {
        if ($order->status !== 'ready_to_ship') {
            return response()->json([
                'message' => 'Only ready to ship orders can be marked shipped.',
            ], 422);
        }

        $fromStatus = $order->status;

        $order->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        $order->logStatus($fromStatus, 'shipped', optional($request->user())->id, 'Admin marked shipped');

        return $order->load(['items.product', 'photos']);
    }
}
