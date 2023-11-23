<?php

namespace KitLoong\MigrationsGenerator\Tests\Feature\MySQL57;

use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use KitLoong\MigrationsGenerator\Schema\Models\ForeignKey;
use KitLoong\MigrationsGenerator\Schema\Models\Index;
use KitLoong\MigrationsGenerator\Schema\MySQLSchema;
use KitLoong\MigrationsGenerator\Support\CheckMigrationMethod;
use Throwable;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CommandTest extends MySQL57TestCase
{
    use CheckMigrationMethod;

    public function testRun(): void
    {
        $migrateTemplates = function (): void {
            $this->migrateGeneral('mysql57');
        };

        $generateMigrations = function (): void {
            $this->generateMigrations();
        };

        $this->verify($migrateTemplates, $generateMigrations);
    }

    public function testDown(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations();

        $this->rollbackMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $tables = $this->getTableNames();
        $views  = $this->getViewNames();

        $this->assertCount(1, $tables);
        $this->assertCount(0, $views);
        $this->assertSame(0, DB::table('migrations')->count());
    }

    public function testCollation(): void
    {
        $migrateTemplates = function (): void {
            $this->migrateCollation('mysql57');
        };

        $generateMigrations = function (): void {
            $this->generateMigrations(['--use-db-collation' => true]);
        };

        $this->verify($migrateTemplates, $generateMigrations);
    }

    public function testSquashUp(): void
    {
        $migrateTemplates = function (): void {
            $this->migrateGeneral('mysql57');
        };

        $generateMigrations = function (): void {
            $this->generateMigrations(['--squash' => true]);
        };

        $this->verify($migrateTemplates, $generateMigrations);
    }

    public function testSquashDown(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations(['--squash' => true]);

        $this->rollbackMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $tables = $this->getTableNames();
        $views  = $this->getViewNames();

        $this->assertCount(1, $tables);
        $this->assertCount(0, $views);
        $this->assertSame(0, DB::table('migrations')->count());
    }

    public function testTables(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations([
            '--tables' => implode(',', [
                'all_columns_mysql57',
                'users_mysql57',
                'users_mysql57_view',
            ]),
        ]);

        $this->refreshDatabase();

        $this->runMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $tables = $this->getTableNames();
        $views  = $this->getViewNames();

        $this->assertCount(3, $tables);
        $this->assertCount(1, $views);

        $this->assertContains('all_columns_mysql57', $tables);
        $this->assertContains('migrations', $tables);
        $this->assertContains('users_mysql57', $tables);
        $this->assertContains('users_mysql57_view', $views);
    }

    public function testIgnore(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $allAssets = count($this->getTableNames()) + count($this->getViewNames());

        $ignores = [
            'quoted-name-foreign-mysql57',
            'increments_mysql57',
            'timestamps_mysql57',
            'users_mysql57_view',
        ];

        $ignoreNotExists = ['not_exists'];

        $this->generateMigrations([
            '--ignore' => implode(',', $ignores + $ignoreNotExists),
        ]);

        $this->refreshDatabase();

        $this->runMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $tables = $this->getTableNames();
        $views  = $this->getViewNames();

        $this->assertSame(count($tables) + count($views), $allAssets - count($ignores));
        $this->assertEmpty(array_intersect($ignores, $tables));
    }

    public function testDefaultIndexNames(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations([
            '--tables'              => 'test_index_mysql57',
            '--default-index-names' => true,
        ]);

        $this->refreshDatabase();

        $this->runMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $indexes = app(MySQLSchema::class)
            ->getTable('test_index_mysql57')
            ->getIndexes();

        $actualIndexes = $indexes->map(function (Index $index) {
            return $index->getName();
        })->toArray();

        $expectedIndexes = [
            '', // PRIMARY
            'test_index_mysql57_chain_index',
            'test_index_mysql57_chain_unique',
            'test_index_mysql57_col_multi1_col_multi2_index',
//            'test_index_mysql57_col_multi1_col_multi2(16)_index',
            'test_index_mysql57_col_multi1_col_multi2_unique',
            'test_index_mysql57_col_multi_custom1_col_multi_custom2_index',
            'test_index_mysql57_col_multi_custom1_col_multi_custom2_unique',
            'test_index_mysql57_column_hyphen_index',
            'test_index_mysql57_index_custom_index',
            'test_index_mysql57_index_index',
            'test_index_mysql57_spatial_index_custom_spatialindex',
            'test_index_mysql57_spatial_index_spatialindex',
            'test_index_mysql57_unique_custom_unique',
            'test_index_mysql57_unique_unique',
//            'test_index_mysql57_with_length(16)_index',
//            'test_index_mysql57_with_length_custom(16)_index',
        ];

        if ($this->hasFullText()) {
            $expectedIndexes = array_merge($expectedIndexes, [
                'test_index_mysql57_chain_fulltext',
                'test_index_mysql57_col_multi1_col_multi2_fulltext',
                'test_index_mysql57_fulltext_custom_fulltext',
                'test_index_mysql57_fulltext_fulltext',
            ]);
        }

        sort($actualIndexes);
        sort($expectedIndexes);

        $this->assertSame(
            $expectedIndexes,
            $actualIndexes
        );
    }

    public function testDefaultFKNames(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations(['--default-fk-names' => true]);

        $this->refreshDatabase();

        $this->runMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $foreignKeys     = app(MySQLSchema::class)->getTableForeignKeys('user_profile_mysql57');
        $foreignKeyNames = $foreignKeys->map(function (ForeignKey $foreignKey) {
            return $foreignKey->getName();
        })
            ->sort()
            ->values()
            ->toArray();

        $this->assertSame(
            [
                'user_profile_mysql57_user_id_fk_constraint_foreign',
                'user_profile_mysql57_user_id_fk_custom_foreign',
                'user_profile_mysql57_user_id_foreign',
                'user_profile_mysql57_user_id_user_sub_id_fk_custom_foreign',
                'user_profile_mysql57_user_id_user_sub_id_foreign',
            ],
            $foreignKeyNames
        );

        $this->rollbackMigrationsFrom('mysql57', $this->getStorageMigrationsPath());
    }

    public function testDate(): void
    {
        $migrateTemplates = function (): void {
            $this->migrateGeneral('mysql57');
        };

        $generateMigrations = function (): void {
            $this->generateMigrations(['--date' => '2021-10-08 09:30:40']);
        };

        $this->verify($migrateTemplates, $generateMigrations);
    }

    public function testTableFilenameAndViewFilename(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations([
            '--table-filename' => '[datetime]_custom_[name]_table.php',
            '--view-filename'  => '[datetime]_custom_[name]_view.php',
        ]);

        $migrations = [];

        foreach (File::files($this->getStorageMigrationsPath()) as $migration) {
            $migrations[] = substr($migration->getFilenameWithoutExtension(), 18);
        }

        $this->assertContains('custom_all_columns_mysql57_table', $migrations);
        $this->assertContains('custom_users_mysql57_view_view', $migrations);
    }

    public function testProcedureFilename(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations(['--proc-filename' => '[datetime]_custom_[name]_proc.php']);

        $migrations = [];

        foreach (File::files($this->getStorageMigrationsPath()) as $migration) {
            $migrations[] = substr($migration->getFilenameWithoutExtension(), 18);
        }

        $this->assertContains('custom_findNameWithHyphenmysql57_proc', $migrations);
    }

    public function testFKFilename(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations(['--fk-filename' => '[datetime]_custom_[name]_table.php']);

        $migrations = [];

        foreach (File::files($this->getStorageMigrationsPath()) as $migration) {
            $migrations[] = substr($migration->getFilenameWithoutExtension(), 18);
        }

        $this->assertContains('custom_user_profile_mysql57_table', $migrations);
    }

    public function testSkipView(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations(['--skip-views' => true]);

        $migrations   = [];
        $prefixLength = 18;

        foreach (File::files($this->getStorageMigrationsPath()) as $migration) {
            $migrations[] = substr($migration->getFilenameWithoutExtension(), $prefixLength);
        }

        $this->assertContains('create_all_columns_mysql57_table', $migrations);
        $this->assertNotContains('create_users_mysql57_view_view', $migrations);
    }

    public function testSkipProcedure(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();

        $this->generateMigrations(['--skip-proc' => true]);

        $migrations   = [];
        $prefixLength = 18;

        foreach (File::files($this->getStorageMigrationsPath()) as $migration) {
            $migrations[] = substr($migration->getFilenameWithoutExtension(), $prefixLength);
        }

        $this->assertContains('create_all_columns_mysql57_table', $migrations);
        $this->assertNotContains('create_getNameWithHyphen_proc', $migrations);
    }

    public function testWithHasTable(): void
    {
        $migrateTemplates = function (): void {
            $this->migrateGeneral('mysql57');
        };

        $generateMigrations = function (): void {
            $this->generateMigrations(['--with-has-table' => true]);
        };

        $this->verify($migrateTemplates, $generateMigrations);
    }

    public function testWithHasTableSquash(): void
    {
        $migrateTemplates = function (): void {
            $this->migrateGeneral('mysql57');
        };

        $generateMigrations = function (): void {
            $this->generateMigrations(['--with-has-table' => true, '--squash' => true]);
        };

        $this->verify($migrateTemplates, $generateMigrations);
    }

    public function testWillCreateMigrationTable(): void
    {
        $this->migrateGeneral('mysql57');
        Schema::dropIfExists('migrations');

        $this->generateMigrations();

        $this->assertTrue(Schema::hasTable('migrations'));
    }

    public function testNoInteraction(): void
    {
        $this->migrateGeneral('mysql57');
        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('expected.sql'));

        $this->artisan(
            'migrate:generate',
            [
                '--path'           => $this->getStorageMigrationsPath(),
                '--no-interaction' => true,
            ]
        );

        $this->assertSame(0, DB::table('migrations')->count());
        $this->dumpSchemaAs($this->getStorageSqlPath('actual.sql'));

        $this->assertFileEqualsIgnoringOrder(
            $this->getStorageSqlPath('expected.sql'),
            $this->getStorageSqlPath('actual.sql')
        );
    }

    public function testSkipLog(): void
    {
        $this->migrateGeneral('mysql57');
        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('expected.sql'));

        $this->artisan(
            'migrate:generate',
            [
                '--path'     => $this->getStorageMigrationsPath(),
                '--skip-log' => true,
            ]
        );

        $this->assertSame(0, DB::table('migrations')->count());
        $this->dumpSchemaAs($this->getStorageSqlPath('actual.sql'));

        $this->assertFileEqualsIgnoringOrder(
            $this->getStorageSqlPath('expected.sql'),
            $this->getStorageSqlPath('actual.sql')
        );
    }

    public function testLogWithBatch0(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('expected.sql'));

        $this->artisan(
            'migrate:generate',
            [
                '--path'           => $this->getStorageMigrationsPath(),
                '--log-with-batch' => '0',
            ]
        );

        $this->assertMigrations();

        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('actual.sql'));

        $this->assertFileEqualsIgnoringOrder(
            $this->getStorageSqlPath('expected.sql'),
            $this->getStorageSqlPath('actual.sql')
        );
    }

    public function testLogWithBatch99(): void
    {
        $this->migrateGeneral('mysql57');

        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('expected.sql'));

        $this->artisan(
            'migrate:generate',
            [
                '--path'           => $this->getStorageMigrationsPath(),
                '--log-with-batch' => '99',
            ]
        );

        $this->assertMigrations();

        $this->assertSame(99, app(MigrationRepositoryInterface::class)->getNextBatchNumber() - 1);

        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('actual.sql'));

        $this->assertFileEqualsIgnoringOrder(
            $this->getStorageSqlPath('expected.sql'),
            $this->getStorageSqlPath('actual.sql')
        );
    }

    public function testLogWithBatchNaN(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('--log-with-batch must be a valid integer.');

        $this->artisan(
            'migrate:generate',
            [
                '--path'           => $this->getStorageMigrationsPath(),
                '--log-with-batch' => 'Not a number',
            ]
        );
    }

    public function testSkipVendor(): void
    {
        $this->migrateGeneral('mysql57');

        $this->migrateVendors('mysql57');

        // Load migrations from vendors path to mock vendors migration.
        // Loaded migrations should not be generated.
        app('migrator')->path($this->getStorageFromVendorsPath());

        $tables = $this->getTableNames();

        $vendors = [
            'personal_access_tokens_mysql57',
            'telescope_entries_mysql57',
            'telescope_entries_tags_mysql57',
            'telescope_monitoring_mysql57',
        ];

        foreach ($vendors as $vendor) {
            $this->assertContains($vendor, $tables);
        }

        $tablesWithoutVendors = (new Collection($tables))->filter(function ($table) use ($vendors) {
            return !in_array($table, $vendors);
        })
            ->values()
            ->all();

        $this->truncateMigrationsTable();

        $this->generateMigrations(['--skip-vendor' => true]);

        $this->refreshDatabase();

        $this->runMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $generatedTables = $this->getTableNames();

        $this->assertSame($tablesWithoutVendors, $generatedTables);
    }

    private function verify(callable $migrateTemplates, callable $generateMigrations): void
    {
        $migrateTemplates();

        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('expected.sql'));

        $generateMigrations();

        $this->assertMigrations();

        $this->refreshDatabase();

        $this->runMigrationsFrom('mysql57', $this->getStorageMigrationsPath());

        $this->truncateMigrationsTable();
        $this->dumpSchemaAs($this->getStorageSqlPath('actual.sql'));

        $this->assertFileEqualsIgnoringOrder(
            $this->getStorageSqlPath('expected.sql'),
            $this->getStorageSqlPath('actual.sql')
        );
    }
}
