<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Support\ModuleCatalog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleIds = DB::table('rols')->pluck('id', 'title');
        $activeStateId = DB::table('states')->where('abbreviation', 'ACT')->value('id');
        $requiredRoles = ['Administrador', 'Usuario Principal', 'Usuario Básico'];

        if (! $activeStateId || collect([...$requiredRoles, 'Rol 3'])->contains(fn ($role) => ! $roleIds->has($role))) {
            throw new RuntimeException('Faltan el estado ACT o los roles base para crear usuarios.');
        }

        $now = now();
        $users = [
            [
                'names' => 'Larri Rodrigo',
                'father_surname' => 'Estrada',
                'mother_surname' => 'León',
                'username' => 'lestradal',
                'dni' => '71086437',
                'cui' => '9',
                'email' => 'lestradal@example.com',
                'role' => 'Administrador',
            ],
            [
                'names' => 'Miguel Angel',
                'father_surname' => 'Perez',
                'mother_surname' => 'Vega',
                'username' => 'mvegape',
                'dni' => '74283707',
                'cui' => '1',
                'email' => 'mvegape@example.com',
                'role' => 'Usuario Principal',
            ],
            [
                'names' => 'Usuario',
                'father_surname' => 'Basico',
                'mother_surname' => 'Test',
                'username' => 'usuario1',
                'dni' => '12345678',
                'cui' => '2',
                'email' => 'usuario1@example.com',
                'role' => 'Usuario Básico',
            ],
            [
                'names' => 'Ruth Susy Elizabeth',
                'father_surname' => 'Seminario',
                'mother_surname' => 'López',
                'username' => 'SUSY',
                'dni' => '40593949',
                'cui' => '1',
                'email' => 'susyseminario80@gmail.com',
                'role' => 'Rol 3',
                'password' => 'Susy123++',
            ],
        ];

        foreach ($users as $user) {
            $role = $user['role'];
            $password = $user['password'] ?? 'admin';
            unset($user['role'], $user['password']);

            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                array_merge($user, [
                    'password' => bcrypt($password),
                    'rol_id' => $roleIds->get($role),
                    'state_id' => $activeStateId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $baseRoleIds = collect($requiredRoles)->map(fn ($role) => $roleIds->get($role))->all();
        DB::table('module_rol')->whereIn('rol_id', $baseRoleIds)->delete();

        // Los permisos viven en las hojas; los módulos grupo se derivan de sus submódulos.
        $groups = ModuleCatalog::groups();
        $modules = DB::table('modules')->whereNotIn('slug', $groups)->get(['id', 'slug']);
        $permissions = [];
        foreach ($modules as $module) {
            $permissions[] = $this->permissionRow($module->id, $roleIds->get('Administrador'), true, $now);

            if (! in_array($module->slug, [ModuleCatalog::RESPONSABLES_RACIONES, ModuleCatalog::REPORTES, ModuleCatalog::SISTEMA], true)) {
                $permissions[] = $this->permissionRow($module->id, $roleIds->get('Usuario Principal'), true, $now);
            }

            if (in_array($module->slug, [ModuleCatalog::SOCIOS_BENEFICIARIOS, ModuleCatalog::EVALUACION_NUTRICIONAL, ModuleCatalog::PERFIL, ModuleCatalog::AYUDA], true)) {
                $permissions[] = $this->permissionRow($module->id, $roleIds->get('Usuario Básico'), false, $now);
            }
        }

        DB::table('module_rol')->insert($permissions);
    }

    private function permissionRow(int $moduleId, int $roleId, bool $canDelete, $now): array
    {
        return [
            'module_id' => $moduleId,
            'rol_id' => $roleId,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => $canDelete,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
