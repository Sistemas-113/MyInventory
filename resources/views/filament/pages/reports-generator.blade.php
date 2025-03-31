<x-filament-panels::page>
    <div class="space-y-8">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-adjustments-horizontal"
                        class="h-5 w-5 text-primary-500"
                    />
                    <span>Generador de Reportes</span>
                </div>
            </x-slot>

            <x-slot name="description">
                Configura y genera reportes específicos según tus necesidades
            </x-slot>

            <form wire:submit.prevent="generateReport" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button 
                        type="submit"
                        icon="heroicon-m-document-arrow-down"
                        size="lg"
                    >
                        Generar Reporte
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
