<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Adapters\Out\Persistence\Repositories;

use App\Core\Application\Exceptions\UsernameTaken;
use App\Core\Application\Ports\Out\UserRepository;
use App\Core\Domain\User;
use App\Core\Domain\ValueObjects\UserId;
use App\Core\Infrastructure\Adapters\Out\Persistence\Models\UserModel;
use Illuminate\Database\QueryException;

final class EloquentUserRepository implements UserRepository
{
    public function findByUsername(string $username): ?User
    {
        $normalized = User::normalizeUsername($username);
        /** @var UserModel|null $model */
        $model = UserModel::query()->where('username', $normalized)->first();
        if ($model === null) {
            return null;
        }

        return User::reconstitute(
            UserId::fromString((string) $model->id),
            (string) $model->username,
            (string) $model->password_hash,
            (string) $model->role
        );
    }

    public function add(User $user): void
    {
        try {
            UserModel::query()->create([
                'id' => $user->id()->value(),
                'username' => $user->username(),
                'password_hash' => $user->passwordHash(),
                'role' => $user->role(),
            ]);
        } catch (QueryException $e) {
            // MySQL error 1062 = Duplicate entry
            if ((int) $e->getCode() === 23000 || str_contains($e->getMessage(), '1062')) {
                throw new UsernameTaken($user->username());
            }

            throw $e;
        }
    }
}
