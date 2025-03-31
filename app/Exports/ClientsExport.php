<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para los encabezados
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '004d99']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
            // Ajustar ancho automático para todas las columnas
            'A:Z' => ['alignment' => ['wrapText' => true]],
        ];
    }
}
