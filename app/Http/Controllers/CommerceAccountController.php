<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CommerceAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with('items:id,order_id,name,quantity,line_total')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Order $order): array => $this->orderSummary($order))
            ->values();

        return Inertia::render('Commerce/BuyerDashboard', [
            'orders' => $orders,
            'stats' => [
                'orders' => $orders->count(),
                'active' => $orders->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'spent' => (float) $orders->where('payment_status', 'paid')->sum('total'),
            ],
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $user = $request->user();
        abort_unless($user->isCommerceAdmin() || (int) $order->user_id === (int) $user->id, 404);

        $order->load('items:id,order_id,name,type,unit_price,quantity,line_total');

        return Inertia::render('Commerce/Order', [
            'order' => [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'customer_note' => $order->customer_note,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'total' => $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
                'confirmation_url' => $order->confirmationUrl(),
                'items' => $order->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                ])->values(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => $order->total,
            'created_at' => $order->created_at?->toIso8601String(),
            'confirmation_url' => $order->confirmationUrl(),
            'items' => $order->items->map(fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->values(),
        ];
    }
}
