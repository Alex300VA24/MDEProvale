<?php

namespace Tests\Feature;

use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ModuleSeeder::class);
        $this->seed(RolSeeder::class);
    }

    public function test_operational_roles_have_the_expected_module_access(): void
    {
        $expected = [
            'Rol 1' => ['club-madres', 'reconocimientos', 'reportes', 'socios-beneficiarios'],
            'Rol 2' => ['movimientos', 'pecosas', 'productos', 'reportes', 'responsables-raciones'],
            'Rol 3' => [
                'club-madres',
                'movimientos',
                'pecosas',
                'productos',
                'reconocimientos',
                'reportes',
                'responsables-raciones',
                'socios-beneficiarios',
            ],
        ];

        foreach ($expected as $title => $expectedSlugs) {
            $rolId = DB::table('rols')->where('title', $title)->value('id');
            $this->assertNotNull($rolId);

            $permissions = DB::table('module_rol')
                ->join('modules', 'modules.id', '=', 'module_rol.module_id')
                ->where('module_rol.rol_id', $rolId)
                ->orderBy('modules.slug')
                ->get([
                    'modules.slug',
                    'module_rol.can_view',
                    'module_rol.can_create',
                    'module_rol.can_edit',
                    'module_rol.can_delete',
                ]);

            $this->assertSame($expectedSlugs, $permissions->pluck('slug')->all());
            $permissions->each(function ($permission) {
                $this->assertSame(1, (int) $permission->can_view);
                $this->assertSame(1, (int) $permission->can_create);
                $this->assertSame(1, (int) $permission->can_edit);
                $this->assertSame(1, (int) $permission->can_delete);
            });
        }
    }

    public function test_rol_three_never_receives_system_access(): void
    {
        $rolId = DB::table('rols')->where('title', 'Rol 3')->value('id');

        $this->assertFalse(
            DB::table('module_rol')
                ->join('modules', 'modules.id', '=', 'module_rol.module_id')
                ->where('module_rol.rol_id', $rolId)
                ->where('modules.slug', 'sistema')
                ->exists()
        );
    }
}
