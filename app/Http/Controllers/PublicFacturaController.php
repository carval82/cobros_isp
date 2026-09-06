<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;

class PublicFacturaController extends Controller
{
    public function show(string $token)
    {
        $factura = $this->findByToken($token);
        $factura->load(['cliente.proyecto', 'servicio.planServicio', 'pagos']);

        return view('facturas.publica', compact('factura', 'token'));
    }

    public function pdf(string $token)
    {
        $factura = $this->findByToken($token);
        $factura->load(['cliente.proyecto', 'servicio.planServicio', 'pagos']);

        return Pdf::loadView('facturas.pdf.factura', compact('factura'))
            ->stream("factura-{$factura->numero}.pdf");
    }

    private function findByToken(string $token): Factura
    {
        return Factura::where('token_publico', $token)->firstOrFail();
    }
}
