<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKUP_TABLE = 'categories_corrupt_backup_20260731';

    private const RECOVERY_TABLE = 'categories_recovered_20260731';

    public function up(): void
    {
        $usedCategoryIds = Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')
            ? DB::table('products')->whereNotNull('category_id')->distinct()->pluck('category_id')->map(fn ($id) => (int) $id)->all()
            : [];

        if (! Schema::hasTable('categories')) {
            $this->createCategoriesTable('categories');
            $this->seedRecoveredCategories('categories', $usedCategoryIds);
            $this->ensureProductCategoryForeignKey();

            return;
        }

        if (! $this->categoriesAreCorrupted()) {
            if (DB::table('categories')->count() === 0 && $usedCategoryIds !== []) {
                $this->seedRecoveredCategories('categories', $usedCategoryIds);
            }

            $this->ensureProductCategoryForeignKey();

            return;
        }

        if (Schema::hasTable(self::BACKUP_TABLE)) {
            throw new \RuntimeException('Bảng backup categories đã tồn tại. Dừng phục hồi để tránh ghi đè dữ liệu.');
        }

        Schema::dropIfExists(self::RECOVERY_TABLE);
        $this->createCategoriesTable(self::RECOVERY_TABLE);
        $this->seedRecoveredCategories(self::RECOVERY_TABLE, $usedCategoryIds);
        $this->dropProductCategoryForeignKey();

        Schema::rename('categories', self::BACKUP_TABLE);
        Schema::rename(self::RECOVERY_TABLE, 'categories');

        $this->ensureProductCategoryForeignKey();
    }

    public function down(): void
    {
    }

    private function categoriesAreCorrupted(): bool
    {
        $requiredColumns = ['id', 'name', 'slug', 'description', 'image', 'status', 'created_at', 'updated_at', 'deleted_at'];

        if (! Schema::hasColumns('categories', $requiredColumns)) {
            return true;
        }

        return DB::table('categories')
            ->whereNotIn('status', [0, 1])
            ->orWhere('name', '')
            ->exists();
    }

    private function createCategoriesTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 100);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedRecoveredCategories(string $tableName, array $usedCategoryIds): void
    {
        $now = now();
        $categories = [
            1 => ['name' => 'Trà Sữa', 'slug' => 'tra-sua', 'description' => 'Các loại trà sữa thơm béo, nhiều topping.'],
            2 => ['name' => 'Cà Phê', 'slug' => 'ca-phe', 'description' => 'Cà phê rang xay đậm vị, pha chế mỗi ngày.'],
            3 => ['name' => 'Sinh Tố', 'slug' => 'sinh-to', 'description' => 'Sinh tố trái cây tươi, mịn và mát lạnh.'],
            4 => ['name' => 'Nước Ép', 'slug' => 'nuoc-ep', 'description' => 'Nước ép trái cây nguyên chất, giàu vitamin.'],
            5 => ['name' => 'Trà Trái Cây', 'slug' => 'tra-trai-cay', 'description' => 'Trà trái cây thanh mát, hợp thời tiết nóng.'],
            6 => ['name' => 'Soda', 'slug' => 'soda', 'description' => 'Soda giải khát có gas, màu sắc bắt mắt.'],
            7 => ['name' => 'Đá Xay', 'slug' => 'da-xay', 'description' => 'Đồ uống đá xay mát lạnh, vị ngọt cân bằng.'],
            8 => ['name' => 'Matcha', 'slug' => 'matcha', 'description' => 'Matcha thơm vị trà xanh, béo nhẹ và thanh mát.'],
        ];

        foreach ($usedCategoryIds as $categoryId) {
            $categories[$categoryId] ??= [
                'name' => 'Danh mục phục hồi '.$categoryId,
                'slug' => 'danh-muc-phuc-hoi-'.$categoryId,
                'description' => 'Danh mục được phục hồi để giữ nguyên liên kết sản phẩm.',
            ];
        }

        $rows = collect($categories)->map(fn (array $category, int $id) => [
            'id' => $id,
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'],
            'image' => null,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ])->values()->all();

        DB::table($tableName)->insert($rows);
    }

    private function dropProductCategoryForeignKey(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'category_id')) {
            return;
        }

        $foreignKey = collect(Schema::getForeignKeys('products'))->first(
            fn (array $key) => $key['columns'] === ['category_id']
        );

        if ($foreignKey) {
            Schema::table('products', function (Blueprint $table) use ($foreignKey) {
                DB::getDriverName() === 'sqlite'
                    ? $table->dropForeign(['category_id'])
                    : $table->dropForeign($foreignKey['name']);
            });
        }
    }

    private function ensureProductCategoryForeignKey(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'category_id')) {
            return;
        }

        $hasForeignKey = collect(Schema::getForeignKeys('products'))->contains(
            fn (array $key) => $key['columns'] === ['category_id'] && $key['foreign_table'] === 'categories'
        );

        if (! $hasForeignKey) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('category_id', 'products_category_id_foreign')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }
};
