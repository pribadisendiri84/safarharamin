@include('errors.show', [
  'status' => method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400,
  'title' => 'Permintaan tidak dapat diproses',
  'message' => 'Periksa kembali alamat atau data yang dikirim, lalu coba lagi.',
])
