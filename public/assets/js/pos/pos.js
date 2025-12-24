// public/assets/js/pos/pos.js
class POSSystem {
    constructor() {
        this.cart = [];
        this.selectedPaymentMethod = 'cash';
        this.taxRate = 0.12; // 12% VAT
        
        this.initEvents();
        this.loadProducts();
    }

    initEvents() {
        // Product search
        $('#productSearch').on('input', debounce(() => this.searchProducts(), 300));
        
        // Category filter
        $('#productCategories button').on('click', (e) => {
            $('#productCategories button').removeClass('active');
            $(e.target).addClass('active');
            this.filterByCategory($(e.target).data('category'));
        });

        // Quantity controls
        $(document).on('click', '.btn-increment', (e) => {
            const productId = $(e.target).closest('.product-card').data('id');
            this.addToCart(productId);
        });

        $(document).on('click', '.btn-decrement', (e) => {
            const productId = $(e.target).closest('.product-card').data('id');
            this.removeFromCart(productId);
        });

        // Cart controls
        $(document).on('click', '.remove-item', (e) => {
            const index = $(e.target).closest('tr').data('index');
            this.removeCartItem(index);
        });

        $(document).on('input', '.cart-qty', (e) => {
            const index = $(e.target).closest('tr').data('index');
            const qty = parseInt($(e.target).val()) || 1;
            this.updateQuantity(index, qty);
        });

        // Payment method
        $('button[data-method]').on('click', (e) => {
            $('button[data-method]').removeClass('active');
            $(e.target).addClass('active');
            this.selectedPaymentMethod = $(e.target).data('method');
        });

        // Amount paid calculation
        $('#amountPaid').on('input', () => this.calculateChange());

        // Process payment
        $('#processPayment').on('click', () => this.processPayment());

        // Clear cart
        $('#clearCart').on('click', () => this.clearCart());

        // Keyboard shortcuts
        $(document).on('keydown', (e) => {
            // F1 - Focus search
            if (e.key === 'F1') {
                e.preventDefault();
                $('#productSearch').focus();
            }
            // F2 - Process payment
            if (e.key === 'F2') {
                e.preventDefault();
                this.processPayment();
            }
            // F3 - Clear cart
            if (e.key === 'F3') {
                e.preventDefault();
                this.clearCart();
            }
        });
    }

