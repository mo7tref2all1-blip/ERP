<x-filament-panels::page>
    <form wire:submit="transfer">
        {{ $this->form }}
        <div class="mt-6 flex justify-end gap-x-3">
            <x-filament::button type="submit" icon="heroicon-o-arrows-right-left" color="primary">
                تنفيذ التحويل
            </x-filament::button>
        </div>
    </form>

    @if($this->available_quantity !== null)
    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <p class="text-blue-800 dark:text-blue-200">
            <strong>الكمية المتاحة في الفرع المصدر:</strong>
            {{ number_format($this->available_quantity, 2) }}
        </p>
    </div>
    @endif
</x-filament-panels::page>
