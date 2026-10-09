<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $sold_at
 * @property string $sold_by_username
 * @property string $sold_by_user_id
 */
final class SaleModel extends Model
{
    protected $table = 'sale';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sold_at',
        'sold_by_username',
        'sold_by_user_id',
    ];

    /**
     * @return HasMany<SaleItemModel, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItemModel::class, 'sale_id', 'id');
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'sold_by_user_id', 'id');
    }
}
