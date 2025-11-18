# Panduan Upgrade: Sistem Tiket Individual dengan QR Code Terpisah

## 📋 Ringkasan Perubahan

Sistem ISOLA SCREEN telah dimodifikasi untuk menghasilkan **tiket individual per kursi** dengan QR code terpisah, menggantikan sistem lama yang menggunakan 1 tiket untuk semua kursi dalam satu booking.

## 🎯 Fitur Utama

### ✅ Tiket Individual Per Kursi
- Setiap kursi mendapat tiket sendiri dengan QR code unik
- Format kode tiket: `ISOLA-XXXXXXXX-SEAT` (contoh: `ISOLA-5A42BE81-A1`)
- Auto-generate saat pembayaran dikonfirmasi

### ✅ Design Tiket Modern
- **Gradient Theme**: Purple ke Red (sesuai warna website)
- **JPG Tickets**: Download individual atau ZIP untuk multiple tickets
- **PDF Tickets**: Design profesional dengan layout yang eye-catching
- **Email**: Tampilan card untuk setiap tiket dengan embedded QR codes

### ✅ Sistem Validasi
- Scan QR code individual di pintu masuk
- Tracking per tiket (bukan per booking)
- Auto-update booking status saat semua tiket sudah digunakan

---

## 📁 File yang Dimodifikasi

### 1. Database Migration
**File**: `database_migration_individual_tickets.sql`

Membuat tabel `tickets` dan trigger auto-generate:
```sql
- Table: tickets (id, booking_id, booking_code, seat_label, ticket_code, qr_code_path, ticket_status, used_at)
- Trigger: after_booking_insert → auto-generate tiket per kursi
- Trigger: after_booking_payment → auto-generate jika trigger pertama gagal
```

### 2. Core Functions
**File**: `includes/functions.php`
- Update query untuk join dengan tabel `tickets`
- Fungsi helper untuk tiket individual

**File**: `includes/ticket-functions.php`
- `generateTicketsForBooking()` - Generate tiket per kursi
- `getTicketsByBookingCode()` - Ambil semua tiket dalam booking
- `getTicketByCode()` - Ambil tiket specific
- `validateTicketQRCode()` - Validasi QR code (support individual + legacy)
- `markTicketAsUsed()` - Mark tiket sebagai used
- `checkAllTicketsUsed()` - Check apakah semua tiket sudah dipakai
- `saveTicketQRCode()` - Generate dan simpan QR code PNG
- `getTicketQRCodeUrl()` - Get path ke QR code file

### 3. Payment Integration
**File**: `payment-callback.php`
- Auto-generate tiket saat payment status = paid
- Kompatibel dengan gateway payment yang ada

**File**: `tripay-webhook.php`
- Auto-generate tiket untuk Tripay webhook
- Handling untuk paid status

### 4. User Interface
**File**: `booking-success.php`
- Tampilkan semua tiket individual dalam grid cards
- Download per tiket atau download semua
- Print per tiket atau print semua
- Design modern dengan gradient theme

**File**: `download-ticket.php`
- Generate JPG dengan design keren
- Support download individual ticket
- Support download semua tiket (ZIP)
- Design: Gradient header, seat prominent, QR code centered

**File**: `print-ticket.php`
- Generate PDF dengan FPDF
- Support print individual atau multiple tickets
- Design: Professional layout dengan gradient colors

### 5. Email System
**File**: `includes/email-functions.php`
- `sendTicketEmail()` - Update untuk kirim multiple tickets
- `generateMultipleTicketsEmailHTML()` - Generate HTML email dengan card per tiket
- Embed QR code untuk setiap tiket
- Design responsive dengan gradient theme

### 6. Scan & Validation
**File**: `scan-ticket.php`
- Support scan tiket individual
- Support scan booking code (legacy)
- Mark tiket individual sebagai used
- Auto-update booking status jika semua tiket sudah used
- HTML5 QR Scanner integration

---

## 🚀 Cara Install di Hosting

### Step 1: Backup Database
```bash
mysqldump -u username -p database_name > backup_before_upgrade.sql
```

### Step 2: Upload Files
Upload semua file yang dimodifikasi ke hosting Anda via FTP/cPanel File Manager.

### Step 3: Run Migration SQL
1. Login ke **phpMyAdmin**
2. Pilih database Anda
3. Klik tab **SQL**
4. Copy-paste isi file `database_migration_individual_tickets.sql`
5. Klik **Go**

SQL akan:
- Create table `tickets`
- Create 2 triggers untuk auto-generate tiket
- Generate tiket untuk booking yang sudah ada (backfill)

### Step 4: Test Sistem
1. Buat booking baru
2. Lakukan pembayaran (test mode)
3. Check:
   - ✅ Email terkirim dengan multiple tickets
   - ✅ Booking success page tampil semua tiket
   - ✅ Download JPG berhasil
   - ✅ Print PDF berhasil
   - ✅ Scan QR code berhasil validasi

### Step 5: Verify Database
```sql
-- Check tiket yang ter-generate
SELECT * FROM tickets ORDER BY id DESC LIMIT 10;

-- Check booking dengan tiket
SELECT b.booking_code, COUNT(t.id) as total_tickets
FROM bookings b
LEFT JOIN tickets t ON b.id = t.booking_id
WHERE b.payment_status = 'paid'
GROUP BY b.id;
```

