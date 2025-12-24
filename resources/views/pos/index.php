<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scale POS System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS -->
    <style>
        .product-card {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pos-sidebar {
            background: #f8f9fa;
            height: 100vh;
            overflow-y: auto;
        }

        .pos-main {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .cart-header {
            background: #fff;
            border-bottom: 2px solid #dee2e6;
        }

        .cart-body {
            flex: 1;
            overflow-y: auto;
        }

        .cart-footer {
            background: #fff;
            border-top: 2px solid #dee2e6;
        }

        .keyboard-shortcut {
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Products Sidebar -->
            <div class="col-md-4 col-lg-3 pos-sidebar p-3">
                <div class="mb-3">
                    <h4><i class="fas fa-store me-2"></i>Products</h4>
                    <div class="input-group">
                        <input type="text"
                            id="productSearch"
                            class="form-control"
                            placeholder="Search products (Ctrl+1)"
                            autocomplete="off"
                            autofocus>
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="btn-group flex-wrap" role="group">
                        <button type="button" class="btn btn-outline-primary active category-btn" data-category="all">
                            All
                        </button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="electronics">
                            Electronics
                        </button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="groceries">
                            Groceries
                        </button>
                    </div>
                </div>

                <div id="productList" class="row g-2">
                    <!-- Products will be loaded here -->
                </div>
            </div>

            <!-- Cart & Checkout Area -->
            <div class="col-md-8 col-lg-9 pos-main">
                <!-- Cart Header -->
                <div class="cart-header p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Current Sale
                            <span id="cartCount" class="badge bg-primary ms-2">0</span>
                        </h3>
                        <div>
                            <button id="holdCart" class="btn btn-warning btn-sm me-2">
                                <i class="fas fa-pause me-1"></i>Hold
                            </button>
                            <button id="clearCart" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash me-1"></i>Clear <span class="keyboard-shortcut">(Ctrl+3)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart Body -->
                <div class="cart-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="40%">Product</th>
                                    <th width="15%">Price</th>
                                    <th width="20%">Quantity</th>
                                    <th width="15%">Total</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="cartTable">
                                <tr id="emptyCartMessage">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                        <p class="mb-0">Your cart is empty</p>
                                        <small>Add products from the left panel</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cart Footer -->
                <div class="cart-footer p-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Customer Notes</label>
                                <textarea id="customerNotes" class="form-control" rows="2" placeholder="Add notes..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Discount</label>
                                <div class="input-group">
                                    <input type="number" id="discountAmount" class="form-control" value="0" min="0" step="0.01">
                                    <span class="input-group-text">₱</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (12%):</span>
                                    <span id="taxAmount">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount:</span>
                                    <span id="displayDiscount">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                                    <span>Total:</span>
                                    <span id="totalAmount">₱0.00</span>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Payment Method</label>
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary active payment-btn" data-method="cash">
                                            <i class="fas fa-money-bill me-1"></i>Cash
                                        </button>
                                        <button type="button" class="btn btn-outline-primary payment-btn" data-method="card">
                                            <i class="fas fa-credit-card me-1"></i>Card
                                        </button>
                                        <button type="button" class="btn btn-outline-primary payment-btn" data-method="mobile">
                                            <i class="fas fa-mobile-alt me-1"></i>Mobile
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Amount Paid</label>
                                        <input type="number"
                                            id="amountPaid"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Change</label>
                                        <div class="form-control bg-white" id="changeAmount">
                                            ₱0.00
                                        </div>
                                    </div>
                                </div>

                                <button id="processPayment" class="btn btn-success btn-lg w-100 py-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Process Payment <span class="keyboard-shortcut">(Ctrl+2)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- POS JavaScript -->
    <script>
        class POSSystem {
            constructor() {
                this.cart = [];
                this.selectedPaymentMethod = 'cash';
                this.taxRate = 0.12; // 12% VAT

                this.init();
            }

            init() {
                this.loadProducts();
                this.bindEvents();
                this.setupKeyboardShortcuts();
                this.updateCartDisplay();
            }

            bindEvents() {
                // Product search
                $('#productSearch').on('input', () => this.searchProducts());
                $('#clearSearch').on('click', () => {
                    $('#productSearch').val('').focus();
                    this.loadProducts();
                });

                // Category filter
                $('.category-btn').on('click', (e) => {
                    $('.category-btn').removeClass('active');
                    $(e.target).addClass('active');
                    this.filterProducts($(e.target).data('category'));
                });

                // Payment method
                $('.payment-btn').on('click', (e) => {
                    $('.payment-btn').removeClass('active');
                    $(e.target).addClass('active');
                    this.selectedPaymentMethod = $(e.target).data('method');
                });

                // Cart actions
                $('#clearCart').on('click', () => this.clearCart());
                $('#holdCart').on('click', () => this.holdCart());

                // Payment
                $('#discountAmount, #amountPaid').on('input', () => this.calculateTotals());
                $('#processPayment').on('click', () => this.processPayment());
            }

            setupKeyboardShortcuts() {
                $(document).on('keydown', (e) => {
                    // Ctrl+1: Focus search
                    if (e.ctrlKey && e.key === '1') {
                        e.preventDefault();
                        $('#productSearch').focus().select();
                    }

                    // Ctrl+2: Process payment
                    if (e.ctrlKey && e.key === '2') {
                        e.preventDefault();
                        this.processPayment();
                    }

                    // Ctrl+3: Clear cart
                    if (e.ctrlKey && e.key === '3') {
                        e.preventDefault();
                        this.clearCart();
                    }

                    // Enter in search: Add first product
                    if (e.key === 'Enter' && $('#productSearch').is(':focus')) {
                        e.preventDefault();
                        const firstProduct = $('#productList .product-card').first();
                        if (firstProduct.length) {
                            this.addToCart(firstProduct.data('id'));
                        }
                    }

                    // Escape: Clear search
                    if (e.key === 'Escape' && $('#productSearch').is(':focus')) {
                        e.preventDefault();
                        $('#productSearch').val('').blur();
                        this.loadProducts();
                    }
                });
            }

            async loadProducts(category = 'all') {
                try {
                    const response = await fetch(`/api/products${category !== 'all' ? '?category=' + category : ''}`);
                    const products = await response.json();
                    this.renderProducts(products);
                } catch (error) {
                    console.error('Error loading products:', error);
                }
            }

            async searchProducts() {
                const query = $('#productSearch').val();
                if (query.length < 2) {
                    this.loadProducts();
                    return;
                }

                try {
                    const response = await fetch(`/api/products/search?q=${encodeURIComponent(query)}`);
                    const products = await response.json();
                    this.renderProducts(products);
                } catch (error) {
                    console.error('Error searching products:', error);
                }
            }

            filterProducts(category) {
                this.loadProducts(category);
            }

            renderProducts(products) {
                const container = $('#productList');
                container.empty();

                if (products.length === 0) {
                    container.html(`
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-3x mb-3"></i>
                            <p class="mb-0">No products found</p>
                        </div>
                    `);
                    return;
                }

                products.forEach(product => {
                    const cartItem = this.cart.find(item => item.id === product.id);
                    const inCart = cartItem ? cartItem.quantity : 0;

                    const card = `
                        <div class="col-6 col-md-12 col-lg-6 mb-2">
                            <div class="product-card card" data-id="${product.id}">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-2">
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-box text-muted"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="card-title mb-0 text-truncate" title="${product.name}">
                                                ${product.name}
                                            </h6>
                                            <small class="text-muted">${product.sku}</small>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span class="fw-bold text-primary">₱${parseFloat(product.price).toFixed(2)}</span>
                                                <span class="badge ${product.stock_quantity > 10 ? 'bg-success' : 'bg-warning'}">
                                                    ${product.stock_quantity} in stock
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 ms-2">
                                            <button class="btn btn-sm ${inCart ? 'btn-primary' : 'btn-outline-primary'} add-to-cart">
                                                ${inCart ? `<i class="fas fa-check me-1"></i>${inCart}` : '<i class="fas fa-plus"></i>'}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    container.append(card);
                });

                // Add click handlers to new product cards
                $('.product-card').on('click', (e) => {
                    if (!$(e.target).closest('.add-to-cart').length) {
                        const productId = $(e.currentTarget).data('id');
                        this.addToCart(productId);
                    }
                });

                $('.add-to-cart').on('click', (e) => {
                    e.stopPropagation();
                    const productId = $(e.currentTarget).closest('.product-card').data('id');
                    this.addToCart(productId);
                });
            }

            async addToCart(productId) {
                try {
                    const response = await fetch(`/api/products`);
                    const products = await response.json();
                    const product = products.find(p => p.id == productId);

                    if (!product) {
                        this.showAlert('error', 'Product not found');
                        return;
                    }

                    const existingItem = this.cart.find(item => item.id == productId);

                    if (existingItem) {
                        if (existingItem.quantity >= product.stock_quantity) {
                            this.showAlert('error', `Only ${product.stock_quantity} items available`);
                            return;
                        }
                        existingItem.quantity += 1;
                        existingItem.total = existingItem.quantity * existingItem.price;
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            sku: product.sku,
                            price: parseFloat(product.price),
                            quantity: 1,
                            total: parseFloat(product.price),
                            stock: product.stock_quantity
                        });
                    }

                    this.updateCartDisplay();
                    this.loadProducts($('.category-btn.active').data('category'));

                } catch (error) {
                    console.error('Error adding to cart:', error);
                    this.showAlert('error', 'Error adding product to cart');
                }
            }

            removeFromCart(index) {
                if (index >= 0 && index < this.cart.length) {
                    this.cart.splice(index, 1);
                    this.updateCartDisplay();
                    this.loadProducts($('.category-btn.active').data('category'));
                }
            }

            updateQuantity(index, quantity) {
                if (index >= 0 && index < this.cart.length) {
                    if (quantity < 1) {
                        this.removeFromCart(index);
                        return;
                    }

                    const item = this.cart[index];
                    if (quantity > item.stock) {
                        this.showAlert('error', `Only ${item.stock} items available`);
                        quantity = item.stock;
                    }

                    item.quantity = quantity;
                    item.total = quantity * item.price;
                    this.updateCartDisplay();
                }
            }

            updateCartDisplay() {
                const tbody = $('#cartTable');

                if (this.cart.length === 0) {
                    tbody.html(`
                        <tr id="emptyCartMessage">
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                <p class="mb-0">Your cart is empty</p>
                                <small>Add products from the left panel</small>
                            </td>
                        </tr>
                    `);
                    $('#cartCount').text('0');
                    this.calculateTotals();
                    return;
                }

                $('#emptyCartMessage').remove();

                let html = '';
                this.cart.forEach((item, index) => {
                    html += `
                        <tr class="cart-item">
                            <td>${index + 1}</td>
                            <td>
                                <div class="fw-bold">${item.name}</div>
                                <small class="text-muted">${item.sku}</small>
                            </td>
                            <td>₱${item.price.toFixed(2)}</td>
                            <td>
                                <div class="input-group input-group-sm" style="width: 120px;">
                                    <button class="btn btn-outline-secondary quantity-btn decrement" data-index="${index}">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           class="form-control text-center quantity-input" 
                                           value="${item.quantity}" 
                                           min="1" 
                                           max="${item.stock}"
                                           data-index="${index}">
                                    <button class="btn btn-outline-secondary quantity-btn increment" data-index="${index}">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="fw-bold">₱${item.total.toFixed(2)}</td>
                            <td>
                                <button class="btn btn-outline-danger btn-sm remove-item" data-index="${index}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                tbody.html(html);
                $('#cartCount').text(this.cart.reduce((sum, item) => sum + item.quantity, 0));

                // Bind quantity controls
                $('.decrement').on('click', (e) => {
                    const index = $(e.currentTarget).data('index');
                    const currentQty = this.cart[index].quantity;
                    this.updateQuantity(index, currentQty - 1);
                });

                $('.increment').on('click', (e) => {
                    const index = $(e.currentTarget).data('index');
                    const currentQty = this.cart[index].quantity;
                    this.updateQuantity(index, currentQty + 1);
                });

                $('.quantity-input').on('change', (e) => {
                    const index = $(e.currentTarget).data('index');
                    const quantity = parseInt($(e.currentTarget).val()) || 1;
                    this.updateQuantity(index, quantity);
                });

                $('.remove-item').on('click', (e) => {
                    const index = $(e.currentTarget).data('index');
                    this.removeFromCart(index);
                });

                this.calculateTotals();
            }

            calculateTotals() {
                const subtotal = this.cart.reduce((sum, item) => sum + item.total, 0);
                const taxAmount = subtotal * this.taxRate;
                const discount = parseFloat($('#discountAmount').val()) || 0;
                const total = Math.max(0, subtotal + taxAmount - discount);
                const amountPaid = parseFloat($('#amountPaid').val()) || 0;
                const change = Math.max(0, amountPaid - total);

                $('#subtotal').text(`₱${subtotal.toFixed(2)}`);
                $('#taxAmount').text(`₱${taxAmount.toFixed(2)}`);
                $('#displayDiscount').text(`₱${discount.toFixed(2)}`);
                $('#totalAmount').text(`₱${total.toFixed(2)}`);
                $('#changeAmount').text(`₱${change.toFixed(2)}`);

                return {
                    subtotal,
                    taxAmount,
                    total,
                    change
                };
            }

            async processPayment() {
                const {
                    total
                } = this.calculateTotals();
                const amountPaid = parseFloat($('#amountPaid').val()) || 0;

                if (this.cart.length === 0) {
                    this.showAlert('error', 'Cart is empty');
                    return;
                }

                if (amountPaid < total) {
                    this.showAlert('error', `Insufficient payment. Need ₱${(total - amountPaid).toFixed(2)} more`);
                    return;
                }

                const saleData = {
                    items: this.cart.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity,
                        unit_price: item.price
                    })),
                    amount_paid: amountPaid,
                    payment_method: this.selectedPaymentMethod,
                    discount_amount: parseFloat($('#discountAmount').val()) || 0,
                    notes: $('#customerNotes').val()
                };

                try {
                    const response = await fetch('/api/sales', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(saleData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.showAlert('success', result.message);
                        this.printReceipt(result.sale);
                        this.clearCart();
                    } else {
                        this.showAlert('error', result.message);
                    }

                } catch (error) {
                    console.error('Error processing payment:', error);
                    this.showAlert('error', 'Error processing payment');
                }
            }

            printReceipt(sale) {
                const receiptWindow = window.open('', '_blank');
                receiptWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Receipt - ${sale.invoice_number}</title>
                        <style>
                            body { font-family: monospace; width: 80mm; padding: 10px; }
                            .header { text-align: center; margin-bottom: 10px; }
                            .divider { border-top: 1px dashed #000; margin: 10px 0; }
                            .total { font-weight: bold; font-size: 1.2em; }
                            .text-right { text-align: right; }
                            table { width: 100%; border-collapse: collapse; }
                            td { padding: 2px 0; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h3>Scale POS</h3>
                            <p>Thank you for your purchase!</p>
                        </div>
                        
                        <div class="divider"></div>
                        
                        <p><strong>Invoice:</strong> ${sale.invoice_number}</p>
                        <p><strong>Date:</strong> ${new Date().toLocaleString()}</p>
                        
                        <div class="divider"></div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${sale.items.map(item => `
                                    <tr>
                                        <td>Product ${item.product_id}</td>
                                        <td class="text-right">${item.quantity}</td>
                                        <td class="text-right">₱${parseFloat(item.total_price).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        
                        <div class="divider"></div>
                        
                        <table>
                            <tr>
                                <td>Total:</td>
                                <td class="text-right total">₱${parseFloat(sale.total).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td>Paid:</td>
                                <td class="text-right">₱${(sale.amount_paid || sale.total + sale.change_amount).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <td>Change:</td>
                                <td class="text-right">₱${parseFloat(sale.change_amount).toFixed(2)}</td>
                            </tr>
                        </table>
                        
                        <div class="divider"></div>
                        
                        <div class="header">
                            <p>Thank you for shopping with us!</p>
                            <p>Please come again</p>
                        </div>
                        
                        <script>
                            window.onload = () => {
                                window.print();
                                setTimeout(() => window.close(), 1000);
                            };
                        <\/script>
                    </body>
                    </html>
                `);
                receiptWindow.document.close();
            }

            holdCart() {
                if (this.cart.length === 0) {
                    this.showAlert('warning', 'Cart is empty');
                    return;
                }

                const holdId = 'HOLD-' + Date.now();
                localStorage.setItem(holdId, JSON.stringify({
                    cart: this.cart,
                    timestamp: new Date().toISOString()
                }));

                this.showAlert('success', `Cart held with ID: ${holdId}`);
                this.clearCart();
            }

            clearCart() {
                this.cart = [];
                this.updateCartDisplay();
                $('#customerNotes').val('');
                $('#discountAmount').val('0');
                $('#amountPaid').val('');
                this.loadProducts($('.category-btn.active').data('category'));
            }

            showAlert(type, message) {
                const alertClass = {
                    success: 'alert-success',
                    error: 'alert-danger',
                    warning: 'alert-warning',
                    info: 'alert-info'
                } [type] || 'alert-info';

                const alert = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                         style="top: 20px; right: 20px; z-index: 9999; max-width: 300px;">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);

                $('body').append(alert);

                setTimeout(() => {
                    alert.alert('close');
                }, 3000);
            }
        }

        // Initialize POS when page loads
        $(document).ready(() => {
            window.pos = new POSSystem();
        });
    </script>
</body>

</html>