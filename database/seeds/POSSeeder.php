<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\User;

class POSSeeder extends Seeder
{
    public function run(): void
    {
        // Create categories
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Groceries', 'description' => 'Food and household items'],
            ['name' => 'Clothing', 'description' => 'Apparel and fashion items'],
            ['name' => 'Stationery', 'description' => 'Office and school supplies'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Create sample products
        $products = [
            [
                'sku' => 'ELEC-001',
                'name' => 'USB-C Cable',
                'description' => 'High-speed USB-C charging cable',
                'price' => 499.99,
                'cost_price' => 250.00,
                'stock_quantity' => 100,
                'category_id' => 1,
                'barcode' => '123456789012',
            ],
            [
                'sku' => 'GROC-001',
                'name' => 'Coca-Cola 1.5L',
                'description' => 'Regular Coca-Cola 1.5 liter bottle',
                'price' => 75.50,
                'cost_price' => 50.00,
                'stock_quantity' => 200,
                'category_id' => 2,
                'barcode' => '234567890123',
            ],
            [
                'sku' => 'CLOTH-001',
                'name' => 'Basic T-Shirt',
                'description' => 'Cotton t-shirt, various colors',
                'price' => 299.99,
                'cost_price' => 150.00,
                'stock_quantity' => 150,
                'category_id' => 3,
                'barcode' => '345678901234',
            ],
            // Add more products as needed
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Create sample customers
        $customers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+639123456789',
                'address' => '123 Main Street, City',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+639987654321',
                'address' => '456 Oak Avenue, Town',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        // Create test user if not exists
        if (!User::where('email', 'cashier@example.com')->exists()) {
            User::create([
                'name' => 'Test Cashier',
                'email' => 'cashier@example.com',
                'password' => bcrypt('password123'),
                'role' => 'cashier',
            ]);
        }
    }
}
