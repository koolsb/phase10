<?php

declare(strict_types=1);

use App\Enums\Phase\DifficultyBand;
use App\Enums\Phase\GenerationMode;
use App\Services\PhaseGeneratorService;
use App\Services\PhaseLibrary;
use App\Support\Phase\GeneratedGame;
use App\Support\Phase\GenerationOptions;
use App\Support\Phase\Phase;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public int $count = 10;

    public string $mode = 'ramp';

    public string $band = 'medium';

    /** @var array<int, string> */
    public array $slotBands = [];

    public ?int $seed = null;

    /** @var list<string> ordered phase signatures = the current game */
    public array $signatures = [];

    /** @var list<int> */
    public array $widenedSlots = [];

    public bool $generated = false;

    /** @var array{0: int, 1: int} Indices into DifficultyBand::ordered() — 0=Easy, 3=Brutal */
    public array $difficultyRange = [0, 3];

    public function mount(): void
    {
        $this->syncSlotBands();
    }

    public function updatedMode(): void
    {
        $this->syncSlotBands();
    }

    public function updatedCount(): void
    {
        $this->count = max(1, min(20, (int) $this->count));
        $this->syncSlotBands();
    }

    public function generate(PhaseGeneratorService $generator, PhaseLibrary $library): void
    {
        $this->applyGame($generator->generateGame($library->all(), $this->options()));
    }

    public function generateOne(PhaseGeneratorService $generator, PhaseLibrary $library): void
    {
        $band = $this->mode === GenerationMode::FLAT->value ? DifficultyBand::from($this->band) : null;
        [$minBand, $maxBand] = $this->bandRange();

        $pool = $library->all()
            ->reject(fn (Phase $p): bool => in_array($p->signature, $this->signatures, true))
            ->values();

        if ($pool->isEmpty()) {
            $pool = $library->all();
        }

        $phase = $generator->generateOne($pool, new GenerationOptions(count: 1, mode: GenerationMode::from($this->mode), band: $band, minBand: $minBand, maxBand: $maxBand));

        $this->signatures[] = $phase->signature;
        $this->count = count($this->signatures);
        $this->generated = true;
        $this->syncSlotBands();
    }

    public function regenerate(int $index, PhaseGeneratorService $generator, PhaseLibrary $library): void
    {
        $game = $this->currentGame($library);

        if ($game === null) {
            return;
        }

        $this->applyGame($generator->regenerateSlot($library->all(), $game, $index, $this->options()));
    }

    public function remove(int $index): void
    {
        unset($this->signatures[$index]);
        $this->signatures = array_values($this->signatures);
        $this->count = max(1, count($this->signatures));
        $this->widenedSlots = [];
        $this->syncSlotBands();
    }

    public function clear(): void
    {
        $this->signatures = [];
        $this->widenedSlots = [];
        $this->seed = null;
        $this->generated = false;
    }

    public function printUrl(): string
    {
        return route('phases.print', ['phases' => implode(',', $this->signatures)]);
    }

    public function fullscreenUrl(): string
    {
        return route('phases.fullscreen', ['phases' => implode(',', $this->signatures)]);
    }

    #[Computed]
    public function phases(): array
    {
        $library = app(PhaseLibrary::class);

        return array_values(array_filter(array_map(
            fn (string $sig): ?Phase => $library->find($sig),
            $this->signatures,
        )));
    }

    #[Computed]
    public function bandCounts(): array
    {
        return app(PhaseLibrary::class)->bandCounts();
    }

    private function options(): GenerationOptions
    {
        [$minBand, $maxBand] = $this->bandRange();

        return new GenerationOptions(
            count: $this->count,
            mode: GenerationMode::from($this->mode),
            band: DifficultyBand::from($this->band),
            slotBands: array_map(
                static fn (string $b): DifficultyBand => DifficultyBand::from($b),
                array_values($this->slotBands),
            ),
            minBand: $minBand,
            maxBand: $maxBand,
        );
    }

    /** @return array{?DifficultyBand, ?DifficultyBand} */
    private function bandRange(): array
    {
        $bands = DifficultyBand::ordered();

        return [
            $bands[$this->difficultyRange[0]] ?? null,
            $bands[$this->difficultyRange[1]] ?? null,
        ];
    }

    private function currentGame(PhaseLibrary $library): ?GeneratedGame
    {
        if ($this->signatures === []) {
            return null;
        }

        $phases = array_values(array_filter(array_map(
            fn (string $sig): ?Phase => $library->find($sig),
            $this->signatures,
        )));

        return new GeneratedGame($phases, $this->seed, GenerationMode::from($this->mode), $this->widenedSlots);
    }

    private function applyGame(GeneratedGame $game): void
    {
        $this->signatures = array_map(static fn (Phase $p): string => $p->signature, $game->phases);
        $this->widenedSlots = $game->widenedSlots;
        $this->seed = $game->seed;
        $this->generated = true;
    }

    private function syncSlotBands(): void
    {
        $out = [];

        for ($i = 0; $i < $this->count; $i++) {
            $out[$i] = $this->slotBands[$i] ?? 'medium';
        }

        $this->slotBands = $out;
    }

    public function with(): array
    {
        return [
            'modes' => GenerationMode::cases(),
            'bandOptions' => DifficultyBand::ordered(),
        ];
    }
}; ?>

