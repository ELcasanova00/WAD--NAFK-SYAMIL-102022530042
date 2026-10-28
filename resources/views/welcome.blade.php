<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout & Voucher</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 p-6">
    <div class="max-w-4xl mx-auto p-6 bg-white border-2 border-black shadow-md">
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 border-2 border-black font-semibold text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border-2 border-red-600 font-semibold text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ isset($editOrder) && $editOrder ? route('checkout.update', $editOrder->id) : route('checkout.store') }}" method="POST" id="form">
            @csrf
            @if(isset($editOrder) && $editOrder)
                @method('PUT')
            @endif

            <h3 class="text-xl font-bold mb-3">{{ isset($editOrder) && $editOrder ? 'EDIT PESANAN #' . $editOrder->id : 'PILIH PRODUK' }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($products as $p)
                    <label class="border border-black p-3 flex gap-2 items-center cursor-pointer bg-gray-50 hover:bg-gray-100">
                        <input type="checkbox" class="prod" name="product[]" value="{{ $p->id }}"
                               data-price="{{ $p->harga }}"
                               {{ in_array($p->id, $selectedProducts ?? []) ? 'checked' : '' }}>
                        <span>{{ $p->nama }} <br><b>Rp {{ number_format($p->harga, 0, ',', '.') }}</b></span>
                    </label>
                @endforeach
            </div>

            <h3 class="text-xl font-bold mt-6 mb-3">VOUCHER DISKON</h3>
            <label class="border border-black p-3 flex gap-2 items-center cursor-pointer mb-2 bg-gray-50">
                <input type="radio" name="voucher_id" class="vch" value="0" data-persen="0" data-min="0" {{ ($selectedVoucherId ?? 0) == 0 ? 'checked' : '' }}>
                <span>Tanpa voucher</span>
            </label>
            @foreach($vouchers as $v)
                <label class="border border-black p-3 flex gap-2 items-center cursor-pointer mb-2 bg-gray-50">
                    <input type="radio" name="voucher_id" class="vch" value="{{ $v->id }}"
                           data-persen="{{ $v->persen }}" data-min="{{ $v->min_belanja }}"
                           {{ ($selectedVoucherId ?? 0) == $v->id ? 'checked' : '' }}>
                    <span><b>{{ $v->nama }}</b> [{{ $v->kode }}] - Diskon {{ $v->persen }}% (Min. Belanja Rp {{ number_format($v->min_belanja, 0, ',', '.') }})</span>
                </label>
            @endforeach
            <p class="text-sm text-red-600 mt-1 font-semibold" id="info"></p>

            <div class="mt-6 p-4 bg-gray-50 border border-black space-y-2">
                <div class="flex justify-between font-semibold"><span>Subtotal</span><span id="subtotal">Rp 0</span></div>
                <div class="flex justify-between font-semibold text-red-600"><span>Diskon</span><span id="diskon">-Rp 0</span></div>
                <div class="flex justify-between pt-2 border-t border-black font-bold text-xl"><span>Total Pembayaran</span><span id="total">Rp 0</span></div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="border-2 border-black bg-black text-white px-6 py-2 font-bold hover:bg-white hover:text-black transition">
                    {{ isset($editOrder) && $editOrder ? 'Simpan Perubahan' : 'Simpan Pesanan' }}
                </button>
                @if(isset($editOrder) && $editOrder)
                    <a href="{{ route('checkout.index') }}" class="border-2 border-black bg-gray-200 px-6 py-2 font-bold hover:bg-black hover:text-white transition inline-block text-center">Batal</a>
                @endif
            </div>
        </form>
    </div>

    <div class="max-w-4xl mx-auto mt-6 p-6 bg-white border-2 border-black shadow-md">
        <h3 class="text-xl font-bold mb-4">RIWAYAT PESANAN</h3>
        <table class="w-full border-collapse border border-black text-sm">
            <thead>
                <tr class="bg-gray-200 text-left">
                    <th class="border border-black p-2">#</th>
                    <th class="border border-black p-2">Item Produk</th>
                    <th class="border border-black p-2">Subtotal</th>
                    <th class="border border-black p-2">Diskon</th>
                    <th class="border border-black p-2">Total</th>
                    <th class="border border-black p-2">Waktu</th>
                    <th class="border border-black p-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $r)
                    <tr>
                        <td class="border border-black p-2">{{ $r->id }}</td>
                        <td class="border border-black p-2">
                            @foreach($r->items as $it)
                                {{ $it->product->nama ?? 'Produk' }} (Rp {{ number_format($it->harga, 0, ',', '.') }})<br>
                            @endforeach
                        </td>
                        <td class="border border-black p-2">Rp {{ number_format($r->subtotal, 0, ',', '.') }}</td>
                        <td class="border border-black p-2">
                            @if($r->diskon > 0)
                                -Rp {{ number_format($r->diskon, 0, ',', '.') }}<br>
                                <span class="text-xs text-gray-500">({{ $r->voucher->kode ?? '' }})</span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="border border-black p-2 font-bold">Rp {{ number_format($r->total, 0, ',', '.') }}</td>
                        <td class="border border-black p-2 text-xs">{{ $r->created_at }}</td>
                        <td class="border border-black p-2 flex gap-1">
                            <a href="{{ route('checkout.index', ['edit' => $r->id]) }}" class="border border-black bg-yellow-200 px-2 py-1 font-bold text-xs hover:bg-yellow-400">Edit</a>
                            <form action="{{ route('checkout.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus pesanan #{{ $r->id }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="border border-red-600 bg-red-100 text-red-600 px-2 py-1 font-bold text-xs hover:bg-red-600 hover:text-white">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="border border-black p-4 text-center text-gray-500">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
      const prods = document.querySelectorAll('.prod');
      const vchs  = document.querySelectorAll('.vch');
      const rp = n => 'Rp ' + n.toLocaleString('id-ID');

      function hitung() {
        let subtotal = 0;
        prods.forEach(p => { if (p.checked) subtotal += Number(p.dataset.price); });

        let diskon = 0, info = '';
        vchs.forEach(v => {
          const min = Number(v.dataset.min);
          const ok  = subtotal >= min;
          if (v.checked && Number(v.value) !== 0) {
            if (ok) diskon = Math.floor(subtotal * Number(v.dataset.persen) / 100);
            else info = 'Voucher belum berlaku: belanja kurang ' + rp(min - subtotal) + ' lagi.';
          }
        });

        document.getElementById('subtotal').innerText = rp(subtotal);
        document.getElementById('diskon').innerText   = '-' + rp(diskon);
        document.getElementById('total').innerText    = rp(subtotal - diskon);
        document.getElementById('info').innerText     = info;
      }
      document.addEventListener('change', hitung);
      hitung();
    </script>
</body>
</html>