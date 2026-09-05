@include('errors.show', [
  'status' => 503,
  'title' => 'Layanan sedang tidak tersedia',
  'message' => 'Sistem sedang menjalani perawatan atau mengalami gangguan sementara. Silakan coba lagi beberapa saat.',
  'reload' => true,
])
