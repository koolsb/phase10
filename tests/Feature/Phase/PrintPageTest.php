<?php

declare(strict_types=1);

use App\Services\PhaseLibrary;

it('renders a printable card for the given signatures', function (): void {
    $signatures = app(PhaseLibrary::class)->all()
        ->take(3)
        ->map(fn ($p): string => $p->signature)
        ->implode(',');

    $this->get('/game/print?phases='.$signatures)
        ->assertOk()
        ->assertSee('Print / Save as PDF')
        ->assertSee('2 sets of 3');
});

it('handles an empty print request gracefully', function (): void {
    $this->get('/game/print')
        ->assertOk()
        ->assertSee('No phases to print');
});
