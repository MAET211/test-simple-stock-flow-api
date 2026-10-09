<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';

            $table->char('id', 36)->charset('ascii')->collation('ascii_bin');
            $table->dateTime('sold_at', 6);
            $table->string('sold_by_username', 120);
            $table->char('sold_by_user_id', 36)->charset('ascii')->collation('ascii_bin');

            $table->primary('id');
            $table->index('sold_at', 'idx_sale_sold_at');
            $table->index('sold_by_user_id', 'idx_sale_sold_by_user_id');

            $table->foreign('sold_by_user_id', 'fk_sale_sold_by_user_id')
                ->references('id')->on('user')->restrictOnDelete()->noActionOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale');
    }
};
