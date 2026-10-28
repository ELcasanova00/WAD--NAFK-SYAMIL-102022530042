-- =====================================================
-- Database: db_checkout  (Modul 4 PAW - Checkout Ringkas)
-- Kompatibel: MySQL 5.7+ / MariaDB 10.3+ (XAMPP/Laragon)
-- =====================================================
CREATE DATABASE IF NOT EXISTS db_checkout
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_checkout;

DROP TABLE IF EXISTS pesanan_item;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS voucher;
DROP TABLE IF EXISTS produk;

-- Master produk
CREATE TABLE produk (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama       VARCHAR(100) NOT NULL,
  harga      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Master voucher diskon (persen dari subtotal, dengan minimal belanja)
CREATE TABLE voucher (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode         VARCHAR(30)  NOT NULL,
  nama         VARCHAR(100) NOT NULL,
  persen       TINYINT UNSIGNED NOT NULL,
  min_belanja  INT UNSIGNED NOT NULL DEFAULT 0,
  aktif        TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_voucher_kode (kode),
  CONSTRAINT chk_voucher_persen CHECK (persen BETWEEN 1 AND 100)
) ENGINE=InnoDB;

-- Header pesanan
CREATE TABLE pesanan (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_id  INT UNSIGNED NULL,
  subtotal    INT UNSIGNED NOT NULL,
  diskon      INT UNSIGNED NOT NULL DEFAULT 0,
  total       INT UNSIGNED NOT NULL,
  dibuat_pada TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_pesanan_voucher FOREIGN KEY (voucher_id)
    REFERENCES voucher(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Detail pesanan (harga disalin agar riwayat tidak berubah jika harga produk diubah)
CREATE TABLE pesanan_item (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pesanan_id  INT UNSIGNED NOT NULL,
  produk_id   INT UNSIGNED NOT NULL,
  harga       INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_item_pesanan FOREIGN KEY (pesanan_id)
    REFERENCES pesanan(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_item_produk FOREIGN KEY (produk_id)
    REFERENCES produk(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Data awal (sama persis dengan file HTML/PHP asli)
INSERT INTO produk (nama, harga) VALUES
  ('Attack 800g', 35000),
  ('Biore Foam',  28000),
  ('Laurier',     22000),
  ('Sabun Cair',  19000),
  ('Shampoo',     32000),
  ('Pasta Gigi',  18000);

INSERT INTO voucher (kode, nama, persen, min_belanja, aktif) VALUES
  ('DISKON20', 'Diskon 20%', 20, 100000, 1);
