<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;

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
    }
}
