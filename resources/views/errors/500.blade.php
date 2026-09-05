@include('errors.show', [
  'status' => 500,
  'title' => 'Terjadi kesalahan pada sistem',
  'message' => 'Permintaan belum dapat diproses. Data teknis tidak ditampilkan demi keamanan. Silakan coba kembali atau hubungi administrator.',
])
