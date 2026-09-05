@include('errors.show', [
  'status' => 403,
  'title' => 'Akses tidak diizinkan',
  'message' => 'Akun Anda tidak memiliki izin untuk membuka halaman atau menjalankan tindakan ini.',
])
