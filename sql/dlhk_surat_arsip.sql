-- Database: dlhk_surat_arsip
CREATE DATABASE IF NOT EXISTS dlhk_surat_arsip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dlhk_surat_arsip;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reset Password
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Surat Masuk
CREATE TABLE IF NOT EXISTS surat_masuk (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kendali VARCHAR(100) NOT NULL,
  jenis_surat VARCHAR(100) NOT NULL,
  tgl_terima VARCHAR(50) NOT NULL,
  asal_surat VARCHAR(150) NOT NULL,
  nomor_surat VARCHAR(100) NOT NULL,
  tgl_surat VARCHAR(50) NOT NULL,
  perihal TEXT NOT NULL,
  file_path VARCHAR(255) NULL,
  dispo_kadis VARCHAR(255) NOT NULL DEFAULT '-',
  dispo_sekdin VARCHAR(255) NOT NULL DEFAULT '-',
  dispo_kabid VARCHAR(255) NOT NULL DEFAULT '-',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Disposisi
CCREATE TABLE `disposisi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surat_id` int(11) NOT NULL,
  `tujuan` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `tgl_disposisi` date NOT NULL,
  `status` enum('Belum Ditindak','Sedang Diproses','Selesai') DEFAULT 'Belum Ditindak',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `surat_id` (`surat_id`),
  CONSTRAINT `disposisi_ibfk_1` FOREIGN KEY (`surat_id`) REFERENCES `surat_masuk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Arsip Digital
CREATE TABLE IF NOT EXISTS arsip (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kendali VARCHAR(100) NOT NULL,
  jenis_surat VARCHAR(100) NOT NULL,
  tgl_terima VARCHAR(50) NOT NULL,
  asal_surat VARCHAR(150) NOT NULL,
  nomor_surat VARCHAR(100) NOT NULL,
  tgl_surat VARCHAR(50) NOT NULL,
  perihal TEXT NOT NULL,
  file_path VARCHAR(255) NULL,
  dispo_kadis VARCHAR(255) NOT NULL DEFAULT '-',
  dispo_sekdin VARCHAR(255) NOT NULL DEFAULT '-',
  dispo_kabid VARCHAR(255) NOT NULL DEFAULT '-',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

