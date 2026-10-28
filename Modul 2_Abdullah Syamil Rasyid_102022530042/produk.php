<?php
require_once __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi  = $_POST['aksi'] ?? '';
    $nama  = trim($_POST['nama'] ?? '');
    $harga = (int)($_POST['harga'] ?? 0);
    try {
        if ($aksi === 'hapus') {                                              // DELETE
            $pdo->prepare("DELETE FROM produk WHERE id = ?")->execute([(int)$_POST['id']]);
            flash('Produk dihapus.');
        } elseif ($nama === '' || $harga <= 0) {
            flash('Nama wajib diisi dan harga harus lebih dari 0.', 'err');
            if ($aksi === 'ubah') redirect('produk.php?edit=' . (int)$_POST['id']);
        } elseif ($aksi === 'simpan') {                                       // CREATE
            $pdo->prepare("INSERT INTO produk (nama, harga) VALUES (?, ?)")->execute([$nama, $harga]);
            flash('Produk ditambahkan.');
        } elseif ($aksi === 'ubah') {                                         // UPDATE
            $pdo->prepare("UPDATE produk SET nama = ?, harga = ? WHERE id = ?")->execute([$nama, $harga, (int)$_POST['id']]);
            flash('Produk diperbarui.');
        }
    } catch (PDOException $ex) {
        flash($ex->getCode() === '23000'
            ? 'Produk tidak bisa dihapus karena sudah dipakai di pesanan.'
            : 'Gagal: ' . $ex->getMessage(), 'err');
    }
    redirect('produk.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$rows = $pdo->query("SELECT * FROM produk ORDER BY id")->fetchAll();   // READ
?>
<form method="post" class="card">
  <h3><?= $edit ? 'EDIT PRODUK' : 'TAMBAH PRODUK' ?></h3>
  <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'simpan' ?>">
  <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
  <label class="f">Nama</label>
  <input type="text" name="nama" required maxlength="100" value="<?= e($edit['nama'] ?? '') ?>">
  <label class="f">Harga (Rp)</label>
  <input type="number" name="harga" required min="1" value="<?= e($edit['harga'] ?? '') ?>">
  <div style="margin-top:12px">
    <button type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah' ?></button>
    <?php if ($edit): ?><a class="btn" href="produk.php">Batal</a><?php endif; ?>
  </div>
</form>

<div class="card">
  <h3>DAFTAR PRODUK</h3>
  <table>
    <tr><th>ID</th><th>Nama</th><th>Harga</th><th>Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td><td><?= e($r['nama']) ?></td><td><?= rupiah($r['harga']) ?></td>
        <td>
          <a class="btn" href="produk.php?edit=<?= (int)$r['id'] ?>">Edit</a>
          <form method="post" class="inline" onsubmit="return confirm('Hapus produk ini?')">
            <input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn-hapus">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
