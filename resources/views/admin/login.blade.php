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
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <label>Email</label>
  <input type="email" name="email" value="{{ old('email') }}" required autofocus>
  <label>Password</label>
  @include('admin.partials.password-field', ['name' => 'password', 'required' => true, 'autocomplete' => 'current-password'])
  <button class="btn" type="submit">Masuk</button>
</form>
@include('admin.partials.password-toggle-script')
</body>
</html>
