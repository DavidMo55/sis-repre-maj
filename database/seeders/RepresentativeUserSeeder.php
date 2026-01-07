<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; 
use Illuminate\Support\Facades\Hash;

class RepresentativeUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $username_test = 'majrepre'; 
        $password_test = 'majrepre1255';        
        $email_test    = 'representante_prueba12@sistema.com';


        if (!User::where('name', $username_test)->exists()) {
            User::create([
                'name' => $username_test, 
                'email' => $email_test,
                'password' => Hash::make($password_test), 
            ]);

            $this->command->info("¡Éxito! Usuario de prueba '{$username_test}' creado.");
            $this->command->info("Datos de acceso: Username='{$username_test}', Password='{$password_test}'");
        } else {
             $this->command->info("El usuario '{$username_test}' ya existe. No se creó uno nuevo.");
        }
    }
}