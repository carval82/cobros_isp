# Cobros ISP

Sistema de gestión de cobros para proveedores de servicios de Internet (ISP).

## Características

- **Gestión de Clientes**: Registro completo con ubicación, contacto y estado
- **Planes de Servicio**: Configuración de velocidades y precios
- **Servicios**: Asignación de planes a clientes con IP, MAC, equipos
- **Facturación**: Generación automática de facturas mensuales
- **Cobros**: Rutas de cobro para cobradores en campo
- **Pagos**: Registro de pagos con múltiples métodos
- **Liquidaciones**: Cálculo de comisiones para cobradores
- **Sincronización**: Conexión con base de datos en Railway

## Requisitos

- PHP 8.2+
- MySQL 8.0+
- Composer

## Instalación

```bash
# Clonar repositorio
git clone <repo-url> cobros_isp
cd cobros_isp

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Crear base de datos
mysql -u root -e "CREATE DATABASE cobros_isp"

# Ejecutar migraciones
php artisan migrate

# Iniciar servidor
php artisan serve
```

## Configuración Railway

1. Crear base de datos MySQL en Railway
2. Agregar credenciales en `.env`:

```env
DB_REMOTE_HOST=tu-host.railway.internal
DB_REMOTE_PORT=3306
DB_REMOTE_DATABASE=railway
DB_REMOTE_USERNAME=root
DB_REMOTE_PASSWORD=tu-password
```

3. Comandos de sincronización:

```bash
# Ver estado de conexión
php artisan sync:database --status

# Inicializar base de datos remota
php artisan sync:database --init

# Sincronizar una vez
php artisan sync:database

# Modo daemon (cada 30 segundos)
php artisan sync:database --daemon --interval=30
```

## Estructura de Módulos

| Módulo | Descripción |
|--------|-------------|
| `/clientes` | CRUD de clientes |
| `/planes` | Planes de servicio |
| `/servicios` | Servicios contratados |
| `/facturas` | Facturación mensual |
| `/cobradores` | Gestión de cobradores |
| `/cobros` | Rutas de cobro |
| `/pagos` | Registro de pagos |
| `/liquidaciones` | Liquidación de comisiones |

## Flujo de Trabajo

1. **Configurar planes** de servicio con velocidades y precios
2. **Registrar clientes** con datos de contacto y ubicación
3. **Asignar servicios** a clientes (plan, IP, equipo)
4. **Generar facturas** mensuales automáticamente
5. **Crear cobros** para cobradores en campo
6. **Registrar pagos** asociados a facturas y cobros
7. **Cerrar cobros** al finalizar la ruta
8. **Liquidar comisiones** a cobradores

## Licencia

MIT
