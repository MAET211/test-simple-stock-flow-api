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
        Schema::create('product', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->char('id', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('name', 200);
            $table->decimal('price', 12, 2);
            $table->integer('stock');
            $table->char('category_id', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('image_key', 512)->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
            $table->dateTime('deleted_at', 6)->nullable();
            $table->integer('version');

            $table->primary('id');
            $table->index(['category_id', 'deleted_at', 'name'], 'idx_product_category_active_name');
            $table->index(['deleted_at', 'name'], 'idx_product_active_name');

            $table->foreign('category_id', 'fk_product_category_id')
                ->references('id')->on('category')->restrictOnDelete()->noActionOnUpdate();
        });

        DB::statement('ALTER TABLE `product` ADD CONSTRAINT ck_product_name_not_blank CHECK (CHAR_LENGTH(TRIM(name)) > 0)');
        DB::statement('ALTER TABLE `product` ADD CONSTRAINT ck_product_price_positive CHECK (price > 0)');
        DB::statement('ALTER TABLE `product` ADD CONSTRAINT ck_product_stock_non_negative CHECK (stock >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
