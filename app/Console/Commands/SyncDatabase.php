<?php

namespace App\Console\Commands;

use App\Services\DatabaseSyncService;
use Illuminate\Console\Command;

class SyncDatabase extends Command
{
    protected $signature = 'sync:database 
        {--direction=to-remote : Dirección de sincronización (to-remote, from-remote)}
        {--init : Inicializar estructura en base de datos remota}
        {--status : Mostrar estado de conexión}
        {--daemon : Ejecutar en modo continuo}
        {--interval=30 : Intervalo en segundos para modo daemon}';

    protected $description = 'Sincronizar base de datos local con Railway';

    protected DatabaseSyncService $syncService;

    public function __construct(DatabaseSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        if ($this->option('status')) {
            return $this->showStatus();
        }

        if ($this->option('init')) {
            return $this->initializeRemote();
        }

        if ($this->option('daemon')) {
            return $this->runDaemon();
        }

        return $this->runSync();
    }

    protected function showStatus(): int
    {
        $status = $this->syncService->getStatus();

        $this->info('Estado de Sincronización');
        $this->line('------------------------');
        $this->line('Conexión Local: ' . ($status['local_connected'] ? '✓ Conectado' : '✗ Error'));
        $this->line('Conexión Remota: ' . ($status['remote_connected'] ? '✓ Conectado' : '✗ Error'));
        $this->line('Tablas a sincronizar: ' . count($status['tables_to_sync']));

        return 0;
    }

    protected function initializeRemote(): int
    {
        $this->info('Inicializando base de datos remota...');

        if (!$this->syncService->testConnection()) {
            $this->error('No se puede conectar a la base de datos remota. Verifica las credenciales.');
            return 1;
        }

        if (!$this->confirm('Esto sobrescribirá todos los datos en la base de datos remota. ¿Continuar?')) {
            return 0;
        }

        $this->info('Copiando estructura y datos...');
        
        if ($this->syncService->initializeRemote()) {
            $this->info('✓ Base de datos remota inicializada correctamente');
            return 0;
        }

        $this->error('Error al inicializar la base de datos remota');
        return 1;
    }

    protected function runSync(): int
    {
        $direction = $this->option('direction');

        if (!$this->syncService->testConnection()) {
            $this->error('No se puede conectar a la base de datos remota');
            return 1;
        }

        $this->info("Sincronizando ({$direction})...");

        $results = $direction === 'from-remote' 
            ? $this->syncService->syncFromRemote()
            : $this->syncService->syncToRemote();

        if ($results['success']) {
            $this->info("✓ Sincronización completada. Registros: {$results['synced']}");
            return 0;
        }

        $this->error('Error en sincronización: ' . implode(', ', $results['errors']));
        return 1;
    }

    protected function runDaemon(): int
    {
        $interval = (int) $this->option('interval');
        
        $this->info("Ejecutando sincronización en modo daemon (cada {$interval} segundos)");
        $this->info('Presiona Ctrl+C para detener');

        while (true) {
            $results = $this->syncService->syncToRemote();
            
            $timestamp = now()->format('H:i:s');
            if ($results['success']) {
                $this->line("[{$timestamp}] Sync OK - {$results['synced']} registros");
            } else {
                $this->error("[{$timestamp}] Error: " . implode(', ', $results['errors']));
            }

            sleep($interval);
        }
    }
}
