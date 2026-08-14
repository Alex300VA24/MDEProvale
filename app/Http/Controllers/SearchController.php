<?php

namespace App\Http\Controllers;

use App\Models\People;
use App\Models\Partner;
use Illuminate\Http\Request;

/**
 * Lightweight AJAX search endpoints for Select2 dropdowns.
 * Replaces People::all() and Partner::with('people')->get() which
 * were loading thousands of records on every page load.
 */
class SearchController extends Controller
{
    /**
     * Search people by name or DNI for Select2 AJAX.
     * GET /api/search/people?q=searchterm&limit=30
     */
    public function people(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $limit = min((int) $request->get('limit', 30), 100);

        $query = People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni']);

        if (mb_strlen($q) >= 2) {
            $this->applyNameSearch($query, $q);
        }

        $results = $query->orderBy('names')->limit($limit)->get();

        return response()->json([
            'results' => $results->map(function ($p) {
                return [
                    'id' => $p->id,
                    'text' => $p->names . ' ' . $p->father_lastname . ' ' . ($p->mother_lastname ?? '') . ' (' . ($p->dni ?? 'S/DNI') . ')',
                ];
            }),
        ]);
    }

    /**
     * Search partners (socios) by name or DNI for Select2 AJAX.
     * GET /api/search/partners?q=searchterm&limit=30
     */
    public function partners(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $limit = min((int) $request->get('limit', 30), 100);

        $query = Partner::select(['partners.id', 'partners.person_id'])
            ->join('people', 'partners.person_id', '=', 'people.id');

        if (mb_strlen($q) >= 2) {
            $this->applyNameSearch($query, $q, 'people.');
        }

        $results = $query
            ->addSelect(['people.names', 'people.father_lastname', 'people.mother_lastname', 'people.dni'])
            ->orderBy('people.names')
            ->limit($limit)
            ->get();

        return response()->json([
            'results' => $results->map(function ($p) {
                return [
                    'id' => $p->id,
                    'text' => $p->names . ' ' . $p->father_lastname . ' ' . ($p->mother_lastname ?? '') . ' (' . ($p->dni ?? 'S/DNI') . ')',
                ];
            }),
        ]);
    }

    /**
     * Filtra por nombre completo (nombres + apellidos) buscando cada palabra
     * escrita en cualquiera de las columnas de nombre/DNI, en cualquier orden.
     * Antes cada columna se comparaba contra el texto completo, así que
     * "SHARON VASQUEZ" (nombres + apellido) nunca hacía match con nada.
     */
    private function applyNameSearch($query, string $q, string $prefix = ''): void
    {
        $terms = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);

        $query->where(function ($outer) use ($terms, $prefix) {
            foreach ($terms as $term) {
                $outer->where(function ($qb) use ($term, $prefix) {
                    $qb->where("{$prefix}names", 'like', "{$term}%")
                        ->orWhere("{$prefix}father_lastname", 'like', "{$term}%")
                        ->orWhere("{$prefix}mother_lastname", 'like', "{$term}%")
                        ->orWhere("{$prefix}dni", 'like', "{$term}%");
                });
            }
        });
    }
}
