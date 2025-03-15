<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'danilo.doria@sanagustin.edu.co',
            'password' => Hash::make('Sistemas113'),
        ]);

        // Categorías de ejemplo
        Category::create(['name' => 'Electrónica', 'description' => 'Productos electrónicos']);
        Category::create(['name' => 'Ropa', 'description' => 'Artículos de vestir']);

        // Productos de ejemplo
        Product::create([
            'name' => 'Smartphone XYZ',
            'description' => 'Último modelo',
            'price' => 899.99,
            'stock' => 10,
            'category_id' => 1,
            'min_stock' => 3
        ]);

        // Cliente de ejemplo
        Client::create([
            'name' => 'Cliente Ejemplo',
            'email' => 'cliente@ejemplo.com',
            'birth_date' => '1990-01-01',
            'credit_limit' => 1000.00,
            'current_balance' => 0.00
        ]);
    }
}
