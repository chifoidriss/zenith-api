<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Society;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserAndRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate([
            'name' =>'admin',
            'display_name' =>'Administrateur',
            'display_name' =>'Administrateur',
        ]);

        $user = User::firstOrCreate([
            'first_name' => 'Idriss',
            'last_name' => 'CHIFO',
            'genre' => 'M',
            'title' => 'Responsable Général',
            'email' => 'chifoidriss@gmail.com',
            'phone' => '690263212',
            'username' => 'chifoidriss',
            'language' => 'FR',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        $root = User::firstOrCreate([
            'first_name' => 'Super',
            'last_name' => 'User',
            'genre' => 'M',
            'title' => 'Root',
            'email' => 'root@gmail.com',
            'phone' => '00000000',
            'username' => 'root',
            'language' => 'FR',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            DB::table('permission_user')->insert([
                'user_id' => $user->id,
                'permission_id' => $permission->id,
                'value' => 2,
            ]);
            DB::table('permission_user')->insert([
                'user_id' => $root->id,
                'permission_id' => $permission->id,
                'value' => 2,
            ]);
        }

        Society::firstOrCreate([
            'name' => 'Optima Corporation',
            'phone' => '237690263212',
            'email' => 'contact@optimacorps.com',
            'site' => 'https://optimacorps.com',
            'address_name' => 'Akwa, Douala Cameroun'
        ]);

        $user->roles()->sync($admin->id);
    }
}
