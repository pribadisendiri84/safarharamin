@include('errors.show', [
  'status' => 419,
  'title' => 'Sesi halaman sudah berakhir',
  'message' => 'Halaman terlalu lama terbuka atau token keamanan sudah berubah. Muat ulang sebelum mengirim data kembali.',
  'reload' => true,
])
