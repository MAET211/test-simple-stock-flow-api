<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->char('id', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('username', 120);
            $table->string('password_hash', 512)->charset('ascii')->collation('ascii_bin');
            $table->string('role', 40)->charset('ascii')->collation('ascii_bin');

            $table->primary('id');
            $table->unique('username', 'uq_user_username');
        });

        DB::statement('ALTER TABLE `user` ADD CONSTRAINT ck_user_username_normalized CHECK (CHAR_LENGTH(username) > 0 AND CAST(username AS BINARY) = CAST(LOWER(TRIM(username)) AS BINARY))');
        DB::statement("ALTER TABLE `user` ADD CONSTRAINT ck_user_role_allowed CHECK (role IN ('admin','seller'))");
        DB::statement('ALTER TABLE `user` ADD CONSTRAINT ck_user_password_hash_not_blank CHECK (CHAR_LENGTH(password_hash) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
