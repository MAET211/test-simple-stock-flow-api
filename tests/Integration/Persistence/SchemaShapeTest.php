<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('schema five tables are innoDB and utf8mb4_0900_ai_ci', function (): void {
    $tables = DB::table('information_schema.tables')
        ->select('table_name', 'engine', 'table_collation')
        ->where('table_schema', 'stockflow_test')
        ->where('table_name', '<>', 'migrations')
        ->orderBy('table_name')
        ->get();

    expect($tables->pluck('table_name')->all())->toBe([
        'category',
        'product',
        'sale',
        'sale_item',
        'user',
    ]);

    foreach ($tables as $t) {
        expect($t->engine)->toBe('InnoDB')
            ->and($t->table_collation)->toBe('utf8mb4_0900_ai_ci');
    }
});

test('schema 25 columns exist, no defaults, deleted_at and image_key are the only nullables', function (): void {
    $columns = DB::table('information_schema.columns')
        ->select('table_name', 'column_name', 'is_nullable', 'column_default')
        ->where('table_schema', 'stockflow_test')
        ->where('table_name', '<>', 'migrations')
        ->orderBy('table_name')
        ->orderBy('ordinal_position')
        ->get();

    expect($columns)->toHaveCount(25);

    foreach ($columns as $c) {
        expect($c->column_default)->toBeNull("Column {$c->table_name}.{$c->column_name} has a default value");

        $isNullableExpected = in_array("{$c->table_name}.{$c->column_name}", ['product.deleted_at', 'product.image_key'], true);
        expect($c->is_nullable === 'YES')->toBe($isNullableExpected, "Nullable mismatch for {$c->table_name}.{$c->column_name}");
    }
});

test('schema binary collations match contract', function (): void {
    $asciiBinCols = [
        'category.id',
        'user.id',
        'user.password_hash',
        'user.role',
        'product.id',
        'product.category_id',
        'sale.id',
        'sale.sold_by_user_id',
        'sale_item.id',
        'sale_item.sale_id',
        'sale_item.product_id',
    ];

    foreach ($asciiBinCols as $colStr) {
        [$table, $col] = explode('.', $colStr);
        $res = DB::table('information_schema.columns')
            ->where('table_schema', 'stockflow_test')
            ->where('table_name', $table)
            ->where('column_name', $col)
            ->value('collation_name');

        expect($res)->toBe('ascii_bin', "Expected ascii_bin for {$colStr}");
    }

    $imageKeyCollation = DB::table('information_schema.columns')
        ->where('table_schema', 'stockflow_test')
        ->where('table_name', 'product')
        ->where('column_name', 'image_key')
        ->value('collation_name');

    expect($imageKeyCollation)->toBe('utf8mb4_bin');
});

test('schema 21 constraints match exact contract', function (): void {
    $constraints = DB::table('information_schema.table_constraints')
        ->where('table_schema', 'stockflow_test')
        ->where('table_name', '<>', 'migrations')
        ->get();

    expect($constraints)->toHaveCount(21);

    $primaryCount = $constraints->where('constraint_type', 'PRIMARY KEY')->count();
    $foreignCount = $constraints->where('constraint_type', 'FOREIGN KEY')->count();
    $uniqueCount = $constraints->where('constraint_type', 'UNIQUE')->count();
    $checkCount = $constraints->where('constraint_type', 'CHECK')->count();

    expect($primaryCount)->toBe(5)
        ->and($foreignCount)->toBe(4)
        ->and($uniqueCount)->toBe(3)
        ->and($checkCount)->toBe(9);
});

test('schema nine ck_* constraints are enforced and no extra check exists', function (): void {
    $checks = DB::table('information_schema.table_constraints')
        ->where('table_schema', 'stockflow_test')
        ->where('table_name', '<>', 'migrations')
        ->where('constraint_type', 'CHECK')
        ->orderBy('constraint_name')
        ->get();

    $expectedChecks = [
        'ck_category_name_not_blank',
        'ck_product_name_not_blank',
        'ck_product_price_positive',
        'ck_product_stock_non_negative',
        'ck_sale_item_quantity_positive',
        'ck_sale_item_unit_price_positive',
        'ck_user_password_hash_not_blank',
        'ck_user_role_allowed',
        'ck_user_username_normalized',
    ];

    expect($checks->pluck('constraint_name')->all())->toBe($expectedChecks);

    foreach ($checks as $ck) {
        expect($ck->enforced)->toBe('YES');
    }
});

