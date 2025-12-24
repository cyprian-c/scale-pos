<?php declare(strict_types=1);
// app/Bootstrap/app.php

// Define paths if not already defined
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 1));
    define('APP_PATH', BASE_PATH . '/app');
    define('PUBLIC_PATH', BASE_PATH . '/public');
    define('STORAGE_PATH', BASE_PATH . '/storage');
    define('CONFIG_PATH', BASE_PATH . '/config');
}

// Error reporting for development
if (getenv('APP_ENV') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

// Simple request handling
try {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestMethod = $_SERVER['REQUEST_METHOD'];

    // Simple router for MVP
    $routes = [
        'GET' => [
            '/' => 'home',
            '/pos' => 'pos',
            '/api/products' => 'apiProducts',
            '/api/products/search' => 'apiProductsSearch',
            '/assets/{file}' => 'asset',
        ],
        'POST' => [
            '/api/sales' => 'apiSales',
        ],
    ];

    $handler = null;
    $params = [];

    // Find matching route
    if (isset($routes[$requestMethod])) {
        foreach ($routes[$requestMethod] as $route => $handlerName) {
            $pattern = str_replace('/', '\/', $route);
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $pattern);
            $pattern = '/^' . $pattern . '$/';

            if (preg_match($pattern, $requestUri, $matches)) {
                $handler = $handlerName;
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                break;
            }
        }
    }

    if ($handler) {
        // FIX 1: Check if function exists before calling
        if (function_exists($handler)) {
            $result = call_user_func_array($handler, [$params]);

            if (is_array($result) || is_object($result)) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                echo $result;
            }
        } else {
            http_response_code(500);
            echo "Handler function '{$handler}' not found";
        }
    } else {
        // 404 Not Found
        http_response_code(404);
        echo renderView('errors/404');
    }
} catch (Exception $e) {
    http_response_code(500);

    if (getenv('APP_ENV') !== 'production') {
        echo '<h1>Application Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Something went wrong</h1>';
        echo '<p>Please try again later.</p>';
    }

    error_log('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage());
}

// Route handlers
if (!function_exists('home')) {
    function home(array $params = []): void
    {
        header('Location: /pos');
        exit;
    }
}

if (!function_exists('pos')) {
    function pos(array $params = []): string
    {
        return renderView('pos/index');
    }
}

if (!function_exists('apiProducts')) {
    function apiProducts(array $params = []): array
{
    // Simple in-memory products for MVP
    $products = [
        [
            'id' => 1,
            'sku' => 'SKU001',
            'name' => 'USB-C Cable',
            'description' => 'High-speed charging cable',
            'price' => 499.99,
            'stock_quantity' => 100,
            'barcode' => '123456789012',
            'category' => 'Electronics'
        ],
        [
            'id' => 2,
            'sku' => 'SKU002',
            'name' => 'Coca-Cola 1.5L',
            'description' => 'Soft drink',
            'price' => 75.50,
            'stock_quantity' => 200,
            'barcode' => '234567890123',
            'category' => 'Groceries'
        ],
        [
            'id' => 3,
            'sku' => 'SKU003',
            'name' => 'Basic T-Shirt',
            'description' => 'Cotton t-shirt',
            'price' => 299.99,
            'stock_quantity' => 150,
            'barcode' => '345678901234',
            'category' => 'Clothing'
        ],
        [
            'id' => 4,
            'sku' => 'SKU004',
            'name' => 'Notebook',
            'description' => '100 pages, A5 size',
            'price' => 49.99,
            'stock_quantity' => 300,
            'barcode' => '456789012345',
            'category' => 'Stationery'
        ],
        [
            'id' => 5,
            'sku' => 'SKU005',
            'name' => 'Wireless Mouse',
            'description' => 'Bluetooth mouse',
            'price' => 399.99,
            'stock_quantity' => 80,
            'barcode' => '567890123456',
            'category' => 'Electronics'
        ],
    ];

    return $products;
}
}

if (!function_exists('apiProductsSearch')) {
    function apiProductsSearch(array $params = []): array
{
    $query = $_GET['q'] ?? '';
    $allProducts = apiProducts();

    if (empty($query)) {
        return array_slice($allProducts, 0, 20);
    }

    $filtered = array_filter($allProducts, function ($product) use ($query) {
        $search = strtolower($query);
        return strpos(strtolower($product['name']), $search) !== false ||
            strpos(strtolower($product['sku']), $search) !== false ||
            strpos($product['barcode'], $search) !== false;
    });

    return array_slice(array_values($filtered), 0, 20);
}
}

if (!function_exists('apiSales')) {
    function apiSales(array $params = []): array
{
    // FIX 2: Add error handling for JSON decode
    $rawData = file_get_contents('php://input');
    $data = json_decode($rawData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        return ['error' => 'Invalid JSON: ' . json_last_error_msg()];
    }

    // Validate data
    if (empty($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        return ['error' => 'No items in cart'];
    }

    // Calculate totals
    $subtotal = 0;
    $taxRate = 0.12;

    foreach ($data['items'] as $item) {
        // For MVP, just calculate - in real app, validate stock
        $price = $item['unit_price'] ?? 0;
        $quantity = $item['quantity'] ?? 1;
        $itemTotal = $price * $quantity;
        $subtotal += $itemTotal;
        
        // FIX 3: Add total_price to each item for receipt
        $item['total_price'] = $itemTotal;
    }

    $taxAmount = $subtotal * $taxRate;
    $discount = $data['discount_amount'] ?? 0;
    $total = $subtotal + $taxAmount - $discount;
    $amountPaid = $data['amount_paid'] ?? $total;
    $change = max(0, $amountPaid - $total);

    // Generate invoice number
    $invoiceNumber = 'INV-' . date('Ymd-His') . '-' . rand(1000, 9999);

    // FIX 4: Add total_price to items for receipt printing
    $itemsWithTotal = array_map(function($item) {
        $item['total_price'] = ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1);
        return $item;
    }, $data['items']);

    // For MVP, just return success
    return [
        'success' => true,
        'message' => 'Sale completed successfully',
        'sale' => [
            'id' => time(),
            'invoice_number' => $invoiceNumber,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount,
            'total' => $total,
            'amount_paid' => $amountPaid,
            'change_amount' => $change,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'items' => $itemsWithTotal,
            'created_at' => date('Y-m-d H:i:s'),
        ]
    ];
}
}

if (!function_exists('asset')) {
    function asset(array $params = []): string
{
    $file = $params['file'] ?? '';
    
    // FIX 5: Sanitize file path to prevent directory traversal
    $file = str_replace(['..', '\\'], '', $file);
    $filePath = PUBLIC_PATH . '/assets/' . $file;

    if (file_exists($filePath) && is_file($filePath)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        ];

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        }

        readfile($filePath);
        exit;
    }

    http_response_code(404);
    return 'Asset not found';
}
}

