<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $name
 * @property string $price
 * @property int $stock
 * @property string $category_id
 * @property string|null $image_key
 * @property string|null $deleted_at
 * @property int $version
 */
final class ProductModel extends Model
{
    protected $table = 'product';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'price',
        'stock',
        'category_id',
        'image_key',
        'deleted_at',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'string',
            'stock' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CategoryModel, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id');
    }
}
