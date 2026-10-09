<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $sale_id
 * @property string $product_id
 * @property string $product_name
 * @property string $category_name
 * @property int $quantity
 * @property string $unit_price
 */
final class SaleItemModel extends Model
{
    protected $table = 'sale_item';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sale_id',
        'product_id',
        'product_name',
        'category_name',
        'quantity',
        'unit_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'string',
        ];
    }

    /**
     * @return BelongsTo<SaleModel, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'sale_id', 'id');
    }

    /**
     * @return BelongsTo<ProductModel, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id', 'id');
    }
}
