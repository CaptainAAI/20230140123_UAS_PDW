CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel praktikum
CREATE TABLE `praktikum` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_praktikum` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel pendaftaran_praktikum (relasi mahasiswa ke praktikum)
CREATE TABLE `pendaftaran_praktikum` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `praktikum_id` INT(11) NOT NULL,
  `tanggal_daftar` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_praktikum` (`user_id`, `praktikum_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`praktikum_id`) REFERENCES `praktikum`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel modul (pertemuan/materi tiap praktikum)
CREATE TABLE `modul` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `praktikum_id` INT(11) NOT NULL,
  `judul` VARCHAR(100) NOT NULL,
  `file_materi` VARCHAR(255),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`praktikum_id`) REFERENCES `praktikum`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel laporan (tugas mahasiswa per modul)
CREATE TABLE `laporan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `modul_id` INT(11) NOT NULL,
  `file_laporan` VARCHAR(255),
  `nilai` INT(3),
  `catatan` TEXT,
  `tanggal_kumpul` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_modul` (`user_id`, `modul_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`modul_id`) REFERENCES `modul`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

select * from laporan;
select * from praktikum;
select * from pendaftaran_praktikum;
select * from modul;