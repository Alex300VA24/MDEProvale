<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

/**
 * Lightweight AJAX search endpoints for Select2 dropdowns.
 * Replaces loading every person and partner on each page load,
 * which pulled thousands of records.
 */
class SearchController extends Controller
{
    public function __construct(private SearchService $search)
    {
    }

    /**
     * Search people by name or DNI for Select2 AJAX.
     * GET /api/search/people?q=searchterm&limit=30
     */
    public function people(Request $request)
    {
        return response()->json([
            'results' => $this->search->people((string) $request->get('q', ''), (int) $request->get('limit', 30)),
        ]);
    }

    /**
     * Search partners (socios) by name or DNI for Select2 AJAX.
     * GET /api/search/partners?q=searchterm&limit=30
     */
    public function partners(Request $request)
    {
        return response()->json([
            'results' => $this->search->partners((string) $request->get('q', ''), (int) $request->get('limit', 30)),
        ]);
    }
}
