// INTERVEREDANET Cobrador PWA
const App = {
    apiUrl: '/api',
    token: null,
    cobrador: null,
    db: null,
    isOnline: navigator.onLine,
    
    // IndexedDB Setup
    async initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('InterveredanetCobrador', 1);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Clientes store
                if (!db.objectStoreNames.contains('clientes')) {
                    const clientesStore = db.createObjectStore('clientes', { keyPath: 'id' });
                    clientesStore.createIndex('nombre', 'nombre', { unique: false });
                }
                
                // Facturas store
                if (!db.objectStoreNames.contains('facturas')) {
                    const facturasStore = db.createObjectStore('facturas', { keyPath: 'id' });
                    facturasStore.createIndex('cliente_id', 'cliente_id', { unique: false });
                }
                
                // Planes store
                if (!db.objectStoreNames.contains('planes')) {
                    db.createObjectStore('planes', { keyPath: 'id' });
                }
                
                // Pending operations (offline queue)
                if (!db.objectStoreNames.contains('pendingOps')) {
                    const pendingStore = db.createObjectStore('pendingOps', { keyPath: 'id', autoIncrement: true });
                    pendingStore.createIndex('type', 'type', { unique: false });
                }
                
                // Config store
                if (!db.objectStoreNames.contains('config')) {
                    db.createObjectStore('config', { keyPath: 'key' });
                }
            };
        });
    },
    
    // DB Operations
    async dbPut(storeName, data) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            const request = store.put(data);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },
    
    async dbGet(storeName, key) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },
    
    async dbGetAll(storeName) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, 'readonly');
            const store = tx.objectStore(storeName);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    },
    
    async dbDelete(storeName, key) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            const request = store.delete(key);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },
    
    async dbClear(storeName) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            const request = store.clear();
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    },
    
    // API Calls
    async apiCall(endpoint, method = 'GET', data = null) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const options = { method, headers };
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(this.apiUrl + endpoint, options);
        return response.json();
    },
    
    // Auth
    async login(documento, pin) {
        try {
            const result = await this.apiCall('/cobrador/login', 'POST', { documento, pin });
            
            if (result.success) {
                this.token = result.token;
                this.cobrador = result.cobrador;
                
                await this.dbPut('config', { key: 'token', value: this.token });
                await this.dbPut('config', { key: 'cobrador', value: this.cobrador });
                
                this.showMainApp();
                await this.syncData();
            } else {
                alert(result.message || 'Error al iniciar sesión');
            }
        } catch (error) {
            alert('Error de conexión. Verifica tu internet.');
            console.error(error);
        }
    },
    
    async logout() {
        this.token = null;
        this.cobrador = null;
        await this.dbClear('config');
        await this.dbClear('clientes');
        await this.dbClear('facturas');
        await this.dbClear('planes');
        document.getElementById('loginScreen').classList.remove('hidden');
        document.getElementById('mainApp').classList.add('hidden');
    },
    
    async checkAuth() {
        const tokenData = await this.dbGet('config', 'token');
        const cobradorData = await this.dbGet('config', 'cobrador');
        
        if (tokenData && cobradorData) {
            this.token = tokenData.value;
            this.cobrador = cobradorData.value;
            this.showMainApp();
            
            if (this.isOnline) {
                this.syncData();
            } else {
                this.loadFromDB();
            }
        }
    },
    
    showMainApp() {
        document.getElementById('loginScreen').classList.add('hidden');
        document.getElementById('mainApp').classList.remove('hidden');
        document.getElementById('cobradorNombre').textContent = this.cobrador.nombre;
        document.getElementById('fechaHoy').textContent = new Date().toLocaleDateString('es-CO', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    },
    
    // Sync
    async syncData() {
        if (!this.isOnline) {
            this.loadFromDB();
            return;
        }
        
        document.getElementById('syncIndicator').classList.add('active');
        
        try {
            // First, sync pending operations
            await this.syncPendingOps();
            
            // Then, get fresh data
            const lastSync = await this.dbGet('config', 'lastSync');
            const result = await this.apiCall('/cobrador/sync?last_sync=' + (lastSync?.value || ''));
            
            if (result.success) {
                // Save to IndexedDB
                for (const cliente of result.data.clientes) {
                    await this.dbPut('clientes', cliente);
                }
                
                // Clear and reload facturas
                await this.dbClear('facturas');
                for (const factura of result.data.facturas_pendientes) {
                    await this.dbPut('facturas', factura);
                }
                
                // Save planes
                await this.dbClear('planes');
                for (const plan of result.data.planes) {
                    await this.dbPut('planes', plan);
                }
                
                await this.dbPut('config', { key: 'lastSync', value: result.data.server_time });
                
                this.loadFromDB();
            }
        } catch (error) {
            console.error('Sync error:', error);
            this.loadFromDB();
        }
        
        document.getElementById('syncIndicator').classList.remove('active');
    },
    
    async syncPendingOps() {
        const pendingOps = await this.dbGetAll('pendingOps');
        
        for (const op of pendingOps) {
            try {
                let result;
                if (op.type === 'pago') {
                    result = await this.apiCall('/cobrador/pago', 'POST', op.data);
                } else if (op.type === 'cliente') {
                    result = await this.apiCall('/cobrador/cliente', 'POST', op.data);
                }
                
                if (result && result.success) {
                    await this.dbDelete('pendingOps', op.id);
                }
            } catch (error) {
                console.error('Error syncing op:', op, error);
            }
        }
        
        this.updatePendingCount();
    },
    
    async loadFromDB() {
        const clientes = await this.dbGetAll('clientes');
        const facturas = await this.dbGetAll('facturas');
        
        document.getElementById('statClientes').textContent = clientes.length;
        document.getElementById('statPendientes').textContent = facturas.length;
        
        // Calculate today's collection
        const pendingOps = await this.dbGetAll('pendingOps');
        const pagosHoy = pendingOps.filter(op => op.type === 'pago');
        const totalHoy = pagosHoy.reduce((sum, op) => sum + parseFloat(op.data.monto), 0);
        document.getElementById('statRecaudadoHoy').textContent = '$' + totalHoy.toLocaleString('es-CO');
        
        this.updatePendingCount();
        this.renderClientes(clientes);
        this.renderFacturas(facturas);
        this.loadPlanes();
    },
    
    async updatePendingCount() {
        const pendingOps = await this.dbGetAll('pendingOps');
        const pendingDiv = document.getElementById('pendingSync');
        const countSpan = document.getElementById('pendingSyncCount');
        
        if (pendingOps.length > 0) {
            pendingDiv.classList.remove('hidden');
            countSpan.textContent = pendingOps.length;
        } else {
            pendingDiv.classList.add('hidden');
        }
    },
    
    // Views
    showView(view) {
        const views = ['dashboard', 'clientes', 'cobrar', 'nuevoCliente', 'pago'];
        views.forEach(v => {
            const el = document.getElementById('view' + v.charAt(0).toUpperCase() + v.slice(1));
            if (el) el.classList.add('hidden');
        });
        
        const targetView = document.getElementById('view' + view.charAt(0).toUpperCase() + view.slice(1));
        if (targetView) targetView.classList.remove('hidden');
        
        // Update nav
        document.querySelectorAll('.nav-item').forEach((item, index) => {
            item.classList.remove('active');
            const viewMap = ['dashboard', 'clientes', 'cobrar', 'nuevoCliente'];
            if (viewMap[index] === view) item.classList.add('active');
        });
    },
    
    // Render
    renderClientes(clientes) {
        const container = document.getElementById('listaClientes');
        container.innerHTML = clientes.map(c => `
            <div class="cliente-item" onclick="App.showClienteDetail(${c.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cliente-nombre">${c.nombre}</div>
                        <div class="cliente-info">
                            <i class="fas fa-map-marker-alt me-1"></i>${c.direccion || 'Sin dirección'}
                        </div>
                        <div class="cliente-info">
                            <i class="fas fa-phone me-1"></i>${c.celular || c.telefono || 'Sin teléfono'}
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary">${c.codigo}</span>
                        ${c.servicio ? `<div class="cliente-info mt-1">${c.servicio.plan_nombre}</div>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    },
    
    renderFacturas(facturas) {
        const container = document.getElementById('listaFacturas');
        container.innerHTML = facturas.map(f => `
            <div class="cliente-item" onclick="App.showPagoForm(${f.id})">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="cliente-nombre">${f.cliente_nombre}</div>
                        <div class="cliente-info">${f.periodo} - ${f.numero}</div>
                    </div>
                    <div class="text-end">
                        <span class="badge-saldo ${f.estado === 'pagada' ? 'badge-pagado' : ''}">
                            $${parseFloat(f.saldo).toLocaleString('es-CO')}
                        </span>
                        <div class="cliente-info mt-1">${f.estado}</div>
                    </div>
                </div>
            </div>
        `).join('');
    },
    
    async loadPlanes() {
        const planes = await this.dbGetAll('planes');
        const select = document.getElementById('clientePlan');
        select.innerHTML = '<option value="">Sin plan</option>' + 
            planes.map(p => `<option value="${p.id}">${p.nombre} - $${parseFloat(p.precio).toLocaleString('es-CO')}</option>`).join('');
    },
    
    // Pago
    async showPagoForm(facturaId) {
        const factura = await this.dbGet('facturas', facturaId);
        if (!factura) return;
        
        document.getElementById('pagoFacturaId').value = factura.id;
        document.getElementById('pagoMonto').value = factura.saldo;
        document.getElementById('pagoFacturaInfo').innerHTML = `
            <div class="fw-bold">${factura.cliente_nombre}</div>
            <div class="text-muted">${factura.periodo} - ${factura.numero}</div>
            <div class="mt-2">
                <span class="text-muted">Total:</span> $${parseFloat(factura.total).toLocaleString('es-CO')}<br>
                <span class="text-muted">Saldo:</span> <strong class="text-danger">$${parseFloat(factura.saldo).toLocaleString('es-CO')}</strong>
            </div>
        `;
        
        this.showView('pago');
    },
    
    async registrarPago(event) {
        event.preventDefault();
        
        const facturaId = parseInt(document.getElementById('pagoFacturaId').value);
        const monto = parseFloat(document.getElementById('pagoMonto').value);
        const metodo = document.getElementById('pagoMetodo').value;
        const observaciones = document.getElementById('pagoObservaciones').value;
        
        const pagoData = {
            factura_id: facturaId,
            monto: monto,
            metodo_pago: metodo,
            fecha_pago: new Date().toISOString().split('T')[0],
            observaciones: observaciones,
            offline_id: 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
        };
        
        // Save to pending operations
        await this.dbPut('pendingOps', {
            type: 'pago',
            data: pagoData,
            created_at: new Date().toISOString()
        });
        
        // Update local factura
        const factura = await this.dbGet('facturas', facturaId);
        if (factura) {
            factura.saldo = Math.max(0, factura.saldo - monto);
            factura.estado = factura.saldo <= 0 ? 'pagada' : 'parcial';
            await this.dbPut('facturas', factura);
        }
        
        alert('Pago registrado correctamente');
        
        // Try to sync if online
        if (this.isOnline) {
            this.syncPendingOps();
        }
        
        this.loadFromDB();
        this.showView('cobrar');
    },
    
    // Nuevo Cliente
    async registrarCliente(event) {
        event.preventDefault();
        
        const clienteData = {
            nombre: document.getElementById('clienteNombre').value,
            tipo_documento: document.getElementById('clienteTipoDoc').value,
            documento: document.getElementById('clienteDocumento').value,
            celular: document.getElementById('clienteCelular').value,
            direccion: document.getElementById('clienteDireccion').value,
            barrio: document.getElementById('clienteBarrio').value,
            referencia_ubicacion: document.getElementById('clienteReferencia').value,
            latitud: document.getElementById('clienteLatitud').value || null,
            longitud: document.getElementById('clienteLongitud').value || null,
            plan_servicio_id: document.getElementById('clientePlan').value || null,
            offline_id: 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
        };
        
        // Save to pending operations
        await this.dbPut('pendingOps', {
            type: 'cliente',
            data: clienteData,
            created_at: new Date().toISOString()
        });
        
        // Save locally
        const tempId = -Date.now();
        await this.dbPut('clientes', {
            id: tempId,
            codigo: 'PEND',
            nombre: clienteData.nombre,
            celular: clienteData.celular,
            direccion: clienteData.direccion,
            barrio: clienteData.barrio,
            servicio: null
        });
        
        alert('Cliente registrado correctamente');
        
        // Clear form
        document.getElementById('formNuevoCliente').reset();
        
        // Try to sync if online
        if (this.isOnline) {
            this.syncPendingOps();
        }
        
        this.loadFromDB();
        this.showView('clientes');
    },
    
    // GPS
    obtenerUbicacion() {
        if (!navigator.geolocation) {
            alert('Geolocalización no soportada');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                document.getElementById('clienteLatitud').value = position.coords.latitude;
                document.getElementById('clienteLongitud').value = position.coords.longitude;
                alert('Ubicación obtenida correctamente');
            },
            (error) => {
                alert('Error al obtener ubicación: ' + error.message);
            }
        );
    },
    
    // Search
    setupSearch() {
        document.getElementById('searchClientes').addEventListener('input', async (e) => {
            const query = e.target.value.toLowerCase();
            const clientes = await this.dbGetAll('clientes');
            const filtered = clientes.filter(c => 
                c.nombre.toLowerCase().includes(query) || 
                c.direccion?.toLowerCase().includes(query) ||
                c.codigo?.toLowerCase().includes(query)
            );
            this.renderClientes(filtered);
        });
        
        document.getElementById('searchFacturas').addEventListener('input', async (e) => {
            const query = e.target.value.toLowerCase();
            const facturas = await this.dbGetAll('facturas');
            const filtered = facturas.filter(f => 
                f.cliente_nombre.toLowerCase().includes(query) || 
                f.numero?.toLowerCase().includes(query)
            );
            this.renderFacturas(filtered);
        });
    },
    
    // Init
    async init() {
        await this.initDB();
        
        // Online/Offline detection
        window.addEventListener('online', () => {
            this.isOnline = true;
            document.getElementById('offlineBadge').classList.remove('show');
            this.syncData();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            document.getElementById('offlineBadge').classList.add('show');
        });
        
        if (!this.isOnline) {
            document.getElementById('offlineBadge').classList.add('show');
        }
        
        // Login form
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const documento = document.getElementById('loginDocumento').value;
            const pin = document.getElementById('loginPin').value;
            this.login(documento, pin);
        });
        
        // Pago form
        document.getElementById('formPago').addEventListener('submit', (e) => this.registrarPago(e));
        
        // Nuevo cliente form
        document.getElementById('formNuevoCliente').addEventListener('submit', (e) => this.registrarCliente(e));
        
        // Search
        this.setupSearch();
        
        // Check auth
        await this.checkAuth();
    }
};

// Start app
document.addEventListener('DOMContentLoaded', () => App.init());

// Register Service Worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('Service Worker registered'))
        .catch(err => console.error('SW registration failed:', err));
}
