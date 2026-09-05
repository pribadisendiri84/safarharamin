@include('errors.show', [
  'status' => method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500,
  'title' => 'Layanan mengalami gangguan',
  'message' => 'Permintaan belum dapat diproses. Silakan coba kembali atau hubungi administrator jika masalah berulang.',
])
