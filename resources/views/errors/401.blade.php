@include('errors.show', [
  'status' => 401,
  'title' => 'Silakan masuk terlebih dahulu',
  'message' => 'Sesi login diperlukan untuk membuka halaman ini.',
])
