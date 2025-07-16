✅ SCENARIO SISTEM

### 1. Pelanggan Walk-in (Tanpa Konsultasi & Janji Temu)

Petugas langsung input data binatang (jika belum ada).

- Buat entri layanan:

  - Medical Record → `medical_records` (tanpa `appointment_id`).

  - Grooming → `grooming_services` (tanpa `appointment_id`).

- Jika ada obat diresepkan → input ke `obat_resep`.

- Buat transaksi → transactions + `transaction_items` sesuai layanan yang dilakukan.

---

### 2. Pelanggan Konsultasi + Janji Temu (Flow via Booking)

- Step 1: Konsultasi Awal

  - Input data konsultasi ke tabel `consultations` dengan status = 'pending'.
  - Pilihan metode konsultasi:
    - telepon
    - video call
    - chat
    - langsung

- Step 2: Buat Janji Temu

  - Cek `dokter_schedules` → cari dokter & jadwal available.

  - Buat appointments, hubungkan `consultation_id`, `schedule_id`, `animal_id`, `dokter_id`.

  - Update:

    - `dokter_schedules.ketersediaan` -1.

    - `consultations.status` = scheduled.

- Step 3: Saat Janji Temu Berlangsung

  - Layanan dilakukan, `input ke medical_records` / `grooming_services`, s`ertakan appointment_id`.

  - Jika ada resep → `obat_resep`.
    `
  - `appointments.status = completed`.

  - Buat transactions & `transaction_items` sesuai layanan & produk.

  ***

### 3. Pet Hotel Flow

- Input data binatang (jika belum ada).

- Buat entri di `pet_hotel_bookings`.

- Buat transaksi setelah tanggal_keluar diketahui, `input transactions` + `transaction_items`.

---

### 4. Pet Shop (Produk atau Obat tanpa Layanan)

- Penjualan langsung dari `products` atau `obat`.

- Buat transaksi langsung → `transactions` + `transaction_items` (`item_type = product`).

---

🎁 ALUR SISTEM (FLOWCHART KONSEP)
Berikut adalah alur logika dalam bentuk poin, saya akan lanjutkan dengan membuat flowchart visual.

📝 High-Level Flowchart Breakdown:

```
Start

        Pilih Jenis Kunjungan:

        Walk-in → Lanjut ke Medical/Grooming → Transaksi.

        Konsultasi → Input Consultations → Buat Appointment → Layanan → Transaksi.

        Pet Hotel → Input Pet Hotel Booking → Transaksi.

        Pet Shop → Penjualan Produk/Obat → Transaksi.

End
```
