<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('schema five tables are innoDB and utf8mb4_0900_ai_ci', function (): void {
    $tables = DB::table('information_schema.tables')
        ->select(
            'TABLE_NAME as table_name',
            'ENGINE as engine',
            'TABLE_COLLATION as table_collation'
        )
        ->where('TABLE_SCHEMA', 'stockflow_test')
        ->where('TABLE_NAME', '<>', 'migrations')
        ->orderBy('TABLE_NAME')
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
        ->select(
            'TABLE_NAME as table_name',
            'COLUMN_NAME as column_name',
            'IS_NULLABLE as is_nullable',
            'COLUMN_DEFAULT as column_default'
        )
        ->where('TABLE_SCHEMA', 'stockflow_test')
        ->where('TABLE_NAME', '<>', 'migrations')
        ->orderBy('TABLE_NAME')
        ->orderBy('ORDINAL_POSITION')
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
            ->where('TABLE_SCHEMA', 'stockflow_test')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $col)
            ->value('COLLATION_NAME');

        expect($res)->toBe('ascii_bin', "Expected ascii_bin for {$colStr}");
    }

    $imageKeyCollation = DB::table('information_schema.columns')
        ->where('TABLE_SCHEMA', 'stockflow_test')
        ->where('TABLE_NAME', 'product')
        ->where('COLUMN_NAME', 'image_key')
        ->value('COLLATION_NAME');

    expect($imageKeyCollation)->toBe('utf8mb4_bin');
});

test('schema 21 constraints match exact contract', function (): void {
    $constraints = DB::table('information_schema.table_constraints')
        ->select(
            'TABLE_NAME as table_name',
            'CONSTRAINT_NAME as constraint_name',
            'CONSTRAINT_TYPE as constraint_type'
        )
        ->where('TABLE_SCHEMA', 'stockflow_test')
        ->where('TABLE_NAME', '<>', 'migrations')
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
        ->select(
            'CONSTRAINT_NAME as constraint_name',
            'ENFORCED as enforced'
        )
        ->where('TABLE_SCHEMA', 'stockflow_test')
        ->where('TABLE_NAME', '<>', 'migrations')
        ->where('CONSTRAINT_TYPE', 'CHECK')
        ->orderBy('CONSTRAINT_NAME')
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
        ->select(
            'CONSTRAINT_NAME as constraint_name',
            'DELETE_RULE as delete_rule',
            'UPDATE_RULE as update_rule'
        )
        ->where('CONSTRAINT_SCHEMA', 'stockflow_test')
        ->orderBy('CONSTRAINT_NAME')
        ->get()
        ->keyBy('constraint_name');

    expect($fks)->toHaveCount(4);

    expect($fks->get('fk_product_category_id')->delete_rule)->toBe('RESTRICT')
        ->and($fks->get('fk_product_category_id')->update_rule)->toBe('NO ACTION');

    expect($fks->get('fk_sale_sold_by_user_id')->delete_rule)->toBe('RESTRICT')
        ->and($fks->get('fk_sale_sold_by_user_id')->update_rule)->toBe('NO ACTION');

    expect($fks->get('fk_sale_item_sale_id')->delete_rule)->toBe('CASCADE')
        ->and($fks->get('fk_sale_item_sale_id')->update_rule)->toBe('NO ACTION');

    expect($fks->get('fk_sale_item_product_id')->delete_rule)->toBe('RESTRICT')
        ->and($fks->get('fk_sale_item_product_id')->update_rule)->toBe('NO ACTION');
});

test('schema 13 distinct indexes exist with expected columns in order', function (): void {
    $indexes = DB::table('information_schema.statistics')
        ->select(
            'TABLE_NAME as table_name',
            'INDEX_NAME as index_name',
            'NON_UNIQUE as non_unique',
            DB::raw('GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columnas')
        )
        ->where('TABLE_SCHEMA', 'stockflow_test')
        ->where('TABLE_NAME', '<>', 'migrations')
        ->groupBy('TABLE_NAME', 'INDEX_NAME', 'NON_UNIQUE')
        ->orderBy('TABLE_NAME')
        ->orderBy('INDEX_NAME')
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
        ->where('TABLE_SCHEMA', 'stockflow_test')
        ->where('TABLE_NAME', 'sale_item')
        ->where('COLUMN_NAME', 'sale_id')
        ->value('IS_NULLABLE');

    expect($nullable)->toBe('NO');
});

test('schema sql_mode has STRICT_TRANS_TABLES and connection timezone is UTC', function (): void {
    $sqlMode = DB::selectOne('SELECT @@sql_mode as m')->m;
    expect($sqlMode)->toContain('STRICT_TRANS_TABLES');

    $tz = DB::selectOne('SELECT @@session.time_zone as tz')->tz;
    expect($tz)->toBe('+00:00');
});
