@include('errors.show', [
  'status' => 429,
  'title' => 'Terlalu banyak permintaan',
  'message' => 'Sistem menerima terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar lalu coba kembali.',
  'reload' => true,
])
