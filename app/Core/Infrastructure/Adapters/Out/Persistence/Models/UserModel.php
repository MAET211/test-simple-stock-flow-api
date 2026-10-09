<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $username
 * @property string $password_hash
 * @property string $role
 */
final class UserModel extends Model
{
    protected $table = 'user';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'username',
        'password_hash',
        'role',
    ];
}
