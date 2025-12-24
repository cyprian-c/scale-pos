<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Sale\SaleService;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class POSController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index()
    {
        $products = Product::active()
            ->with('category')
            ->orderBy('name')
            ->get();

        $customers = Customer::active()
            ->orderBy('name')
            ->get();

        return view('pos.index', compact('products', 'customers'));
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->input('search', '');

        $products = Product::active()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->take(20)
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity', 'image_path']);

        return response()->json($products);
    }

    public function processSale(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile,credit',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        try {
            $sale = $this->saleService->createSale($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully',
                'sale' => $sale,
                'invoice_number' => $sale->invoice_number,
                'receipt_url' => route('sales.receipt', $sale->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getProductDetails($id): JsonResponse
    {
        $product = Product::active()
            ->with('category')
            ->findOrFail($id);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $product->stock_quantity,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'image' => $product->image_path,
        ]);
    }
}
