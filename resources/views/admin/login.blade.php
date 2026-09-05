<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — {{ $site->name }}</title>
<link rel="icon" type="image/webp" href="{{ $site->logoUrl }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-body">
<form class="login-card" method="post" action="{{ route('admin.login.store') }}">
  @csrf
  <img class="login-logo" src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
  <p>Masuk ke panel admin</p>
  <label>Email</label>
  <input type="email" name="email" value="{{ old('email') }}" required autofocus>
  <label>Password</label>
  @include('admin.partials.password-field', ['name' => 'password', 'required' => true, 'autocomplete' => 'current-password'])
  <label for="admin-captcha">Kode keamanan</label>
  <div class="captcha-box">
    <img id="captcha-image" src="{{ route('admin.captcha', ['v' => now()->timestamp]) }}" alt="Kode keamanan enam karakter">
    <button class="btn gray compact" id="captcha-refresh" type="button">Muat ulang</button>
  </div>
  <input id="admin-captcha" type="text" name="captcha" required maxlength="6" minlength="6"
         autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Ketik kode pada gambar">
  <button class="btn" type="submit">Masuk</button>
</form>
@include('partials.feedback-modal')
@include('admin.partials.password-toggle-script')
<script>
document.getElementById('captcha-refresh').addEventListener('click', function () {
  var image = document.getElementById('captcha-image');
  image.src = @json(route('admin.captcha')).concat('?v=', Date.now());
  document.getElementById('admin-captcha').value = '';
  document.getElementById('admin-captcha').focus();
});
</script>
</body>
</html>
