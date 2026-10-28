<?php
require_once __DIR__ . '/header.php';

/**
 * Hitung pesanan di SERVER (jangan percaya angka dari browser).
 * Return [subtotal, diskon, total, voucher_id|null, harga_per_produk[id=>harga]] atau lempar Exception.
 */
function hitungPesanan(PDO $pdo, array $produkIds, $voucherId) {
    $produkIds = array_values(array_unique(array_filter(array_map('intval', $produkIds))));
    if (!$produkIds) throw new Exception('Pilih minimal satu produk.');

    $in  = implode(',', array_fill(0, count($produkIds), '?'));
    $st  = $pdo->prepare("SELECT id, harga FROM produk WHERE id IN ($in)");
    $st->execute($produkIds);
    $harga = [];
    foreach ($st->fetchAll() as $r) $harga[(int)$r['id']] = (int)$r['harga'];
    if (count($harga) !== count($produkIds)) throw new Exception('Ada produk yang tidak ditemukan.');

    $subtotal = array_sum($harga);
    $diskon = 0; $vid = null;

    if ($voucherId) {
        $st = $pdo->prepare("SELECT * FROM voucher WHERE id = ? AND aktif = 1");
        $st->execute([(int)$voucherId]);
        $v = $st->fetch();
        if (!$v) throw new Exception('Voucher tidak valid / tidak aktif.');
        if ($subtotal < (int)$v['min_belanja'])
            throw new Exception('Voucher ' . $v['kode'] . ' butuh minimal belanja ' . rupiah($v['min_belanja']) . '.');
        $diskon = intdiv($subtotal * (int)$v['persen'], 100);   // bulat, tanpa pecahan
        $vid = (int)$v['id'];
    }
    return [$subtotal, $diskon, $subtotal - $diskon, $vid, $harga];
}

function simpanItem(PDO $pdo, $pesananId, array $harga) {
    $ins = $pdo->prepare("INSERT INTO pesanan_item (pesanan_id, produk_id, harga) VALUES (?, ?, ?)");
    foreach ($harga as $pid => $h) $ins->execute([$pesananId, $pid, $h]);
}

// ================= CREATE / UPDATE / DELETE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'hapus') {                                       // DELETE
            $pdo->prepare("DELETE FROM pesanan WHERE id = ?")->execute([(int)$_POST['id']]);
            flash('Pesanan #' . (int)$_POST['id'] . ' dihapus.');
        } elseif ($aksi === 'simpan' || $aksi === 'ubah') {
            [$sub, $dis, $tot, $vid, $harga] = hitungPesanan($pdo, $_POST['produk'] ?? [], $_POST['voucher_id'] ?? 0);
            $pdo->beginTransaction();
            if ($aksi === 'simpan') {                                  // CREATE
                $pdo->prepare("INSERT INTO pesanan (voucher_id, subtotal, diskon, total) VALUES (?, ?, ?, ?)")
                    ->execute([$vid, $sub, $dis, $tot]);
                $id = (int)$pdo->lastInsertId();
                simpanItem($pdo, $id, $harga);
                flash("Pesanan #$id disimpan. Total " . rupiah($tot));
            } else {                                                   // UPDATE
                $id = (int)$_POST['id'];
                $pdo->prepare("UPDATE pesanan SET voucher_id = ?, subtotal = ?, diskon = ?, total = ? WHERE id = ?")
                    ->execute([$vid, $sub, $dis, $tot, $id]);
                $pdo->prepare("DELETE FROM pesanan_item WHERE pesanan_id = ?")->execute([$id]);
                simpanItem($pdo, $id, $harga);
                flash("Pesanan #$id diperbarui. Total " . rupiah($tot));
            }
            $pdo->commit();
        }
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash($ex->getMessage(), 'err');
        if ($aksi === 'ubah') redirect('index.php?edit=' . (int)$_POST['id']);
    }
    redirect('index.php');
}

// ================= READ =================
$produk  = $pdo->query("SELECT * FROM produk ORDER BY id")->fetchAll();
$voucher = $pdo->query("SELECT * FROM voucher WHERE aktif = 1 ORDER BY id")->fetchAll();

