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
        Schema::create('sale_item', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->char('id', 36)->charset('ascii')->collation('ascii_bin');
            $table->char('sale_id', 36)->charset('ascii')->collation('ascii_bin');
            $table->char('product_id', 36)->charset('ascii')->collation('ascii_bin');
            $table->string('product_name', 200);
            $table->string('category_name', 120);
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);

            $table->primary('id');
            $table->unique(['sale_id', 'product_id'], 'uq_sale_item_sale_product');
            $table->index('product_id', 'idx_sale_item_product_id');

            $table->foreign('sale_id', 'fk_sale_item_sale_id')
                ->references('id')->on('sale')->cascadeOnDelete()->noActionOnUpdate();
            $table->foreign('product_id', 'fk_sale_item_product_id')
                ->references('id')->on('product')->restrictOnDelete()->noActionOnUpdate();
        });

        DB::statement('ALTER TABLE `sale_item` ADD CONSTRAINT ck_sale_item_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE `sale_item` ADD CONSTRAINT ck_sale_item_unit_price_positive CHECK (unit_price > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_item');
    }
};
