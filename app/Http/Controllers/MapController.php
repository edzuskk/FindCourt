<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MapController extends Controller
{
    /**
     * Show the map page.
     */
    public function __invoke(): View
    {
        return view('map');
    }
}
