UPDATE QC ROOM + TRAVELER SOP TITIPGO

File yang diubah:
1. qc_room.php
2. traveler_sop.php
3. css/qc_room.css
4. css/traveler_sop.css
5. database/qc_chat_update.sql

Cara pakai:
1. Copy qc_room.php ke folder utama project TitipGo.
2. Copy traveler_sop.php ke folder utama project TitipGo.
3. Copy css/qc_room.css ke folder css.
4. Copy css/traveler_sop.css ke folder css.
5. Buat folder uploads/qc di project, atau biarkan PHP membuat otomatis.
6. Import database/qc_chat_update.sql ke phpMyAdmin.

Perubahan fitur:
- qc_room.php sekarang punya empty state jika buyer belum punya pesanan berjalan.
- Jika belum ada pesanan, muncul tombol Jelajahi Produk dan Buat Request Manual.
- Buyer bisa chat traveler dan upload foto QC.
- Traveler bisa chat buyer dan upload foto struk/produk dari traveler_sop.php.
- Chat tersimpan di tabel qc_messages.
- Foto tersimpan di folder uploads/qc.
