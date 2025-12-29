<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gasto;
use App\Models\Comprobante;
use App\Models\User;
use Carbon\Carbon;

class GastoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testUser = User::where('name', 'representante.prueba')->first();

        if (!$testUser) {
            $this->command->error("El usuario de prueba no existe. Ejecuta RepresentativeUserSeeder primero.");
            return;
        }

        $userId = $testUser->id;

        // --- 1. Gasto Sin Comprobante (Debe aparecer primero) ---
        $gasto1 = Gasto::create([
            'user_id' => $userId,
            'fecha' => Carbon::yesterday(),
            'concepto' => 'Peaje y gasolina visita Culiacán',
            'monto' => 450.50,
            'facturado' => false,
        ]);
        $this->command->info("Gasto #{$gasto1->id} (Falta Comprobante) creado.");


        // --- 2. Gasto Con Comprobante (Debe aparecer después) ---
        $gasto2 = Gasto::create([
            'user_id' => $userId,
            'fecha' => Carbon::now()->subDays(3),
            'concepto' => 'Alimentos Visita Guadalajara',
            'monto' => 180.00,
            'facturado' => true,
        ]);

        // Simular un comprobante subido
        Comprobante::create([
            'gasto_id' => $gasto2->id,
            'name' => '251216-15-30-00_1-'.$gasto2->id.'.pdf',
            'size' => 1500, // 1.5MB
            'extension' => 'pdf',
            'public_url' => 'https://dropbox.com/simulacion/comprobante-1.pdf',
        ]);
        $this->command->info("Gasto #{$gasto2->id} (Con Comprobante) creado.");
        
    }
}