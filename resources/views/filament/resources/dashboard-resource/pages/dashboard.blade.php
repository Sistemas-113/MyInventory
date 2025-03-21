@extends('filament::page')

@section('content')
    <div class="space-y-6">
        @foreach (\Filament\Facades\Filament::getWidgets() as $widget)
            @livewire($widget)
        @endforeach
    </div>
@endsection
