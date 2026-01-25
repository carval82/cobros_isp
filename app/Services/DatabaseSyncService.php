<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseSyncService
{
    protected $localConnection = 'mysql';
    protected $remoteConnection = 'mysql_remote';
    
    protected $tablesToSync = [
        'users',
        'cobradors',
        'liquidacions',
        'plan_servicios',
        'clientes',
        'cobros',
        'servicios',
        'facturas',
        'pagos',
    ];

    public function syncToRemote(): array
    {
        $results = [
            'success' => true,
            'synced' => 0,
            'errors' => [],
        ];

        try {
            foreach ($this->tablesToSync as $table) {
                $synced = $this->syncTable($table, $this->localConnection, $this->remoteConnection);
                $results['synced'] += $synced;
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Sync error: ' . $e->getMessage());
        }

        return $results;
    }

    public function syncFromRemote(): array
    {
        $results = [
            'success' => true,
            'synced' => 0,
            'errors' => [],
        ];

        try {
            foreach ($this->tablesToSync as $table) {
                $synced = $this->syncTable($table, $this->remoteConnection, $this->localConnection);
                $results['synced'] += $synced;
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Sync error: ' . $e->getMessage());
        }

        return $results;
    }

    protected function syncTable(string $table, string $source, string $destination): int
    {
        $lastSync = $this->getLastSyncTime($table, $destination);
        
        $records = DB::connection($source)
            ->table($table)
            ->where('updated_at', '>', $lastSync)
            ->get();

        if ($records->isEmpty()) {
            return 0;
        }

        $synced = 0;

        foreach ($records as $record) {
            $data = (array) $record;
            
            $exists = DB::connection($destination)
                ->table($table)
                ->where('id', $data['id'])
                ->exists();

            if ($exists) {
                DB::connection($destination)
                    ->table($table)
                    ->where('id', $data['id'])
                    ->update($data);
            } else {
                DB::connection($destination)
                    ->table($table)
                    ->insert($data);
            }

            $synced++;
        }

        $this->updateLastSyncTime($table, $destination);

        return $synced;
    }

    protected function getLastSyncTime(string $table, string $connection): string
    {
        $record = DB::connection($connection)
            ->table('sync_log')
            ->where('table_name', $table)
            ->first();

        return $record->last_sync ?? '1970-01-01 00:00:00';
    }

    protected function updateLastSyncTime(string $table, string $connection): void
    {
        DB::connection($connection)
            ->table('sync_log')
            ->updateOrInsert(
                ['table_name' => $table],
                ['last_sync' => now(), 'updated_at' => now()]
            );
    }

    public function initializeRemote(): bool
    {
        try {
            $localTables = DB::connection($this->localConnection)
                ->select('SHOW TABLES');

            $key = 'Tables_in_' . config('database.connections.mysql.database');

            foreach ($localTables as $table) {
                $tableName = $table->$key;
                
                if ($tableName === 'migrations') continue;

                $createStatement = DB::connection($this->localConnection)
                    ->select("SHOW CREATE TABLE `{$tableName}`");

                $createSql = $createStatement[0]->{'Create Table'};

                DB::connection($this->remoteConnection)
                    ->statement("DROP TABLE IF EXISTS `{$tableName}`");
                
                DB::connection($this->remoteConnection)
                    ->statement($createSql);

                $records = DB::connection($this->localConnection)
                    ->table($tableName)
                    ->get();

                if ($records->isNotEmpty()) {
                    foreach ($records->chunk(100) as $chunk) {
                        DB::connection($this->remoteConnection)
                            ->table($tableName)
                            ->insert($chunk->map(fn($r) => (array) $r)->toArray());
                    }
                }
            }

            $this->createSyncLogTable($this->remoteConnection);

            return true;
        } catch (\Exception $e) {
            Log::error('Initialize remote error: ' . $e->getMessage());
            return false;
        }
    }

    protected function createSyncLogTable(string $connection): void
    {
        DB::connection($connection)->statement("
            CREATE TABLE IF NOT EXISTS sync_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                table_name VARCHAR(100) UNIQUE,
                last_sync DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
    }

    public function testConnection(): bool
    {
        try {
            DB::connection($this->remoteConnection)->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::error('Remote connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getStatus(): array
    {
        return [
            'local_connected' => $this->testLocalConnection(),
            'remote_connected' => $this->testConnection(),
            'tables_to_sync' => $this->tablesToSync,
        ];
    }

    protected function testLocalConnection(): bool
    {
        try {
            DB::connection($this->localConnection)->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