<div class="space-y-8">
    <div class="space-y-2">
        <flux:heading size="xl" class="font-black">Generate a Phase 10 game</flux:heading>
        <flux:text>
            Build a fresh set of phases to keep the game interesting. Pick how difficulty should flow,
            how many phases you want, then generate, tweak, and print a card.
        </flux:text>
        <div class="flex flex-wrap gap-2 pt-1">
            @foreach ($bandOptions as $b)
                <flux:badge size="sm" :color="$b->color()">
                    {{ $b->label() }}: {{ $this->bandCounts[$b->value] ?? 0 }}
                </flux:badge>
            @endforeach
        </div>
    </div>

    {{-- Controls --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
            <flux:field>
                <flux:label>Difficulty mode</flux:label>
                <flux:radio.group wire:model.live="mode" variant="cards" class="max-sm:flex-col">
                    @foreach ($modes as $m)
                        <flux:radio value="{{ $m->value }}" label="{{ $m->label() }}" description="{{ $m->description() }}" />
                    @endforeach
                </flux:radio.group>
            </flux:field>

            <flux:field>
                <flux:label>How many phases?</flux:label>
                <flux:input type="number" wire:model.live="count" min="1" max="20" />
                <flux:description>Default is 10, like the original game.</flux:description>
            </flux:field>
        </div>

        {{-- Flat band picker --}}
        @if ($mode === 'flat')
            <div class="mt-5">
                <flux:field>
                    <flux:label>Band for every phase</flux:label>
                    <flux:select wire:model.live="band" class="max-w-xs">
                        @foreach ($bandOptions as $b)
                            <flux:select.option value="{{ $b->value }}">{{ $b->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
        @endif

        {{-- Manual per-slot bands --}}
        @if ($mode === 'manual')
            <div class="mt-5">
                <flux:label>Difficulty per phase</flux:label>
                <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    @for ($i = 0; $i < $count; $i++)
                        <flux:select wire:model="slotBands.{{ $i }}" size="sm" :label="'Phase '.($i + 1)">
                            @foreach ($bandOptions as $b)
                                <flux:select.option value="{{ $b->value }}">{{ $b->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endfor
                </div>
            </div>
        @endif

        {{-- Difficulty range --}}
        <div class="mt-5">
            <flux:field>
                <flux:label>Difficulty range</flux:label>
                <div class="mt-3 px-1">
                    <flux:slider range wire:model.live="difficultyRange" min="0" max="3" step="1" />
                    <div class="mt-1.5 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        @foreach ($bandOptions as $b)
                            <span>{{ $b->label() }}</span>
                        @endforeach
                    </div>
                </div>
                <flux:description>Constrain which difficulty bands the generator can draw from.</flux:description>
            </flux:field>
        </div>

        <flux:separator class="my-6" />

        <div class="flex flex-wrap items-center gap-3">
            <flux:button variant="primary" icon="sparkles" wire:click="generate" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">Generate {{ $count }} {{ \Illuminate\Support\Str::plural('phase', $count) }}</span>
                <span wire:loading wire:target="generate">Generating…</span>
            </flux:button>

            <flux:button variant="subtle" icon="plus" wire:click="generateOne">Add one phase</flux:button>

            @if ($generated && count($this->phases))
                <flux:button :href="$this->fullscreenUrl()" target="_blank" variant="subtle" icon="arrows-pointing-out">
                    Full screen
                </flux:button>
                <flux:button :href="$this->printUrl()" target="_blank" variant="subtle" icon="printer">
                    Print card
                </flux:button>
                <flux:button variant="ghost" icon="trash" wire:click="clear">Clear</flux:button>
            @endif

            @if ($seed)
                <flux:text class="ml-auto text-xs text-zinc-400">seed #{{ $seed }}</flux:text>
            @endif
        </div>
    </div>

    {{-- Results --}}
    @if (count($this->phases))
        @if (count($this->widenedSlots))
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>
                    Some slots were filled from a nearby difficulty band because the requested band ran out of
                    distinct phases. Add more phases in <code>config/phases.php</code> to widen the pool.
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="grid gap-8 lg:grid-cols-2">
            {{-- Editable list --}}
            <div class="space-y-3">
                <flux:heading size="lg">Your phases</flux:heading>
                <ul class="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                    @foreach ($this->phases as $i => $phase)
                        <li class="flex items-center gap-3 bg-white px-4 py-3 dark:bg-zinc-900" wire:key="slot-{{ $i }}-{{ $phase->signature }}">
                            <span class="w-6 text-right font-bold text-zinc-400 tabular-nums">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold text-zinc-800 dark:text-zinc-100">{{ $phase->label }}</div>
                                <div class="text-xs text-zinc-400">score {{ number_format($phase->score, 1) }}</div>
                            </div>
                            <flux:badge size="sm" :color="$phase->band->color()">{{ $phase->band->label() }}</flux:badge>
                            <flux:button size="sm" variant="subtle" icon="arrow-path" wire:click="regenerate({{ $i }})" tooltip="Regenerate" />
                            <flux:button size="sm" variant="subtle" icon="x-mark" wire:click="remove({{ $i }})" tooltip="Remove" />
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Card preview --}}
            <div class="space-y-3">
                <flux:heading size="lg">Card preview</flux:heading>
                <x-phase-card :phases="$this->phases" :show-bands="true" subtitle="Custom game" />
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
            <flux:icon name="sparkles" class="mx-auto mb-3 size-8 text-zinc-300" />
            <flux:text>No phases yet — hit <span class="font-semibold">Generate</span> to build a game.</flux:text>
        </div>
    @endif
</div>
