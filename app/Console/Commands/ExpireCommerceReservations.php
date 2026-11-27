<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Commerce\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireCommerceReservations extends Command
{
    protected $signature = 'commerce:expire-reservations {--limit=100 : Maximum number of orders to expire in one run}';

    protected $description = 'Release inventory reservations for expired unpaid commerce orders';

    public function handle(InventoryService $inventory): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $expired = 0;

        Order::query()
            ->where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->each(function (int $orderId) use ($inventory, &$expired): void {
                DB::transaction(function () use ($inventory, $orderId, &$expired): void {
                    $order = Order::query()->lockForUpdate()->find($orderId);

                    if (! $order
                        || $order->status !== 'pending'
                        || $order->payment_status !== 'unpaid'
                        || ! $order->reservation_expires_at?->isPast()) {
                        return;
                    }

                    $inventory->release($order);
                    $order->forceFill([
                        'status' => 'cancelled',
                        'reservation_expires_at' => null,
                    ])->save();
                    $expired++;
                });
            });

        $this->info("Expired {$expired} commerce reservation(s).");

        return self::SUCCESS;
    }
}