---

## 💡 Cara Penggunaan

### Untuk Customer

1. **Booking & Bayar** seperti biasa
2. **Terima Email** dengan semua tiket individual
3. **Download/Print** tiket dari halaman booking success
4. **Tunjukkan QR Code** masing-masing kursi saat masuk bioskop

### Untuk Admin/Staff

1. **Scan Tiket** di `scan-ticket.php`
2. Sistem akan:
   - Validasi tiket individual
   - Mark sebagai "used"
   - Tampilkan info kursi, nama, film
3. **Laporan** tetap tersedia di admin panel

---

## 🎨 Design Specifications

### Color Theme
- **Primary Gradient**: `#7c3aed` (Purple) → `#dc2626` (Red)
- **Seat Highlight**: `#f59e0b` (Gold/Amber)
- **Background**: `#1f2937` (Dark Gray)
- **Text**: `#ffffff` (White) & `#6b7280` (Light Gray)

### Ticket Layout
```
┌─────────────────────────────────┐
│   [GRADIENT HEADER]             │
│   SITE NAME - E-TICKET          │
├─────────────────────────────────┤
│                                 │
│   [FILM TITLE]                  │
│                                 │
│   ┌───────────────────────┐     │
│   │    KURSI: A1          │     │  <- Gold box
│   └───────────────────────┘     │
│                                 │
│   Kode: ISOLA-XXXXXXXX-A1       │
│                                 │
│   Tanggal | Waktu               │
│   Nama Pemesan                  │
│                                 │
│   ─ ─ ─ ─ ─ ─ ─ ─ ─ ─           │
│                                 │
│   [QR CODE IMAGE]               │
│   Scan di pintu masuk           │
│                                 │
└─────────────────────────────────┘
```

---

## 🔧 Troubleshooting

### Tiket tidak auto-generate setelah bayar?
1. Check apakah trigger database sudah dibuat:
   ```sql
   SHOW TRIGGERS LIKE 'bookings';
   ```
2. Manual generate untuk booking tertentu:
   ```sql
   CALL generate_tickets_for_booking(booking_id_disini);
   ```

### Email tidak terkirim?
1. Check SMTP settings di database (table `settings`)
2. Check `email-error.log` untuk error detail
3. Test email config di admin panel

### QR Code tidak muncul di tiket?
1. Check folder permissions: `qr-codes/` harus writable (chmod 755)
2. Check apakah library Endroid QR Code ter-install:
   ```bash
   composer require endroid/qr-code
   ```

### Download JPG/PDF error?
1. Check PHP GD extension: `php -m | grep gd`
2. Check FPDF library: `vendor/setasign/fpdf/`
3. Check memory limit di php.ini (minimal 128MB)

---

## 📊 Kompatibilitas

### Backward Compatibility
✅ **System mendukung legacy bookings**:
- Booking lama (tanpa tiket individual) tetap bisa di-scan
- QR code booking lama masih valid
- Validasi otomatis detect format (individual vs legacy)

### Migration Path
Sistem akan otomatis:
1. Generate tiket untuk booking PAID yang belum punya tiket
2. Support scan booking code lama
3. Bertahap migrate saat customer booking baru

---

## 📝 Database Schema

### Table: tickets
```sql
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    booking_code VARCHAR(50) NOT NULL,
    seat_label VARCHAR(10) NOT NULL,
    ticket_code VARCHAR(100) UNIQUE NOT NULL,
    qr_code_path VARCHAR(255),
    ticket_status ENUM('active','used','cancelled') DEFAULT 'active',
    used_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_ticket_code (ticket_code),
    INDEX idx_status (ticket_status)
);
```

### Triggers
1. **after_booking_insert** - Auto-generate saat booking baru dibuat
2. **after_booking_payment** - Fallback jika trigger pertama gagal

---

## 🎁 Bonus Features

### 1. ZIP Download untuk Multiple Tickets
Saat customer punya 3+ kursi, mereka bisa download semua tiket sekaligus dalam 1 file ZIP.

### 2. Email dengan Embedded QR
Email langsung tampilkan QR code (tidak perlu download attachment) untuk quick access.

### 3. Responsive Email Design
Email template responsive untuk mobile & desktop.

### 4. Admin Scan Interface
Interface scan tiket modern dengan HTML5 QR Scanner (camera-based).

---

## 📞 Support

Jika ada masalah:
1. Check `error_log` di hosting
2. Check `email-error.log` untuk email issues
3. Check database triggers dengan `SHOW TRIGGERS`
4. Verify file permissions untuk folder `qr-codes/`

---

## 🎉 Selesai!

Sistem tiket individual dengan QR code terpisah sudah siap digunakan.

**Key Points**:
- ✅ 1 tiket per kursi (bukan 1 tiket per booking)
- ✅ QR code unique per kursi
- ✅ Design modern dengan gradient theme
- ✅ Auto-generate via trigger database
- ✅ Email dengan semua tiket
- ✅ Download JPG & PDF dengan design keren
- ✅ Scan validation untuk tiket individual

**Happy booking! 🎬🍿**
