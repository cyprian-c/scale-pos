<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleService
{
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            // Create sale
            $saleData = [
                'invoice_number' => Sale::generateInvoiceNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => auth()->id(),
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total' => 0,
                'amount_paid' => $data['amount_paid'],
                'change_amount' => 0,
                'payment_method' => $data['payment_method'],
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ];

            $sale = Sale::create($saleData);

            $subtotal = 0;
            $taxAmount = 0;

            // Add sale items
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Validate stock
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $unitPrice = $item['unit_price'] ?? $product->price;
                $itemTotal = $unitPrice * $item['quantity'];
                $itemTax = ($itemTotal * ($item['tax_rate'] ?? 0)) / 100;

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                    'tax_amount' => $itemTax,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                ]);

                // Reduce stock
                $product->reduceStock($item['quantity']);

                $subtotal += $itemTotal;
                $taxAmount += $itemTax;
            }

            // Calculate totals
            $total = $subtotal + $taxAmount - $sale->discount_amount;
            $changeAmount = $sale->amount_paid - $total;

            if ($changeAmount < 0) {
                throw new \Exception('Insufficient payment amount');
            }

            // Update sale with calculated totals
            $sale->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'change_amount' => $changeAmount,
            ]);

            // Log the sale
            Log::info('Sale completed', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total' => $total,
                'payment_method' => $sale->payment_method,
            ]);

            return $sale->fresh();
        });
    }

    public function getDailySalesReport(string $date = null): array
    {
        $date = $date ?: now()->toDateString();

        $sales = Sale::with(['items.product', 'customer', 'user'])
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();

        $totalSales = $sales->sum('total');
        $totalItems = $sales->sum(fn($sale) => $sale->items->sum('quantity'));
        $paymentMethods = $sales->groupBy('payment_method')
            ->map(fn($group) => $group->sum('total'));

        return [
            'date' => $date,
            'total_sales' => $totalSales,
            'total_transactions' => $sales->count(),
            'total_items_sold' => $totalItems,
            'payment_methods' => $paymentMethods,
            'sales' => $sales,
        ];
    }
}
