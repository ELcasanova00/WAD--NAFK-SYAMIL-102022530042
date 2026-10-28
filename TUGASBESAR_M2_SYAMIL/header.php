<?php
// Header + navigasi + flash message. Di-include di awal setiap halaman.
session_start();
require_once __DIR__ . '/koneksi.php';

function flash($pesan, $tipe = 'ok') { $_SESSION['flash'] = [$tipe, $pesan]; }
function redirect($url) { header('Location: ' . $url); exit; }
$hal = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout Ringkas</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; border-radius: 0 !important; font-family: Arial, sans-serif; }
    body { padding: 20px; max-width: 900px; margin: auto; }
    nav { display: flex; gap: 10px; margin-bottom: 15px; }
    nav a { border: 2px solid #000; padding: 6px 12px; text-decoration: none; color: #000; font-weight: bold; }
    nav a.aktif { background: #000; color: #fff; }
    .card { border: 2px solid #000000; padding: 15px; margin-bottom: 15px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 10px; }
    .item { border: 1px solid #000; padding: 10px; display: flex; gap: 8px; cursor: pointer; }
    .row { display: flex; justify-content: space-between; margin-top: 8px; }
    .total { font-weight: bold; font-size: 16px; margin-top: 10px; border-top: 1px solid #000; padding-top: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; vertical-align: top; }
    th { background: #eee; }
    input[type=text], input[type=number] { border: 1px solid #000; padding: 6px; width: 100%; }
    label.f { display: block; margin-top: 8px; font-weight: bold; }
    button, .btn { border: 2px solid #000; background: #fff; padding: 6px 12px; cursor: pointer; font-weight: bold; text-decoration: none; color: #000; display: inline-block; font-size: 13px; }
    button:hover, .btn:hover { background: #000; color: #fff; }
    .btn-hapus { border-color: #b00; color: #b00; }
    .btn-hapus:hover { background: #b00; color: #fff; }
    .inline { display: inline; }
    .msg { padding: 10px; margin-bottom: 15px; border: 2px solid #000; }
    .msg.ok { background: #e6ffe6; }
    .msg.err { background: #ffe6e6; border-color: #b00; }
    .muted { color: #666; font-size: 13px; }
    .off { opacity: .45; }
  </style>
</head>
<body>
  <nav>
    <a href="index.php" class="<?= $hal === 'index.php' ? 'aktif' : '' ?>">Checkout</a>
    <a href="produk.php" class="<?= $hal === 'produk.php' ? 'aktif' : '' ?>">Produk</a>
    <a href="voucher.php" class="<?= $hal === 'voucher.php' ? 'aktif' : '' ?>">Voucher</a>
  </nav>
  <?php if (!empty($_SESSION['flash'])): [$t, $m] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="msg <?= e($t) ?>"><?= e($m) ?></div>
  <?php endif; ?>
