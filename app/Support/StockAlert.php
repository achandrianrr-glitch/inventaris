<?php

namespace App\Support;

use App\Models\Item;
use App\Services\NotificationService;

class StockAlert
{
    public static function lowStock(Item $item, int $adminId, int $threshold = 5): void
    {
        if ((int)$item->stock_available < $threshold) {
            app(NotificationService::class)->createForAdmin(
                $adminId,
                'stock_low',
                'Stok menipis',
                "Stok tersedia rendah: {$item->code} — {$item->name} (available: {$item->stock_available}).",
                'item',
                (int)$item->id
            );
        }
    }
}
