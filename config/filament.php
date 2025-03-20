<?php

return [
    'path' => 'admin',
    'domain' => null,
    'home_url' => '/',
    'layout' => [
        'sidebar' => [
            'is_collapsible_on_desktop' => true,
        ],
        'tables' => [
            'pagination' => [
                'records_per_page_select_options' => [10, 25, 50, 100],
                'next' => 'Siguiente',
                'previous' => 'Anterior',
                'records_per_page_label' => 'Registros por página',
                'showing_label' => 'Mostrando',
                'to_label' => 'a',
                'of_label' => 'de',
                'results_label' => 'resultados',
            ],
        ],
    ],
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
    ],
    
    'default_filesystem_disk' => 'public',

    'validation' => [
        'messages' => [
            'required' => 'Este campo es obligatorio',
            'min' => 'Este campo debe tener al menos :min caracteres',
            'max' => 'Este campo no puede tener más de :max caracteres',
            'unique' => 'Este valor ya está en uso',
            'email' => 'Debe ser una dirección de correo válida',
            'numeric' => 'Este campo debe ser un número',
            'tel' => 'Debe ser un número de teléfono válido',
        ],
    ],

    'notifications' => [
        'duration' => 5000,
    ],

    'pagination' => [
        'default_records_per_page' => 10,
        'records_per_page_select_options' => [10, 25, 50, 100],
    ],
];
