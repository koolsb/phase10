<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PhaseLibrary;
use App\Support\Phase\Phase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FullscreenController extends Controller
{
    public function __invoke(Request $request, PhaseLibrary $library): View
    {
        $signatures = array_filter(explode(',', (string) $request->query('phases', '')));

        $phases = array_values(array_filter(array_map(
            fn (string $sig): ?Phase => $library->find(trim($sig)),
            $signatures,
        )));

        return view('fullscreen', [
            'phases' => $phases,
            'title' => $request->query('title', 'Phase 10'),
        ]);
    }
}
