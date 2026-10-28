<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout & Voucher</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; margin: 0; padding: 20px; color: #000; }
        
        /* Tab Menu ala referensi */
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; max-width: 800px; margin-left: auto; margin-right: auto; }
        .tab { background: #fff; border: 2px solid #000; padding: 8px 20px; font-weight: bold; text-decoration: none; color: #000; }
        .tab.active { background: #000; color: #fff; }

        .box { max-width: 800px; margin: 0 auto 20px auto; background: #fff; border: 2px solid #000; padding: 20px; }
        h3 { margin-top: 0; font-size: 16px; font-weight: bold; margin-bottom: 15px; text-transform: uppercase; }
        
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .card { border: 1px solid #000; padding: 12px 10px; background: #fff; cursor: pointer; display: flex; align-items: flex-start; gap: 8px; font-size: 14px; }
        
        .alert-success { background: #d1fae5; border: 2px solid #000; padding: 10px; margin-bottom: 15px; font-weight: bold; }
        .alert-error { background: #fee2e2; border: 2px solid #dc2626; padding: 10px; margin-bottom: 15px; font-weight: bold; color: #991b1b; }
        
        .voucher-option { border: 1px solid #000; padding: 10px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; }
        
        .summary-table { width: 100%; margin-top: 20px; border-top: 2px solid #000; padding-top: 10px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 15px; }
        .total-row { display: flex; justify-content: space-between; border-top: 2px solid #000; padding-top: 10px; font-size: 18px; font-weight: bold; margin-top: 5px; }
        
        .btn-simpan { background: #fff; color: #000; border: 2px solid #000; padding: 8px 20px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .btn-simpan:hover { background: #000; color: #fff; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f3f4f6; }
        .btn-edit { background: #fef08a; border: 1px solid #000; padding: 3px 8px; font-size: 12px; font-weight: bold; text-decoration: none; color: #000; }
        .btn-hapus { background: #fee2e2; border: 1px solid #dc2626; color: #dc2626; padding: 3px 8px; font-size: 12px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

    <!-- Tab Navigasi atas -->
    <div class="tabs">
        <a href="#" class="tab active">Checkout</a>
        <a href="#" class="tab" onclick="alert('Menu Produk'); return false;">Produk</a>
        <a href="#" class="tab" onclick="alert('Menu Voucher'); return false;">Voucher</a>
    </div>

    <div class="box">
        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        <form action="{{ isset($editOrder) && $editOrder ? route('checkout.update', $editOrder->id) : route('checkout.store') }}" method="POST">
            @csrf
            @if(isset($editOrder) && $editOrder)
                @method('PUT')
            @endif

            <h3>Pilih Produk</h3>
            <div class="grid">
                @foreach($products as $p)
                    <label class="card">
                        <input type="checkbox" class="prod" name="product[]" value="{{ $p->id }}"
                               data-price="{{ $p->harga }}"
                               {{ in_array($p->id, $selectedProducts ?? []) ? 'checked' : '' }}>
                        <span>{{ $p->nama }} <br><b>(Rp {{ number_format($p->harga, 0, ',', '.') }})</b></span>
                    </label>
                @endforeach
            </div>

            <h3>Checkout</h3>
            <label class="voucher-option">
                <input type="radio" name="voucher_id" class="vch" value="0" data-persen="0" data-min="0" {{ ($selectedVoucherId ?? 0) == 0 ? 'checked' : '' }}>
                <span>Tanpa voucher</span>
            </label>
            @foreach($vouchers as $v)
                <label class="voucher-option">
                    <input type="radio" name="voucher_id" class="vch" value="{{ $v->id }}"
                           data-persen="{{ $v->persen }}" data-min="{{ $v->min_belanja }}"
                           {{ ($selectedVoucherId ?? 0) == $v->id ? 'checked' : '' }}>
                    <span>Diskon {{ $v->persen }}% [{{ $v->kode }}] (Min. Belanja Rp {{ number_format($v->min_belanja, 0, ',', '.') }})</span>
                </label>
            @endforeach
            <p style="color: #dc2626; font-size: 13px; font-weight: bold; margin-top: 5px;" id="info"></p>

            <div class="summary-table">
                <div class="summary-row"><span>Subtotal</span><span id="subtotal">Rp 0</span></div>
                <div class="summary-row" style="color: #dc2626;"><span>Diskon</span><span id="diskon">-Rp 0</span></div>
                <div class="total-row"><span>Total</span><span id="total">Rp 0</span></div>
            </div>

            <button type="submit" class="btn-simpan">
                {{ isset($editOrder) && $editOrder ? 'Simpan Perubahan' : 'Simpan Pesanan' }}
            </button>
            @if(isset($editOrder) && $editOrder)
                <a href="{{ route('checkout.index') }}" class="btn-simpan" style="background: #e5e7eb; text-decoration: none; display: inline-block;">Batal</a>
            @endif
        </form>
    </div>

    <div class="box">
        <h3>Riwayat Pesanan</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Subtotal</th>
                    <th>Diskon</th>
                    <th>Total</th>
                    <th>Waktu</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>
                            @foreach($r->items as $it)
                             {{ $it->product->nama ?? 'Produk' }} (Rp {{ number_format($it->harga, 0, ',', '.') }})<br>
                            @endforeach
                        </td>
                        <td>Rp {{ number_format($r->subtotal, 0, ',', '.') }}</td>
                        <td>
                            @if($r->diskon > 0)
                                -Rp {{ number_format($r->diskon, 0, ',', '.') }}<br>
                                <span style="font-size: 11px; color: #666;">({{ $r->voucher->kode ?? '' }})</span>
                            @else
                                -
                            @endif
                        </td>
                        <td><b>Rp {{ number_format($r->total, 0, ',', '.') }}</b></td>
                        <td style="font-size: 12px;">{{ $r->created_at }}</td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="{{ route('checkout.index', ['edit' => $r->id]) }}" class="btn-edit">Edit</a>
                                <form action="{{ route('checkout.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus pesanan #{{ $r->id }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-hapus">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; color: #666; padding: 15px;">Belum ada pesanan.</td></tr>
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
            if (ok) {
              diskon = Math.floor(subtotal * Number(v.dataset.persen) / 100);
            } else {
              info = 'Voucher belum berlaku: kurang belanja ' + rp(min - subtotal) + ' lagi.';
            }
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
