<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Exception;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('id')->get();
        $vouchers = Voucher::where('aktif', 1)->orderBy('id')->get();
        $orders = Order::with(['voucher', 'items.product'])->orderBy('id', 'desc')->get();

        $editOrder = null;
        $selectedProducts = [];
        $selectedVoucherId = 0;

        if ($request->has('edit')) {
            $editOrder = Order::with('items')->find($request->edit);
            if ($editOrder) {
                $selectedProducts = $editOrder->items->pluck('product_id')->map(fn($id) => (int)$id)->toArray();
                $selectedVoucherId = (int)$editOrder->voucher_id;
            }
        }

        return view('checkout.index', compact('products', 'vouchers', 'orders', 'editOrder', 'selectedProducts', 'selectedVoucherId'));
    }

    public function store(Request $request)
    {
        try {
            [$subtotal, $diskon, $total, $vid, $prices] = $this->calculateOrder($request->product ?? [], $request->voucher_id);

            DB::transaction(function () use ($vid, $subtotal, $diskon, $total, $prices) {
                $order = Order::create([
                    'voucher_id' => $vid,
                    'subtotal' => $subtotal,
                    'diskon' => $diskon,
                    'total' => $total,
                ]);

                foreach ($prices as $productId => $price) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'harga' => $price,
                    ]);
                }
            });

            return redirect()->route('checkout.index')->with('success', 'Pesanan berhasil disimpan.');
        } catch (Exception $ex) {
            return redirect()->route('checkout.index')->with('error', $ex->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            [$subtotal, $diskon, $total, $vid, $prices] = $this->calculateOrder($request->product ?? [], $request->voucher_id);

            DB::transaction(function () use ($id, $vid, $subtotal, $diskon, $total, $prices) {
                $order = Order::findOrFail($id);
                $order->update([
                    'voucher_id' => $vid,
                    'subtotal' => $subtotal,
                    'diskon' => $diskon,
                    'total' => $total,
                ]);

                $order->items()->delete();

                foreach ($prices as $productId => $price) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'harga' => $price,
                    ]);
                }
            });

            return redirect()->route('checkout.index')->with('success', "Pesanan #$id diperbarui.");
        } catch (Exception $ex) {
            return redirect()->route('checkout.index')->with('error', $ex->getMessage());
        }
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();
        return redirect()->route('checkout.index')->with('success', "Pesanan #$id dihapus.");
    }

    private function calculateOrder(array $productIds, $voucherId)
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            throw new Exception('Pilih minimal satu produk.');
        }

        $products = Product::whereIn('id', $productIds)->get();
        $prices = [];
        foreach ($products as $p) {
            $prices[$p->id] = (int)$p->harga;
        }

        if (count($prices) !== count($productIds)) {
            throw new Exception('Ada produk yang tidak ditemukan.');
        }

        $subtotal = array_sum($prices);
        $diskon = 0;
        $vid = null;

        if ($voucherId) {
            $voucher = Voucher::where('id', $voucherId)->where('aktif', 1)->first();
            if (!$voucher) {
                throw new Exception('Voucher tidak valid / tidak aktif.');
            }
            if ($subtotal < (int)$voucher->min_belanja) {
                throw new Exception('Voucher ' . $voucher->kode . ' butuh minimal belanja Rp ' . number_format($voucher->min_belanja, 0, ',', '.') . '.');
            }
            $diskon = intdiv($subtotal * (int)$voucher->persen, 100);
            $vid = (int)$voucher->id;
        }

        return [$subtotal, $diskon, $subtotal - $diskon, $vid, $prices];
    }
}
