<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['cliente', 'proyecto', 'atendidoPor']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('proyecto_id')) {
            $query->where('proyecto_id', $request->proyecto_id);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);
        $proyectos = \App\Models\Proyecto::orderBy('nombre')->get();

        return view('tickets.index', compact('tickets', 'proyectos'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['cliente', 'proyecto', 'atendidoPor']);
        return view('tickets.show', compact('ticket'));
    }

    public function responder(Request $request, Ticket $ticket)
    {
        $request->validate([
            'respuesta' => 'required|string|max:2000',
            'estado' => 'required|in:en_proceso,resuelto,cerrado',
        ]);

        $ticket->update([
            'respuesta' => $request->respuesta,
            'estado' => $request->estado,
            'atendido_por' => auth()->id(),
            'fecha_respuesta' => now(),
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Respuesta enviada correctamente');
    }

    public function cambiarEstado(Request $request, Ticket $ticket)
    {
        $request->validate([
            'estado' => 'required|in:abierto,en_proceso,resuelto,cerrado',
        ]);

        $ticket->update(['estado' => $request->estado]);

        return back()->with('success', 'Estado actualizado');
    }
}
