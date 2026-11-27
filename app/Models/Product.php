<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $appends = ['available_stock'];

    protected $hidden = [
        'reserved_stock',
        'seller_id',
        'approval_status',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'approved_by',
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'category',
        'tagline',
        'description',
        'features',
        'image_url',
        'price',
        'compare_at_price',
        'billing_period',
        'stock',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock' => 'integer',
            'reserved_stock' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected function availableStock(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->stock === null
            ? null
            : max(0, (int) $this->stock - (int) $this->reserved_stock));
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('approval_status', 'approved');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
