<?php
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi   = $_POST['aksi'] ?? '';
    $kode   = strtoupper(trim($_POST['kode'] ?? ''));
    $nama   = trim($_POST['nama'] ?? '');
    $persen = (int)($_POST['persen'] ?? 0);
    $min    = max(0, (int)($_POST['min_belanja'] ?? 0));
    $aktif  = isset($_POST['aktif']) ? 1 : 0;
    try {
        if ($aksi === 'hapus') {                                              // DELETE
            $pdo->prepare("DELETE FROM voucher WHERE id = ?")->execute([(int)$_POST['id']]);
            flash('Voucher dihapus.');
        } elseif ($kode === '' || $nama === '' || $persen < 1 || $persen > 100) {
            flash('Kode & nama wajib diisi, persen harus 1-100.', 'err');
            if ($aksi === 'ubah') redirect('voucher.php?edit=' . (int)$_POST['id']);
        } elseif ($aksi === 'simpan') {                                       // CREATE
            $pdo->prepare("INSERT INTO voucher (kode, nama, persen, min_belanja, aktif) VALUES (?, ?, ?, ?, ?)")
                ->execute([$kode, $nama, $persen, $min, $aktif]);
            flash('Voucher ditambahkan.');
        } elseif ($aksi === 'ubah') {                                         // UPDATE
            $pdo->prepare("UPDATE voucher SET kode = ?, nama = ?, persen = ?, min_belanja = ?, aktif = ? WHERE id = ?")
                ->execute([$kode, $nama, $persen, $min, $aktif, (int)$_POST['id']]);
            flash('Voucher diperbarui.');
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() === '23000' ? 'Kode voucher sudah dipakai.' : 'Gagal: ' . $ex->getMessage(), 'err');
    }
    redirect('voucher.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM voucher WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query("SELECT * FROM voucher ORDER BY id")->fetchAll();   // READ
?>
<form method="post" class="card">
  <h3><?= $edit ? 'EDIT VOUCHER' : 'TAMBAH VOUCHER' ?></h3>
  <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'simpan' ?>">
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label class="f">Kode</label>
  <input type="text" name="kode" required maxlength="30" value="<?= e($edit['kode'] ?? '') ?>">
  <label class="f">Nama</label>
  <input type="text" name="nama" required maxlength="100" value="<?= e($edit['nama'] ?? '') ?>">
  <label class="f">Diskon (%)</label>
  <input type="number" name="persen" required min="1" max="100" value="<?= e($edit['persen'] ?? '') ?>">
  <label class="f">Minimal belanja (Rp)</label>
  <input type="number" name="min_belanja" min="0" value="<?= e($edit['min_belanja'] ?? 0) ?>">
  <label class="f"><input type="checkbox" name="aktif" <?= !$edit || $edit['aktif'] ? 'checked' : '' ?>> Aktif</label>
  <div style="margin-top:12px">
    <button type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah' ?></button>
    <?php if ($edit): ?><a class="btn" href="voucher.php">Batal</a><?php endif; ?>
  </div>
</form>

<div class="card">
  <h3>DAFTAR VOUCHER</h3>
  <table>
    <tr><th>ID</th><th>Kode</th><th>Nama</th><th>%</th><th>Min. belanja</th><th>Aktif</th><th>Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td><td><?= e($r['kode']) ?></td><td><?= e($r['nama']) ?></td>
        <td><?= (int)$r['persen'] ?>%</td><td><?= rupiah($r['min_belanja']) ?></td><td><?= $r['aktif'] ? 'Ya' : 'Tidak' ?></td>
        <td>
          <a class="btn" href="voucher.php?edit=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" class="inline" onsubmit="return confirm('Hapus voucher ini?')">
            <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn-hapus">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
