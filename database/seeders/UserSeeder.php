<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* 
        insert into usuarios(nombresApellidos, nombreUsuario, password, dni, cui,codRol, codEstado)
values
('Larri Rodrigo Estrada León', 'lestradal', 'admin', '71086437', '9',1, 1),
('Miguel Angel Vega Perez', 'mvegape', 'admin', '74283707', '1',1, 1);
        */
        DB::table('users')->insert([
            'names' => 'Larri Rodrigo',
            'father_surname' => 'Estrada',
            'mother_surname' => 'León',
            'username' => 'lestradal',
            'password' => bcrypt('admin'),
            'dni' => '71086437',
            'cui' => '9',
            'email' => 'lestradal@example.com',
            'rol_id' => 1,
            'state_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::table('users')->insert([
            'names' => 'Miguel Angel',
            'father_surname' => 'Perez',
            'mother_surname' => 'Vega',
            'username' => 'mvegape',
            'password' => bcrypt('admin'),
            'dni' => '74283707',
            'cui' => '1',
            'email' => 'mvegape@example.com',
            'rol_id' => 1,
            'state_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}
