<?php

return [
    'pagination' => [
        'label' => 'Navegación de paginación',
        'overview' => 'Mostrando :first a :last de :total resultados',
        'fields' => [
            'records_per_page' => [
                'label' => 'por página',
            ],
        ],
        'buttons' => [
            'go_to_page' => [
                'label' => 'Ir a la página :page',
            ],
            'next' => [
                'label' => 'Siguiente',
            ],
            'previous' => [
                'label' => 'Anterior',
            ],
        ],
    ],
    
    'search' => [
        'label' => 'Buscar',
        'placeholder' => 'Buscar',
    ],
];
