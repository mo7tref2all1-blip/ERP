<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="update">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" color="warning" size="lg" icon="heroicon-o-arrow-up-circle">
                    بدء التحديث
                </x-filament::button>
            </div>
        </form>

        @if(count($this->log))
            <x-filament::section>
                <x-slot name="heading">سجل التحديث</x-slot>
                <div class="font-mono text-sm space-y-1 p-4 bg-gray-950 rounded-lg text-gray-100" dir="ltr">
                    @foreach($this->log as $line)
                        <div class="{{ str_starts_with($line, '✓') ? 'text-green-400' : 'text-red-400' }}">
                            {{ $line }}
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
