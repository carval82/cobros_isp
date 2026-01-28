<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;

class ClientesRemigioSeeder extends Seeder
{
    public function run(): void
    {
        $proyectoId = 2; // REMIGIO

        $clientes = [
            'DOLIS MARIMON',
            'EMEL',
            'JHON ARENAS',
            'LEIDY JARAMILLO',
            'JOSE ROJAS',
            'IDALY REGINFO',
            'ADRIANA CARDONA',
            'MARIA CARUPIA',
            'LEIDY PADIERNA',
            'DANIEL CASTAÑO',
            'ZULEIMA CARDONA',
            'FAVIAN PADILLA',
            'CAMILA ALVAREZ',
            'FREDDY FLOREZ',
            'MAGNOLIA',
            'ADRIANA BETANCOUR',
            'MIRIAM GUZMAN',
            'EMILCEN',
            'ARIEL USUGA',
            'JOSE DOMICO',
            'RODRIGO SALAZAR',
            'DOÑA OTILIA',
            'OMAIRA',
            'JORGE RIVERA',
            'DON LEO',
            'CAMILA TRUJILLO',
            'MARTA LOPEZ',
            'DIANA RUIZ',
            'STTELLA OQUENDO',
            'MARCOS COTRALAN',
            'DAVID AVILA',
            'JOHANA MONTEALEGRE',
            'ANDREA GALLEGO',
            'BELSY MARIMON',
            'ROSALBA',
            'CUBANO',
            'CARLOS ZALAZAR',
            'AROLDO MIGUEL',
            'PAOLA TRUJILLO',
            'HECTOR MARIO PADILLA',
            'ANGEL SANTELIZ',
            'CORO',
            'PAOLA PEREZ',
            'LUZ ALBA GARCIA',
            'ELIAS THERAN',
            'MARIA CARMEN',
            'NEYDIS ZULUAGA',
            'YULI ORREGO',
            'NANCI MANCO PUENTE C',
            'CARLOS GARCIA',
            'ALBA ROSA MANGAS',
            'NEGOCIO TERMINAL',
            'MATEO',
        ];

        foreach ($clientes as $nombre) {
            Cliente::create([
                'proyecto_id' => $proyectoId,
                'nombre' => $nombre,
                'direccion' => 'Por definir',
                'estado' => 'activo',
            ]);
        }

        $this->command->info('Se crearon ' . count($clientes) . ' clientes para el proyecto REMIGIO');
    }
}
