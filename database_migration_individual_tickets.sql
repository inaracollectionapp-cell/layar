-- Migration SQL untuk sistem tiket individual
-- Jalankan script ini di database Anda

-- 1. Buat tabel tickets untuk menyimpan tiket individual per kursi
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `ticket_code` varchar(25) NOT NULL COMMENT 'Kode unik tiket (ISOLA-XXXXXXXX-1, ISOLA-XXXXXXXX-2, dst)',
  `seat_label` varchar(5) NOT NULL COMMENT 'Label kursi (A1, B2, dst)',
  `ticket_status` enum('valid','used','cancelled') DEFAULT 'valid',
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `booking_id` (`booking_id`),
  KEY `ticket_status` (`ticket_status`),
  CONSTRAINT `tickets_booking_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- 2. Tambah index untuk performa
CREATE INDEX idx_ticket_code ON tickets(ticket_code);
CREATE INDEX idx_booking_seat ON tickets(booking_id, seat_label);

-- 3. Migrasi data existing bookings ke tabel tickets (opsional, untuk data lama)
-- Uncomment jika ingin migrasi data lama:
/*
INSERT INTO tickets (booking_id, ticket_code, seat_label, ticket_status, used_at, created_at)
SELECT 
    b.id,
    CONCAT(b.booking_code, '-', seat_index.idx) as ticket_code,
    JSON_UNQUOTE(JSON_EXTRACT(b.seats, CONCAT('$[', seat_index.idx - 1, ']'))) as seat_label,
    CASE 
        WHEN b.booking_status = 'used' THEN 'used'
        WHEN b.booking_status = 'cancelled' THEN 'cancelled'
        ELSE 'valid'
    END as ticket_status,
    b.used_at,
    b.booking_date
FROM bookings b
CROSS JOIN (
    SELECT 1 as idx UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
) as seat_index
WHERE seat_index.idx <= b.total_seats
AND b.payment_status = 'paid';
*/

-- 4. Buat stored procedure untuk generate tiket otomatis saat booking dibayar
DELIMITER $$

DROP PROCEDURE IF EXISTS generate_tickets_for_booking$$

CREATE PROCEDURE generate_tickets_for_booking(IN p_booking_id INT)
BEGIN
    DECLARE v_booking_code VARCHAR(20);
    DECLARE v_seats JSON;
    DECLARE v_total_seats INT;
    DECLARE v_counter INT DEFAULT 0;
    DECLARE v_seat_label VARCHAR(5);
    DECLARE v_ticket_code VARCHAR(25);
    
    -- Ambil data booking
    SELECT booking_code, seats, total_seats 
    INTO v_booking_code, v_seats, v_total_seats
    FROM bookings 
    WHERE id = p_booking_id;
    
    -- Generate tiket untuk setiap kursi
    WHILE v_counter < v_total_seats DO
        -- Ambil seat label dari JSON array
        SET v_seat_label = JSON_UNQUOTE(JSON_EXTRACT(v_seats, CONCAT('$[', v_counter, ']')));
        
        -- Generate ticket code: BOOKING_CODE-SEAT_LABEL
        SET v_ticket_code = CONCAT(v_booking_code, '-', v_seat_label);
        
        -- Insert tiket (gunakan INSERT IGNORE untuk avoid duplicate)
        INSERT IGNORE INTO tickets (booking_id, ticket_code, seat_label, ticket_status)
        VALUES (p_booking_id, v_ticket_code, v_seat_label, 'valid');
        
        SET v_counter = v_counter + 1;
    END WHILE;
END$$

DELIMITER ;

-- CATATAN PENTING:
-- Setelah menjalankan SQL ini, Anda perlu:
-- 1. Upload file PHP yang sudah dimodifikasi
-- 2. Test dengan booking baru
-- 3. Untuk data lama, uncomment bagian migrasi data di atas
