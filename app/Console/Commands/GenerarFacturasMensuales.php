<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Models\Servicio;
use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarFacturasMensuales extends Command
{
    protected $signature = 'facturas:generar 
        {--mes= : Mes a facturar (default: mes actual)}
        {--anio= : Año a facturar (default: año actual)}
        {--proyecto= : ID del proyecto (opcional)}';

    protected $description = 'Genera facturas mensuales automáticamente para todos los servicios activos';

    public function handle()
    {
        $mes = $this->option('mes') ?? now()->month;
        $anio = $this->option('anio') ?? now()->year;
        $proyectoId = $this->option('proyecto');

        $this->info("Generando facturas para {$mes}/{$anio}...");

        $query = Servicio::with(['cliente', 'planServicio'])
            ->where('estado', 'activo');

        if ($proyectoId) {
            $proyecto = Proyecto::find($proyectoId);
            if (!$proyecto) {
                $this->error("Proyecto con ID {$proyectoId} no encontrado");
                return 1;
            }
            $this->info("Proyecto: {$proyecto->nombre}");
            $query->whereHas('cliente', function($q) use ($proyectoId) {
                $q->where('proyecto_id', $proyectoId);
            });
        }

        $servicios = $query->get();
        $this->info("Servicios activos encontrados: {$servicios->count()}");

        $generadas = 0;
        $omitidas = 0;

        $bar = $this->output->createProgressBar($servicios->count());
        $bar->start();

        foreach ($servicios as $servicio) {
            if ($servicio->tieneFacturaMes($mes, $anio)) {
                $omitidas++;
                $bar->advance();
                continue;
            }

            $precio = $servicio->precio_mensual;
            
            Factura::create([
                'cliente_id' => $servicio->cliente_id,
                'servicio_id' => $servicio->id,
                'mes' => $mes,
                'anio' => $anio,
                'fecha_emision' => now(),
                'fecha_vencimiento' => Carbon::create($anio, $mes, $servicio->dia_pago_limite),
                'subtotal' => $precio,
                'total' => $precio,
                'saldo' => $precio,
                'concepto' => "Servicio de Internet - " . $servicio->planServicio->nombre,
            ]);

            $generadas++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Facturas generadas: {$generadas}");
        $this->info("→ Omitidas (ya existían): {$omitidas}");

        return 0;
    }
}
