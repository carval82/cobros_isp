<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientePortalController extends Controller
{
    public function showLogin()
    {
        return view('portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'documento' => 'required|string',
            'pin' => 'required|string|min:4|max:4',
        ]);

        $cliente = Cliente::where('documento', $request->documento)
            ->where('estado', 'activo')
            ->first();

        if (!$cliente) {
            return back()->withErrors(['documento' => 'Cliente no encontrado o inactivo']);
        }

        // Verificar PIN (últimos 4 dígitos del documento o PIN personalizado)
        $pinEsperado = substr($cliente->documento, -4);
        
        if ($cliente->pin) {
            // Si tiene PIN personalizado, verificar con hash
            if (!Hash::check($request->pin, $cliente->pin)) {
                return back()->withErrors(['pin' => 'PIN incorrecto']);
            }
        } else {
            // Si no tiene PIN, usar últimos 4 dígitos del documento
            if ($request->pin !== $pinEsperado) {
                return back()->withErrors(['pin' => 'PIN incorrecto']);
            }
        }

        // Guardar cliente en sesión
        session(['cliente_id' => $cliente->id]);
        session(['cliente_nombre' => $cliente->nombre]);

        return redirect()->route('portal.dashboard');
    }

    public function logout()
    {
        session()->forget(['cliente_id', 'cliente_nombre']);
        return redirect()->route('portal.login');
    }

    public function dashboard()
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        $facturasPendientes = $cliente->facturas()
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->orderBy('fecha_vencimiento')
            ->get();

        $facturasRecientes = $cliente->facturas()
            ->where('estado', 'pagada')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        $ticketsAbiertos = $cliente->tickets()
            ->whereIn('estado', ['abierto', 'en_proceso'])
            ->count();

        $saldoTotal = $facturasPendientes->sum('saldo');

        return view('portal.dashboard', compact(
            'cliente', 
            'facturasPendientes', 
            'facturasRecientes',
            'ticketsAbiertos',
            'saldoTotal'
        ));
    }

    public function estadoCuenta()
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        $facturas = $cliente->facturas()
            ->with('pagos')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->paginate(12);

        return view('portal.estado-cuenta', compact('cliente', 'facturas'));
    }

    public function tickets()
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        $tickets = $cliente->tickets()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('portal.tickets', compact('cliente', 'tickets'));
    }

    public function crearTicket()
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        return view('portal.crear-ticket', compact('cliente'));
    }

    public function guardarTicket(Request $request)
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        $request->validate([
            'tipo' => 'required|in:daño,cobro,soporte,otro',
            'asunto' => 'required|string|max:255',
            'descripcion' => 'required|string|max:2000',
        ]);

        Ticket::create([
            'cliente_id' => $cliente->id,
            'proyecto_id' => $cliente->proyecto_id,
            'tipo' => $request->tipo,
            'asunto' => $request->asunto,
            'descripcion' => $request->descripcion,
            'estado' => 'abierto',
            'prioridad' => $request->tipo === 'daño' ? 'alta' : 'media',
        ]);

        return redirect()->route('portal.tickets')
            ->with('success', 'Tu reporte ha sido enviado. Te contactaremos pronto.');
    }

    public function verTicket($id)
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        $ticket = Ticket::where('cliente_id', $cliente->id)
            ->findOrFail($id);

        return view('portal.ver-ticket', compact('cliente', 'ticket'));
    }

    public function perfil()
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        return view('portal.perfil', compact('cliente'));
    }

    public function actualizarPerfil(Request $request)
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        $request->validate([
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
        ]);

        $cliente->update($request->only(['celular', 'email', 'direccion']));

        return back()->with('success', 'Datos actualizados correctamente');
    }

    public function cambiarPin(Request $request)
    {
        $cliente = $this->getCliente();
        if (!$cliente) return redirect()->route('portal.login');

        $request->validate([
            'pin_actual' => 'required|string|min:4|max:4',
            'pin_nuevo' => 'required|string|min:4|max:4|confirmed',
        ]);

        // Verificar PIN actual
        $pinEsperado = substr($cliente->documento, -4);
        $pinValido = false;

        if ($cliente->pin) {
            $pinValido = Hash::check($request->pin_actual, $cliente->pin);
        } else {
            $pinValido = $request->pin_actual === $pinEsperado;
        }

        if (!$pinValido) {
            return back()->withErrors(['pin_actual' => 'PIN actual incorrecto']);
        }

        $cliente->update([
            'pin' => Hash::make($request->pin_nuevo)
        ]);

        return back()->with('success', 'PIN actualizado correctamente');
    }

    private function getCliente()
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return null;

        return Cliente::with(['proyecto', 'servicios.planServicio'])->find($clienteId);
    }
}
