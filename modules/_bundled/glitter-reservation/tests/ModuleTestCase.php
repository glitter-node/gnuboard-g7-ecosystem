<?php

namespace Modules\Glitter\Reservation\Tests;

use App\Enums\PermissionType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Reservation 모듈 테스트 베이스 클래스
 */
abstract class ModuleTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * 오토로드 등록 여부
     */
    protected static bool $autoloadRegistered = false;

    /**
     * 마이그레이션 실행 여부
     */
    protected static bool $migrated = false;

    /**
     * 모듈 루트 경로 반환
     */
    protected function getModuleBasePath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * 테스트 설정
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerModuleAutoload();
        $this->app->register(\Modules\Glitter\Reservation\Providers\ReservationServiceProvider::class);
        $this->runModuleMigrationIfNeeded();
        $this->registerModuleRoutes();
        $this->createDefaultRoles();
    }

    /**
     * 모듈 오토로드 등록
     */
    protected function registerModuleAutoload(): void
    {
        if (static::$autoloadRegistered) {
            return;
        }

        $moduleBasePath = $this->getModuleBasePath().'/src/';

        spl_autoload_register(function ($class) use ($moduleBasePath) {
            $prefix = 'Modules\\Reservation\\';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $moduleBasePath.str_replace('\\', '/', $relativeClass).'.php';

            if (file_exists($file)) {
                require $file;
            }
        });

        static::$autoloadRegistered = true;
    }

    /**
     * 모듈 마이그레이션 실행
     */
    protected function runModuleMigrationIfNeeded(): void
    {
        if (static::$migrated) {
            return;
        }

        if (! Schema::hasTable('users') || ! Schema::hasTable('modules')) {
            $this->artisan('migrate');
        }

        if (! $this->hasReservationTables()) {
            $this->artisan('migrate', [
                '--path' => $this->getModuleBasePath().'/database/migrations',
                '--realpath' => true,
            ]);
        }

        $this->runReservationPatchMigrationsIfNeeded();

        static::$migrated = true;
    }

    protected function runReservationPatchMigrationsIfNeeded(): void
    {
        if (! Schema::hasColumn('reservation_bookings', 'completed_at')) {
            $this->artisan('migrate', [
                '--path' => $this->getModuleBasePath().'/database/migrations/2026_04_06_000008_add_completed_at_to_reservation_bookings_table.php',
                '--realpath' => true,
            ]);
        }

        if (! Schema::hasColumn('reservation_bookings', 'student_grade')) {
            $this->artisan('migrate', [
                '--path' => $this->getModuleBasePath().'/database/migrations/2026_04_06_000009_add_student_grade_to_reservation_bookings_table.php',
                '--realpath' => true,
            ]);
        }

        if (! DB::table('migrations')->where('migration', '2026_04_06_000010_alter_customer_email_length_in_reservation_bookings_table')->exists()) {
            $this->artisan('migrate', [
                '--path' => $this->getModuleBasePath().'/database/migrations/2026_04_06_000010_alter_customer_email_length_in_reservation_bookings_table.php',
                '--realpath' => true,
            ]);
        }

        if (! Schema::hasTable('reservation_email_verifications')) {
            $this->artisan('migrate', [
                '--path' => $this->getModuleBasePath().'/database/migrations/2026_04_06_000011_create_reservation_email_verifications_table.php',
                '--realpath' => true,
            ]);
        }
    }

    protected function hasReservationTables(): bool
    {
        return Schema::hasTable('reservation_services')
            && Schema::hasTable('reservation_schedules')
            && Schema::hasTable('reservation_holidays')
            && Schema::hasTable('reservation_bookings')
            && Schema::hasTable('reservation_booking_logs');
    }

    protected function registerModuleRoutes(): void
    {
        if (app('router')->getRoutes()->getByName('api.modules.reservation.admin.reservation.bookings.index')) {
            $this->registerModuleWebRoutes();

            return;
        }

        $adminRoutesFile = $this->getModuleBasePath().'/routes/admin.php';

        if (file_exists($adminRoutesFile)) {
            Route::prefix('api/modules/glitter-reservation')
                ->name('api.modules.reservation.')
                ->middleware('api')
                ->group($adminRoutesFile);
        }

        $this->registerModuleWebRoutes();

    }

    protected function registerModuleWebRoutes(): void
    {
        if (app('router')->getRoutes()->getByName('reservation.bookings.lookup')) {
            return;
        }

        $webRoutesFile = $this->getModuleBasePath().'/routes/web.php';

        if (file_exists($webRoutesFile)) {
            Route::group([], $webRoutesFile);
        }

    }

    protected function createDefaultRoles(): void
    {
        Role::firstOrCreate(
            ['identifier' => 'admin'],
            ['name' => ['ko' => '관리자', 'en' => 'Administrator']]
        );

        Role::firstOrCreate(
            ['identifier' => 'user'],
            ['name' => ['ko' => '일반 사용자', 'en' => 'User']]
        );

        Role::firstOrCreate(
            ['identifier' => 'guest'],
            ['name' => ['ko' => '비회원', 'en' => 'Guest']]
        );
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function createAdminUser(array $permissions = []): User
    {
        /** @var Role $adminRole */
        $adminRole = Role::query()->where('identifier', 'admin')->firstOrFail();

        /** @var User $user */
        $user = User::factory()->create();
        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        foreach ($permissions as $permissionIdentifier) {
            $permission = Permission::query()->firstOrCreate(
                ['identifier' => $permissionIdentifier, 'type' => PermissionType::Admin],
                [
                    'name' => ['ko' => $permissionIdentifier, 'en' => $permissionIdentifier],
                    'description' => ['ko' => $permissionIdentifier, 'en' => $permissionIdentifier],
                ]
            );

            $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return $user;
    }
}
