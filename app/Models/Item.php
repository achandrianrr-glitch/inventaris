<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'brand_id',
        'location_id',
        'specification',
        'purchase_year',
        'purchase_price',
        'stock_total',
        'stock_available',
        'stock_borrowed',
        'stock_damaged',
        'condition',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'purchase_year' => 'integer',

            // opsional tapi aman: biar angka stok kebaca integer konsisten
            'stock_total' => 'integer',
            'stock_available' => 'integer',
            'stock_borrowed' => 'integer',
            'stock_damaged' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function damages()
    {
        return $this->hasMany(Damage::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }
}
