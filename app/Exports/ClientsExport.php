<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $clients;

    public function __construct($clients = null)
    {
        $this->clients = $clients;
    }

    public function collection()
    {
        return $this->clients ?? Client::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cédula',
            'Nombre',
            'Teléfono',
            'Correo',
            'Dirección',
            'Referencias',
            'Fecha Registro',
        ];
    }

    public function map($client): array
    {
        return [
            $client->id,
            $client->identification,
            $client->name,
            $client->phone,
            $client->email,
            $client->address,
            $client->references,
            $client->created_at->format('d/m/Y'),
        ];
    }
}
