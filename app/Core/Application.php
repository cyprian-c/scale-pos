<?php
// app/Core/Application.php

namespace App\Core;

class Application
{
    protected $config;
    protected $services = [];

    public function __construct(array $config)
    {
        $this->config = $config;

        // Set timezone from config
        date_default_timezone_set($config['app']['timezone']);
    }

    public function registerServices(): void
    {
        // Register database connection
        $this->services['db'] = $this->createDatabaseConnection();

        // Register session handler
        $this->services['session'] = $this->createSessionHandler();

        // Register other services as needed
    }

    protected function createDatabaseConnection(): \PDO
    {
        $db = $this->config['database'];

        try {
            $dsn = "{$db['driver']}:host={$db['host']};port={$db['port']};dbname={$db['database']};charset={$db['charset']}";
            $pdo = new \PDO($dsn, $db['username'], $db['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            return $pdo;
        } catch (\PDOException $e) {
            // For MVP, create SQLite database if MySQL fails
            return $this->createSQLiteDatabase();
        }
    }

    protected function createSQLiteDatabase(): \PDO
    {
        $sqlitePath = STORAGE_PATH . '/database/database.sqlite';

        // Create directory if it doesn't exist
        if (!is_dir(dirname($sqlitePath))) {
            mkdir(dirname($sqlitePath), 0755, true);
        }

        // Create SQLite database if it doesn't exist
        if (!file_exists($sqlitePath)) {
            touch($sqlitePath);
        }

        $pdo = new \PDO("sqlite:" . $sqlitePath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create tables for MVP
        $this->createSQLiteTables($pdo);

        return $pdo;
    }

    protected function createSQLiteTables(\PDO $pdo): void
    {
        // Products table
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sku TEXT UNIQUE,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            cost_price REAL,
            stock_quantity INTEGER DEFAULT 0,
            category_id INTEGER,
            barcode TEXT,
            image_path TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Sales table
        $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number TEXT UNIQUE,
            customer_id INTEGER,
            user_id INTEGER,
            subtotal REAL NOT NULL,
            tax_amount REAL DEFAULT 0,
            discount_amount REAL DEFAULT 0,
            total REAL NOT NULL,
            amount_paid REAL NOT NULL,
            change_amount REAL DEFAULT 0,
            payment_method TEXT,
            status TEXT DEFAULT 'completed',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Sale items table
        $pdo->exec("CREATE TABLE IF NOT EXISTS sale_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price REAL NOT NULL,
            total_price REAL NOT NULL,
            discount_amount REAL DEFAULT 0,
            tax_amount REAL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id)
        )");

        // Insert sample products if empty
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $count = $stmt->fetch()['count'];

        if ($count === 0) {
            $sampleProducts = [
                ['SKU001', 'USB-C Cable', 'High-speed charging cable', 499.99, 250.00, 100, 'ELEC-001'],
                ['SKU002', 'Coca-Cola 1.5L', 'Soft drink', 75.50, 50.00, 200, 'GROC-001'],
                ['SKU003', 'Basic T-Shirt', 'Cotton t-shirt', 299.99, 150.00, 150, 'CLOTH-001'],
                ['SKU004', 'Notebook', '100 pages, A5 size', 49.99, 25.00, 300, 'STAT-001'],
                ['SKU005', 'Wireless Mouse', 'Bluetooth mouse', 399.99, 200.00, 80, 'ELEC-002'],
            ];

            $stmt = $pdo->prepare("INSERT INTO products (sku, name, description, price, cost_price, stock_quantity, barcode) VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($sampleProducts as $product) {
                $stmt->execute($product);
            }
        }
    }

    protected function createSessionHandler(): array
    {
        return [
            'driver' => $this->config['session']['driver'],
            'path' => $this->config['session']['path'],
        ];
    }

    public function get(string $service)
    {
        return $this->services[$service] ?? null;
    }

    public function config(string $key = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? null;
    }
}
