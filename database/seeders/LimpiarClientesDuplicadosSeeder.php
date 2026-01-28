<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\Cobrador;
use Illuminate\Support\Facades\DB;

class LimpiarClientesDuplicadosSeeder extends Seeder
{
    public function run(): void
    {
        // Eliminar clientes del proyecto REMIGIO (id=2) que NO tienen servicios asignados
        $clientesSinServicio = Cliente::where('proyecto_id', 2)
            ->whereDoesntHave('servicios')
            ->get();

        $count = $clientesSinServicio->count();
        
        foreach ($clientesSinServicio as $cliente) {
            $cliente->forceDelete();
        }

        $this->command->info("Se eliminaron {$count} clientes de REMIGIO sin servicio asignado");

        // Asegurar que todos los cobradores activos estén asignados a REMIGIO (id=2)
        $cobradores = Cobrador::where('estado', 'activo')->get();
        foreach ($cobradores as $cobrador) {
            // Verificar si ya tiene el proyecto asignado
            $existe = DB::table('cobrador_proyecto')
                ->where('cobrador_id', $cobrador->id)
                ->where('proyecto_id', 2)
                ->exists();
            
            if (!$existe) {
                DB::table('cobrador_proyecto')->insert([
                    'cobrador_id' => $cobrador->id,
                    'proyecto_id' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Cobrador {$cobrador->nombre} asignado a REMIGIO");
            }
        }
    }
}