test('schema four foreign keys respect contract actions', function (): void {
    $fks = DB::table('information_schema.referential_constraints')
        ->where('constraint_schema', 'stockflow_test')
        ->orderBy('constraint_name')
        ->get()
        ->keyBy('CONSTRAINT_NAME');

    expect($fks)->toHaveCount(4);

    expect($fks->get('fk_product_category_id')->DELETE_RULE)->toBe('RESTRICT')
        ->and($fks->get('fk_product_category_id')->UPDATE_RULE)->toBe('NO ACTION');

    expect($fks->get('fk_sale_sold_by_user_id')->DELETE_RULE)->toBe('RESTRICT')
        ->and($fks->get('fk_sale_sold_by_user_id')->UPDATE_RULE)->toBe('NO ACTION');

    expect($fks->get('fk_sale_item_sale_id')->DELETE_RULE)->toBe('CASCADE')
        ->and($fks->get('fk_sale_item_sale_id')->UPDATE_RULE)->toBe('NO ACTION');

    expect($fks->get('fk_sale_item_product_id')->DELETE_RULE)->toBe('RESTRICT')
        ->and($fks->get('fk_sale_item_product_id')->UPDATE_RULE)->toBe('NO ACTION');
});

test('schema 13 distinct indexes exist with expected columns in order', function (): void {
    $indexes = DB::table('information_schema.statistics')
        ->select(
            'table_name',
            'index_name',
            'non_unique',
            DB::raw('GROUP_CONCAT(column_name ORDER BY seq_in_index) as columnas')
        )
        ->where('table_schema', 'stockflow_test')
        ->where('table_name', '<>', 'migrations')
        ->groupBy('table_name', 'index_name', 'non_unique')
        ->orderBy('table_name')
        ->orderBy('index_name')
        ->get();

    expect($indexes)->toHaveCount(13);

    $saleItemSaleIdSolo = $indexes->first(fn ($idx) => $idx->table_name === 'sale_item' && $idx->columnas === 'sale_id');
    expect($saleItemSaleIdSolo)->toBeNull('No separate index on sale_item(sale_id) should exist');

    $productCatIdx = $indexes->first(fn ($idx) => $idx->index_name === 'idx_product_category_active_name');
    expect($productCatIdx)->not->toBeNull()
        ->and($productCatIdx->columnas)->toBe('category_id,deleted_at,name');

    $productActiveIdx = $indexes->first(fn ($idx) => $idx->index_name === 'idx_product_active_name');
    expect($productActiveIdx)->not->toBeNull()
        ->and($productActiveIdx->columnas)->toBe('deleted_at,name');

    $saleItemUq = $indexes->first(fn ($idx) => $idx->index_name === 'uq_sale_item_sale_product');
    expect($saleItemUq)->not->toBeNull()
        ->and($saleItemUq->columnas)->toBe('sale_id,product_id');
});

test('schema sale_item.sale_id is NOT NULL', function (): void {
    $nullable = DB::table('information_schema.columns')
        ->where('table_schema', 'stockflow_test')
        ->where('table_name', 'sale_item')
        ->where('column_name', 'sale_id')
        ->value('is_nullable');

    expect($nullable)->toBe('NO');
});

test('schema sql_mode has STRICT_TRANS_TABLES and connection timezone is UTC', function (): void {
    $sqlMode = DB::selectOne('SELECT @@sql_mode as m')->m;
    expect($sqlMode)->toContain('STRICT_TRANS_TABLES');

    $tz = DB::selectOne('SELECT @@session.time_zone as tz')->tz;
    expect($tz)->toBe('+00:00');
});
