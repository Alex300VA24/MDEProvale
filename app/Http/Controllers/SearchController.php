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
        $q = $request->get('q', '');
        $limit = min((int) $request->get('limit', 30), 100);

        $query = People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni']);

        if (strlen($q) >= 2) {
            $query->where(function ($qb) use ($q) {
                $qb->where('names', 'like', "{$q}%")
                    ->orWhere('father_lastname', 'like', "{$q}%")
                    ->orWhere('mother_lastname', 'like', "{$q}%")
                    ->orWhere('dni', 'like', "{$q}%");
            });
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
        $q = $request->get('q', '');
        $limit = min((int) $request->get('limit', 30), 100);

        $query = Partner::select(['partners.id', 'partners.person_id'])
            ->join('people', 'partners.person_id', '=', 'people.id');

        if (strlen($q) >= 2) {
            $query->where(function ($qb) use ($q) {
                $qb->where('people.names', 'like', "{$q}%")
                    ->orWhere('people.father_lastname', 'like', "{$q}%")
                    ->orWhere('people.mother_lastname', 'like', "{$q}%")
                    ->orWhere('people.dni', 'like', "{$q}%");
            });
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
}