// Helper function to render views
if (!function_exists('renderView')) {
    function renderView(string $view, array $data = []): string
{
    $viewPath = BASE_PATH . '/resources/views/' . str_replace('.', '/', $view) . '.php';

    if (!file_exists($viewPath)) {
        // For MVP, create a simple fallback
        if ($view === 'pos/index') {
            return getSimplePOSView();
        }
        if ($view === 'errors/404') {
            return getSimple404View();
        }
        return "View not found: {$view}";
    }

    extract($data);
    ob_start();
    include $viewPath;
    return ob_get_clean();
}
}

// FIX 6: Add missing 404 view function
if (!function_exists('getSimple404View')) {
    function getSimple404View(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="text-center">
            <h1 class="display-1">404</h1>
            <p class="fs-3">Page not found</p>
            <p class="lead">The page you are looking for doesn't exist.</p>
            <a href="/" class="btn btn-primary">Go Home</a>
        </div>
    </div>
</body>
</html>
HTML;
}
}

// Fallback POS view if resources/views/pos/index.php doesn't exist
if (!function_exists('getSimplePOSView')) {
    function getSimplePOSView(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scale POS - MVP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .pos-container { height: 100vh; }
        .products-sidebar { background: #f8f9fa; overflow-y: auto; }
        .product-card { cursor: pointer; transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .cart-item { border-bottom: 1px solid #dee2e6; }
        .keyboard-shortcut { font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div id="app" class="container-fluid p-0 pos-container">
        <div class="row g-0 h-100">
            <!-- Products Sidebar -->
            <div class="col-md-4 col-lg-3 products-sidebar p-3">
                <h4><i class="fas fa-store me-2"></i>Products</h4>
                <div class="input-group mb-3">
                    <input type="text" id="search" class="form-control" placeholder="Search products...">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div id="products" class="row g-2">
                    <!-- Products loaded via JavaScript -->
                </div>
            </div>
            
            <!-- Cart Area -->
            <div class="col-md-8 col-lg-9 p-3">
                <div class="card h-100 d-flex flex-column">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>Current Sale
                            <span id="cartCount" class="badge bg-primary">0</span>
                        </h5>
                    </div>
                    <div class="card-body flex-grow-1" style="overflow-y: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cartItems">
                                <tr><td colspan="5" class="text-center">Cart is empty</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <textarea class="form-control mb-2" placeholder="Notes..." rows="2"></textarea>
                                <input type="number" id="discountAmount" class="form-control" placeholder="Discount" value="0" min="0">
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (12%):</span>
                                    <span id="tax">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                                    <span>Total:</span>
                                    <span id="total">₱0.00</span>
                                </div>
                                <div class="mt-3">
                                    <input type="number" class="form-control mb-2" placeholder="Amount Paid" id="amountPaid" step="0.01">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span>Change:</span>
                                        <span id="change">₱0.00</span>
                                    </div>
                                    <button class="btn btn-success w-100" id="processPayment">
                                        <i class="fas fa-check-circle me-2"></i>Process Payment (Ctrl+2)
                                    </button>
                                    <button class="btn btn-outline-danger w-100 mt-2" id="clearCart">
                                        <i class="fas fa-trash me-2"></i>Clear Cart (Ctrl+3)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap & JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class SimplePOS {
            constructor() {
                this.cart = [];
                this.products = [];
                this.init();
            }
            
            async init() {
                await this.loadProducts();
                this.bindEvents();
                this.updateCart();
            }
            
            async loadProducts() {
                try {
                    const response = await fetch('/api/products');
                    if (!response.ok) throw new Error('Failed to load products');
                    this.products = await response.json();
                    this.renderProducts();
                } catch (error) {
                    console.error('Error loading products:', error);
                    alert('Failed to load products. Please refresh the page.');
                }
            }
            
            renderProducts() {
                const container = document.getElementById('products');
                if (!this.products || this.products.length === 0) {
                    container.innerHTML = '<div class="col-12"><p class="text-center">No products available</p></div>';
                    return;
                }
                
                container.innerHTML = this.products.map(product => `
                    <div class="col-12">
                        <div class="product-card card mb-2" data-id="${product.id}">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">${this.escapeHtml(product.name)}</h6>
                                        <small class="text-muted">${this.escapeHtml(product.sku)}</small>
                                    </div>
                                    <div class="text-end ms-2">
                                        <div class="fw-bold">₱${this.formatPrice(product.price)}</div>
                                        <button class="btn btn-sm btn-primary mt-1 add-to-cart">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                // Add event listeners
                document.querySelectorAll('.add-to-cart').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const card = e.target.closest('.product-card');
                        const productId = parseInt(card.dataset.id);
                        this.addToCart(productId);
                    });
                });
            }
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            formatPrice(price) {
                return parseFloat(price).toFixed(2);
            }
            
            addToCart(productId) {
                const product = this.products.find(p => p.id === productId);
                if (!product) return;
                
                const existing = this.cart.find(item => item.id === productId);
                if (existing) {
                    existing.quantity += 1;
                    existing.total = existing.quantity * existing.price;
                } else {
                    this.cart.push({
                        id: productId,
                        name: product.name,
                        price: product.price,
                        quantity: 1,
                        total: product.price
                    });
                }
                
                this.updateCart();
            }
            
            updateCart() {
                const tbody = document.getElementById('cartItems');
                const cartCount = document.getElementById('cartCount');
                const subtotalEl = document.getElementById('subtotal');
                const taxEl = document.getElementById('tax');
                const totalEl = document.getElementById('total');
                const changeEl = document.getElementById('change');
                const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
                const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
                
                // Update cart items
                if (this.cart.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Cart is empty</td></tr>';
                } else {
                    tbody.innerHTML = this.cart.map((item, index) => `
                        <tr class="cart-item">
                            <td>${this.escapeHtml(item.name)}</td>
                            <td>₱${this.formatPrice(item.price)}</td>
                            <td>
                                <div class="input-group input-group-sm" style="width: 140px;">
                                    <button class="btn btn-outline-secondary" onclick="pos.updateQuantity(${index}, ${item.quantity - 1})">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" value="${item.quantity}" 
                                           onchange="pos.updateQuantity(${index}, parseInt(this.value))" min="1">
                                    <button class="btn btn-outline-secondary" onclick="pos.updateQuantity(${index}, ${item.quantity + 1})">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                            <td>₱${this.formatPrice(item.total)}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="pos.removeFromCart(${index})" title="Remove item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
                
                // Calculate totals
                const subtotal = this.cart.reduce((sum, item) => sum + item.total, 0);
                const tax = subtotal * 0.12;
                const total = subtotal + tax - discount;
                const change = Math.max(0, amountPaid - total);
                
                // Update display
                cartCount.textContent = this.cart.reduce((sum, item) => sum + item.quantity, 0);
                subtotalEl.textContent = `₱${this.formatPrice(subtotal)}`;
                taxEl.textContent = `₱${this.formatPrice(tax)}`;
                totalEl.textContent = `₱${this.formatPrice(total)}`;
                changeEl.textContent = `₱${this.formatPrice(change)}`;
            }
            
            updateQuantity(index, newQuantity) {
                newQuantity = parseInt(newQuantity);
                if (isNaN(newQuantity) || newQuantity < 1) {
                    this.cart.splice(index, 1);
                } else {
                    this.cart[index].quantity = newQuantity;
                    this.cart[index].total = this.cart[index].quantity * this.cart[index].price;
                }
                this.updateCart();
            }
            
            removeFromCart(index) {
                if (confirm('Remove this item from cart?')) {
                    this.cart.splice(index, 1);
                    this.updateCart();
                }
            }
            
            clearCart() {
                if (this.cart.length > 0 && confirm('Clear entire cart?')) {
                    this.cart = [];
                    this.updateCart();
                    document.getElementById('amountPaid').value = '';
                    document.getElementById('discountAmount').value = '0';
                }
            }
            
            bindEvents() {
                document.getElementById('amountPaid').addEventListener('input', () => this.updateCart());
                document.getElementById('discountAmount').addEventListener('input', () => this.updateCart());
                document.getElementById('processPayment').addEventListener('click', () => this.processPayment());
                document.getElementById('clearCart').addEventListener('click', () => this.clearCart());
                
                // Search functionality
                document.getElementById('search').addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase();
                    document.querySelectorAll('.product-card').forEach(card => {
                        const name = card.querySelector('h6').textContent.toLowerCase();
                        const sku = card.querySelector('small').textContent.toLowerCase();
                        card.parentElement.style.display = 
                            (name.includes(query) || sku.includes(query)) ? '' : 'none';
                    });
                });
                
                // Keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    if (e.ctrlKey || e.metaKey) {
                        switch(e.key) {
                            case '1':
                                e.preventDefault();
                                document.getElementById('search').focus();
                                break;
                            case '2':
                                e.preventDefault();
                                this.processPayment();
                                break;
                            case '3':
                                e.preventDefault();
                                this.clearCart();
                                break;
                        }
                    }
                });
            }
            
            async processPayment() {
                if (this.cart.length === 0) {
                    alert('Cart is empty. Please add items before processing payment.');
                    return;
                }
                
                const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
                const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
                const subtotal = this.cart.reduce((sum, item) => sum + item.total, 0);
                const total = subtotal + (subtotal * 0.12) - discount;
                
                if (amountPaid < total) {
                    alert(`Insufficient payment. Total is ₱${this.formatPrice(total)}. You need ₱${this.formatPrice(total - amountPaid)} more.`);
                    document.getElementById('amountPaid').focus();
                    return;
                }
                
                const saleData = {
                    items: this.cart.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity,
                        unit_price: item.price
                    })),
                    amount_paid: amountPaid,
                    payment_method: 'cash',
                    discount_amount: discount
                };
                
                try {
                    const response = await fetch('/api/sales', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(saleData)
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(`Payment processed successfully!\n\nInvoice: ${result.sale.invoice_number}\nChange: ₱${this.formatPrice(result.sale.change_amount)}`);
                        
                        // Print receipt
                        this.printReceipt(result.sale);
                        
                        // Clear cart
                        this.cart = [];
                        this.updateCart();
                        document.getElementById('amountPaid').value = '';
                        document.getElementById('discountAmount').value = '0';
                    } else {
                        alert('Error: ' + (result.error || 'Unknown error occurred'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error processing payment: ' + error.message);
                }
            }
            
            printReceipt(sale) {
                const receipt = window.open('', '_blank', 'width=400,height=600');
                if (!receipt) {
                    alert('Please allow popups to print the receipt');
                    return;
                }
                
                receipt.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Receipt - ${sale.invoice_number}</title>
                        <style>
                            body { 
                                font-family: 'Courier New', monospace; 
                                width: 300px; 
                                padding: 20px; 
                                margin: 0 auto;
                            }
                            .header { text-align: center; margin-bottom: 20px; }
                            .header h3 { margin: 5px 0; }
                            .total { font-weight: bold; font-size: 1.2em; }
                            table { width: 100%; border-collapse: collapse; }
                            td { padding: 3px 0; }
                            hr { border: none; border-top: 1px dashed #000; margin: 10px 0; }
                            .text-right { text-align: right; }
                            .footer { text-align: center; margin-top: 20px; }
                            @media print {
                                body { width: auto; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h3>Scale POS</h3>
                            <p>Thank you for your purchase!</p>
                        </div>
                        <hr>
                        <p><strong>Invoice:</strong> ${sale.invoice_number}</p>
                        <p><strong>Date:</strong> ${sale.created_at}</p>
                        <hr>
                        <table>
                            <thead>
                                <tr>
                                    <td><strong>Item</strong></td>
                                    <td class="text-right"><strong>Qty</strong></td>
                                    <td class="text-right"><strong>Price</strong></td>
                                    <td class="text-right"><strong>Total</strong></td>
                                </tr>
                            </thead>
                            <tbody>
                                ${sale.items.map(item => `
                                    <tr>
                                        <td>Product #${item.product_id}</td>
                                        <td class="text-right">${item.quantity}</td>
                                        <td class="text-right">₱${this.formatPrice(item.unit_price)}</td>
                                        <td class="text-right">₱${this.formatPrice(item.total_price)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        <hr>
                        <table>
                            <tr>
                                <td>Subtotal:</td>
                                <td class="text-right">₱${this.formatPrice(sale.subtotal)}</td>
                            </tr>
                            <tr>
                                <td>Tax (12%):</td>
                                <td class="text-right">₱${this.formatPrice(sale.tax_amount)}</td>
                            </tr>
                            ${sale.discount_amount > 0 ? `
                            <tr>
                                <td>Discount:</td>
                                <td class="text-right">-₱${this.formatPrice(sale.discount_amount)}</td>
                            </tr>
                            ` : ''}
                            <tr class="total">
                                <td>TOTAL:</td>
                                <td class="text-right">₱${this.formatPrice(sale.total)}</td>
                            </tr>
                            <tr>
                                <td>Amount Paid:</td>
                                <td class="text-right">₱${this.formatPrice(sale.amount_paid)}</td>
                            </tr>
                            <tr>
                                <td>Change:</td>
                                <td class="text-right">₱${this.formatPrice(sale.change_amount)}</td>
                            </tr>
                            <tr>
                                <td>Payment Method:</td>
                                <td class="text-right">${sale.payment_method.toUpperCase()}</td>
                            </tr>
                        </table>
                        <hr>
                        <div class="footer">
                            <p><strong>Thank you for your business!</strong></p>
                            <p>Please come again</p>
                            <p style="font-size: 0.9em; margin-top: 10px;">
                                This serves as your official receipt
                            </p>
                        </div>
                        <script>
                            window.onload = function() {
                                setTimeout(function() {
                                    window.print();
                                }, 500);
                            };
                        </script>
                    </body>
                    </html>
                `);
                receipt.document.close();
            }
        }
        
        // Initialize POS
        const pos = new SimplePOS();
        window.pos = pos; // Make available globally for button events
    </script>
</body>
</html>
HTML;
}
}