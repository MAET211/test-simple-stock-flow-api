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
        Schema::create('category', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->char('id', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 120);

            $table->primary('id');
            $table->unique('name', 'uq_category_name');
        });

        DB::statement('ALTER TABLE `category` ADD CONSTRAINT ck_category_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};