$edit = null; $dipilih = []; $vPilih = 0;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM pesanan WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
    if ($edit) {
        $st = $pdo->prepare("SELECT produk_id FROM pesanan_item WHERE pesanan_id = ?");
        $st->execute([$edit['id']]);
        $dipilih = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        $vPilih  = (int)$edit['voucher_id'];
    }
}

$riwayat = $pdo->query(
    "SELECT p.*, v.kode FROM pesanan p LEFT JOIN voucher v ON v.id = p.voucher_id ORDER BY p.id DESC"
)->fetchAll();
$itemQ = $pdo->prepare(
    "SELECT pr.nama, i.harga FROM pesanan_item i JOIN produk pr ON pr.id = i.produk_id WHERE i.pesanan_id = ? ORDER BY i.id"
);
?>

<form method="post" class="card" id="form">
  <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'simpan' ?>">
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>

  <h3><?= $edit ? 'EDIT PESANAN #' . (int)$edit['id'] : 'PILIH PRODUK' ?></h3>
  <div class="grid">
    <?php foreach ($produk as $p): ?>
      <label class="item">
        <input type="checkbox" class="prod" name="produk[]" value="<?= (int)$p['id'] ?>"
               data-price="<?= (int)$p['harga'] ?>" <?= in_array((int)$p['id'], $dipilih, true) ? 'checked' : '' ?>>
        <?= e($p['nama']) ?> (<?= rupiah($p['harga']) ?>)
      </label>
    <?php endforeach; ?>
  </div>

  <h3 style="margin-top:15px">CHECKOUT</h3>
  <label class="item" style="margin-top:8px">
    <input type="radio" name="voucher_id" class="vch" value="0" data-persen="0" data-min="0" <?= $vPilih ? '' : 'checked' ?>>
    Tanpa voucher
  </label>
  <?php foreach ($voucher as $v): ?>
    <label class="item vlabel" style="margin-top:8px">
      <input type="radio" name="voucher_id" class="vch" value="<?= (int)$v['id'] ?>"
             data-persen="<?= (int)$v['persen'] ?>" data-min="<?= (int)$v['min_belanja'] ?>"
             <?= $vPilih === (int)$v['id'] ? 'checked' : '' ?>>
      <?= e($v['nama']) ?> [<?= e($v['kode']) ?>] (Min. <?= rupiah($v['min_belanja']) ?>)
    </label>
  <?php endforeach; ?>
  <p class="muted" id="info"></p>

  <div class="row"><span>Subtotal</span><span id="subtotal">Rp 0</span></div>
  <div class="row"><span>Diskon</span><span id="diskon">-Rp 0</span></div>
  <div class="row total"><span>Total</span><span id="total">Rp 0</span></div>

  <div style="margin-top:12px">
    <button type="submit"><?= $edit ? 'Simpan Perubahan' : 'Simpan Pesanan' ?></button>
    <?php if ($edit): ?><a class="btn" href="index.php">Batal</a><?php endif; ?>
  </div>
</form>

<div class="card">
  <h3>RIWAYAT PESANAN</h3>
  <table>
    <tr><th>#</th><th>Item</th><th>Subtotal</th><th>Diskon</th><th>Total</th><th>Waktu</th><th>Aksi</th></tr>
    <?php foreach ($riwayat as $r): $itemQ->execute([$r['id']]); ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?php foreach ($itemQ->fetchAll() as $it): ?><?= e($it['nama']) ?> (<?= rupiah($it['harga']) ?>)<br><?php endforeach; ?></td>
        <td><?= rupiah($r['subtotal']) ?></td>
        <td><?= $r['diskon'] ? '-' . rupiah($r['diskon']) . '<br><span class="muted">' . e($r['kode'] ?? '') . '</span>' : '-' ?></td>
        <td><b><?= rupiah($r['total']) ?></b></td>
        <td><?= e($r['dibuat_pada']) ?></td>
        <td>
          <a class="btn" href="index.php?edit=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" class="inline" onsubmit="return confirm('Hapus pesanan #<?= (int)$r['id'] ?>?')">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn-hapus">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$riwayat): ?><tr><td colspan="7" class="muted">Belum ada pesanan.</td></tr><?php endif; ?>
  </table>
</div>

<script>
  // Hanya PREVIEW. Angka final tetap dihitung ulang di server (hitungPesanan).
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
      v.closest('label').classList.toggle('off', !ok && min > 0);
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

<?php require __DIR__ . '/footer.php'; ?>
