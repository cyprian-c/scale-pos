<?php
// routes/web.php

use App\Core\Router;

/** @var Router $router */
$router = $GLOBALS['router'];

// Home/POS page
$router->get('/', function() use ($router) {
    // For now, redirect to POS
    header('Location: /pos');
    exit;
});


// POS Interface
$router->get('/pos', function() use ($router) {
    return $router->renderView('pos.index');
});

// API endpoints
$router->get('/api/products', function() {
    $app = $GLOBALS['app'];
    $db = $app->get('db');
    
    $stmt = $db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name");
    $products = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    return json_encode($products);
});

$router->get('/api/products/search', function() {
    $app = $GLOBALS['app'];
    $db = $app->get('db');
    
    $search = $_GET['q'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM products WHERE is_active = 1 AND (name LIKE ? OR sku LIKE ? OR barcode = ?) LIMIT 20");
    $stmt->execute(["%{$search}%", "%{$search}%", $search]);
    $products = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    return json_encode($products);
});

$router->post('/api/sales', function() {
    $app = $GLOBALS['app'];
    $db = $app->get('db');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Start transaction
        $db->beginTransaction();
        
        // Generate invoice number
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculate totals
        $subtotal = 0;
        $taxRate = 0.12; // 12% VAT
        
        foreach ($data['items'] as &$item) {
            // Get product price and stock
            $stmt = $db->prepare("SELECT price, stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Product not found");
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Insufficient stock for product ID: {$item['product_id']}");
            }
            
            $itemPrice = $item['unit_price'] ?? $product['price'];
            $itemTotal = $itemPrice * $item['quantity'];
            $subtotal += $itemTotal;
            
            // Update product stock
            $updateStmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $updateStmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount - ($data['discount_amount'] ?? 0);
        $changeAmount = ($data['amount_paid'] ?? 0) - $total;
        
        if ($changeAmount < 0) {
            throw new Exception("Insufficient payment");
        }
        
        // Insert sale
        $stmt = $db->prepare("
            INSERT INTO sales (invoice_number, subtotal, tax_amount, discount_amount, total, amount_paid, change_amount, payment_method, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([
            $invoiceNumber,
            $subtotal,
            $taxAmount,
            $data['discount_amount'] ?? 0,
            $total,
            $data['amount_paid'] ?? $total,
            $changeAmount,
            $data['payment_method'] ?? 'cash',
            $data['notes'] ?? '',
        ]);
        
        $saleId = $db->lastInsertId();
        
        // Insert sale items
        foreach ($data['items'] as $item) {
            $itemStmt = $db->prepare("
                INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price, tax_amount)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $itemTax = ($item['unit_price'] * $item['quantity']) * $taxRate;
            
            $itemStmt->execute([
                $saleId,
                $item['product_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['unit_price'] * $item['quantity'],
                $itemTax,
            ]);
        }
        
        $db->commit();
        
        // Get complete sale data
        $saleStmt = $db->prepare("
            SELECT s.*, 
                   GROUP_CONCAT(json_object('product_id', si.product_id, 'quantity', si.quantity, 'unit_price', si.unit_price, 'total_price', si.total_price)) as items
            FROM sales s
            LEFT JOIN sale_items si ON s.id = si.sale_id
            WHERE s.id = ?
            GROUP BY s.id
        ");
        
        $saleStmt->execute([$saleId]);
        $sale = $saleStmt->fetch();
        
        header('Content-Type: application/json');
        return json_encode([
            'success' => true,
            'message' => 'Sale completed successfully',
            'sale' => [
                'id' => $saleId,
                'invoice_number' => $invoiceNumber,
                'total' => $total,
                'change_amount' => $changeAmount,
                'items' => json_decode('[' . $sale['items'] . ']', true),
            ]
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        http_response_code(400);
        header('Content-Type: application/json');
        return json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }
});

// Static assets (for MVP)
$router->get('/assets/{file}', function($file) {
    $assetPath = PUBLIC_PATH . '/assets/' . $file;
    
    if (file_exists($assetPath)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];
        
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (isset($mimeTypes[$extension])) {
            header('Content-Type: ' . $mimeTypes[$extension]);
        }
        
        readfile($assetPath);
        return '';
    }
    
    http_response_code(404);
    return 'Asset not found';
});

// 404 fallback
$router->get('.*', function() use ($router) {
    http_response_code(404);
    return $router->renderView('errors.404');
});