    async loadProducts(categoryId = 'all') {
        try {
            const response = await fetch(`/pos/products?category=${categoryId}`);
            const products = await response.json();
            this.renderProducts(products);
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    async searchProducts() {
        const searchTerm = $('#productSearch').val();
        if (searchTerm.length < 2) return;

        try {
            const response = await fetch(`/pos/products/search?q=${encodeURIComponent(searchTerm)}`);
            const products = await response.json();
            this.renderProducts(products);
        } catch (error) {
            console.error('Error searching products:', error);
        }
    }

    filterByCategory(categoryId) {
        if (categoryId === 'all') {
            this.loadProducts();
        } else {
            this.loadProducts(categoryId);
        }
    }

    renderProducts(products) {
        const grid = $('#productGrid');
        grid.empty();

        products.forEach(product => {
            const cartItem = this.cart.find(item => item.product_id === product.id);
            const inCartQty = cartItem ? cartItem.quantity : 0;
            
            const card = `
                <div class="col-6 col-sm-4 col-lg-3">
                    <div class="product-card card h-100" data-id="${product.id}">
                        <div class="card-body text-center p-2">
                            ${product.image_path ? 
                                `<img src="/storage/${product.image_path}" class="img-fluid mb-2" alt="${product.name}" style="height: 100px; object-fit: contain;">` :
                                `<div class="bg-light d-flex align-items-center justify-content-center mb-2" style="height: 100px;">
                                    <i class="fas fa-box text-muted fa-2x"></i>
                                </div>`
                            }
                            <h6 class="card-title mb-1 text-truncate" title="${product.name}">
                                ${product.name}
                            </h6>
                            <div class="mb-1">
                                <small class="text-muted">${product.sku}</small>
                            </div>
                            <div class="fw-bold text-primary mb-2">
                                ₱${parseFloat(product.price).toFixed(2)}
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge ${product.stock_quantity > 10 ? 'bg-success' : 'bg-warning'}">
                                    Stock: ${product.stock_quantity}
                                </span>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary btn-decrement" ${inCartQty === 0 ? 'disabled' : ''}>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <button class="btn btn-outline-dark" disabled>
                                        ${inCartQty}
                                    </button>
                                    <button class="btn btn-outline-primary btn-increment" ${inCartQty >= product.stock_quantity ? 'disabled' : ''}>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            grid.append(card);
        });
    }

    async addToCart(productId) {
        try {
            const response = await fetch(`/pos/products/${productId}/details`);
            const product = await response.json();
            
            const existingItem = this.cart.find(item => item.product_id === productId);
            
            if (existingItem) {
                if (existingItem.quantity >= product.stock) {
                    this.showAlert('error', `Only ${product.stock} items available in stock`);
                    return;
                }
                existingItem.quantity += 1;
                existingItem.total = existingItem.quantity * existingItem.unit_price;
            } else {
                this.cart.push({
                    product_id: productId,
                    name: product.name,
                    sku: product.sku,
                    unit_price: parseFloat(product.price),
                    quantity: 1,
                    total: parseFloat(product.price),
                    stock: product.stock
                });
            }
            
            this.updateCart();
            this.loadProducts(); // Refresh product grid to update quantities
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showAlert('error', 'Error adding product to cart');
        }
    }

    removeFromCart(productId) {
        const existingItem = this.cart.find(item => item.product_id === productId);
        
        if (existingItem) {
            existingItem.quantity -= 1;
            existingItem.total = existingItem.quantity * existingItem.unit_price;
            
            if (existingItem.quantity <= 0) {
                this.cart = this.cart.filter(item => item.product_id !== productId);
            }
            
            this.updateCart();
            this.loadProducts();
        }
    }

    removeCartItem(index) {
        this.cart.splice(index, 1);
        this.updateCart();
        this.loadProducts();
    }

    updateQuantity(index, quantity) {
        if (quantity < 1) {
            this.cart.splice(index, 1);
        } else {
            const item = this.cart[index];
            if (quantity > item.stock) {
                this.showAlert('error', `Only ${item.stock} items available in stock`);
                quantity = item.stock;
                $('.cart-qty').eq(index).val(quantity);
            }
            item.quantity = quantity;
            item.total = quantity * item.unit_price;
        }
        this.updateCart();
        this.loadProducts();
    }

    updateCart() {
        this.renderCartItems();
        this.calculateTotals();
        this.updateCartCount();
        this.calculateChange();
    }

    renderCartItems() {
        const tbody = $('#cartItems');
        tbody.empty();

        if (this.cart.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <p class="mb-0">Your cart is empty</p>
                        <small>Add products from the left panel</small>
                    </td>
                </tr>
            `);
            return;
        }

        this.cart.forEach((item, index) => {
            const row = `
                <tr data-index="${index}">
                    <td>${index + 1}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2">
                                <i class="fas fa-box fa-lg text-muted"></i>
                            </div>
                            <div>
                                <div class="fw-bold">${item.name}</div>
                                <small class="text-muted">${item.sku}</small>
                            </div>
                        </div>
                    </td>
                    <td>₱${item.unit_price.toFixed(2)}</td>
                    <td>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <button class="btn btn-outline-secondary btn-decrement" type="button">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   class="form-control text-center cart-qty" 
                                   value="${item.quantity}" 
                                   min="1" 
                                   max="${item.stock}">
                            <button class="btn btn-outline-secondary btn-increment" type="button">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td class="fw-bold">₱${item.total.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-outline-danger btn-sm remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    calculateTotals() {
        const subtotal = this.cart.reduce((sum, item) => sum + item.total, 0);
        const taxAmount = subtotal * this.taxRate;
        const total = subtotal + taxAmount;

        $('#subtotal').text(`₱${subtotal.toFixed(2)}`);
        $('#taxAmount').text(`₱${taxAmount.toFixed(2)}`);
        $('#totalAmount').text(`₱${total.toFixed(2)}`);

        return { subtotal, taxAmount, total };
    }

    calculateChange() {
        const { total } = this.calculateTotals();
        const amountPaid = parseFloat($('#amountPaid').val()) || 0;
        const change = amountPaid - total;

        $('#changeAmount').text(`₱${change >= 0 ? change.toFixed(2) : '0.00'}`);
        
        if (change < 0) {
            $('#changeAmount').addClass('text-danger');
        } else {
            $('#changeAmount').removeClass('text-danger');
        }

        return change;
    }

    updateCartCount() {
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        $('#cartCount').text(totalItems);
    }

    async processPayment() {
        const { total } = this.calculateTotals();
        const amountPaid = parseFloat($('#amountPaid').val()) || 0;
        
        if (this.cart.length === 0) {
            this.showAlert('error', 'Cart is empty');
            return;
        }

        if (amountPaid < total) {
            this.showAlert('error', 'Insufficient payment amount');
            return;
        }

        const saleData = {
            items: this.cart.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.unit_price
            })),
            amount_paid: amountPaid,
            payment_method: this.selectedPaymentMethod,
            customer_id: $('#customerSelect').val() || null,
            notes: $('#saleNotes').val(),
            discount_amount: 0 // Can be extended to add discount functionality
        };

        try {
            const response = await fetch('/pos/process-sale', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
        // Create receipt content
        const receiptContent = `
            <div id="receipt" style="width: 80mm; font-family: monospace; padding: 10px;">
                <div style="text-align: center; margin-bottom: 10px;">
                    <h4 style="margin: 0;">Your Business Name</h4>
                    <p style="margin: 0; font-size: 12px;">Business Address</p>
                    <p style="margin: 0; font-size: 12px;">Phone: (123) 456-7890</p>
                    <hr style="border-top: 1px dashed #000;">
                </div>
                
                <div style="margin-bottom: 10px;">
                    <p style="margin: 0;">Invoice: ${sale.invoice_number}</p>
                    <p style="margin: 0;">Date: ${new Date(sale.created_at).toLocaleString()}</p>
                    <p style="margin: 0;">Cashier: ${sale.user?.name || 'System'}</p>
                    <hr style="border-top: 1px dashed #000;">
                </div>
                
                <table style="width: 100%; margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Item</th>
                            <th style="text-align: right;">Qty</th>
                            <th style="text-align: right;">Price</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sale.items.map(item => `
                            <tr>
                                <td>${item.product.name}</td>
                                <td style="text-align: right;">${item.quantity}</td>
                                <td style="text-align: right;">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                                <td style="text-align: right;">₱${parseFloat(item.total_price).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                
                <hr style="border-top: 1px dashed #000;">
                
                <div style="text-align: right; margin-bottom: 10px;">
                    <p style="margin: 0;">Subtotal: ₱${parseFloat(sale.subtotal).toFixed(2)}</p>
                    <p style="margin: 0;">Tax: ₱${parseFloat(sale.tax_amount).toFixed(2)}</p>
                    <p style="margin: 0;">Discount: ₱${parseFloat(sale.discount_amount).toFixed(2)}</p>
                    <p style="margin: 0; font-weight: bold;">Total: ₱${parseFloat(sale.total).toFixed(2)}</p>
                </div>
                
                <div style="text-align: right; margin-bottom: 10px;">
                    <p style="margin: 0;">Paid: ₱${parseFloat(sale.amount_paid).toFixed(2)}</p>
                    <p style="margin: 0;">Change: ₱${parseFloat(sale.change_amount).toFixed(2)}</p>
                    <p style="margin: 0;">Method: ${sale.payment_method.toUpperCase()}</p>
                </div>
                
                <hr style="border-top: 1px dashed #000;">
                
                <div style="text-align: center; margin-top: 10px;">
                    <p style="margin: 0; font-size: 12px;">Thank you for your purchase!</p>
                    <p style="margin: 0; font-size: 10px;">Returns within 7 days with receipt</p>
                </div>
            </div>
        `;

        // Open print window
        const printWindow = window.open('', '_blank', 'width=350,height=600');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Receipt - ${sale.invoice_number}</title>
                    <style>
                        @media print {
                            body { margin: 0; padding: 0; }
                            #receipt { width: 80mm !important; }
                        }
                    </style>
                </head>
                <body onload="window.print(); window.close();">
                    ${receiptContent}
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    clearCart() {
        this.cart = [];
        this.updateCart();
        $('#customerSelect').val('');
        $('#saleNotes').val('');
        $('#amountPaid').val('');
        $('#discountAmount').text('₱0.00');
        this.loadProducts();
    }

    showAlert(type, message) {
        // Create alert element
        const alert = $(`
            <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alert.alert('close');
        }, 5000);
    }
}

// Utility function for debouncing
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize POS when document is ready
$(document).ready(function() {
    window.posSystem = new POSSystem();
});