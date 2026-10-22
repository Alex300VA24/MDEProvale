<?php

namespace App\Http\Controllers;

use App\Services\ReportGeneratorService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ReportGeneratorController extends Controller
{
    public function __construct(private ReportGeneratorService $reports)
    {
    }

    public function config()
    {
        return response()->json($this->reports->config());
    }

    public function generar(Request $request)
    {
        try {
            [$contents, $filename] = $this->reports->generate(
                (array) $request->input('entidades', []),
                (array) $request->input('columnas', []),
                (array) $request->input('filtros', []),
                (array) $request->input('agrupar_por', []),
                $request->user()?->id
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
