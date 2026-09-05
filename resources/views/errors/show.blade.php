@php
  $isAdminError = request()->is('admin', 'admin/*');
  $isAuthenticatedAdmin = $isAdminError && auth()->check();
  $primaryUrl = $isAdminError
      ? ($isAuthenticatedAdmin ? route('admin.dashboard') : route('admin.login'))
      : route('home');
  $primaryLabel = $isAdminError
      ? ($isAuthenticatedAdmin ? 'Ke dashboard' : 'Ke login admin')
      : 'Ke beranda';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ $title }} — {{ $site->name }}</title>
  <link rel="icon" type="image/webp" href="{{ $site->logoUrl }}">
  <style>
    :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #14213d; background: #f4f7fc; }
    * { box-sizing: border-box; }
    body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: radial-gradient(700px 360px at 5% 0%, rgba(246,136,31,.16), transparent 55%), radial-gradient(700px 360px at 100% 100%, rgba(1,49,128,.14), transparent 55%), #f4f7fc; }
    main { width: min(560px, 100%); padding: 40px; border: 1px solid #dfe6f1; border-radius: 24px; background: #fff; text-align: center; box-shadow: 0 24px 70px rgba(16,35,70,.12); }
    img { max-width: 220px; max-height: 64px; object-fit: contain; margin-bottom: 22px; }
    .code { display: inline-block; margin-bottom: 12px; padding: 6px 12px; border-radius: 999px; color: #013180; background: #e9f0fc; font-size: 13px; font-weight: 800; letter-spacing: .08em; }
    h1 { margin: 0 0 12px; font-size: clamp(28px, 6vw, 40px); letter-spacing: -.03em; }
    p { margin: 0 auto; max-width: 440px; color: #647089; line-height: 1.7; }
    .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 28px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 10px 18px; border: 1px solid #013180; border-radius: 12px; background: #013180; color: #fff; font: inherit; font-weight: 750; text-decoration: none; cursor: pointer; }
    .btn.secondary { background: #fff; color: #013180; }
    .help { margin-top: 22px; font-size: 12px; color: #8993a7; }
    @media (max-width: 560px) { main { padding: 30px 20px; } .actions, .btn { width: 100%; } }
  </style>
</head>
<body>
  <main>
    <img src="{{ $site->logoUrl }}" alt="{{ $site->name }}">
    <div class="code">ERROR {{ $status }}</div>
    <h1>{{ $title }}</h1>
    <p>{{ $message }}</p>
    <div class="actions">
      @if(!empty($reload))
        <button class="btn" type="button" onclick="window.location.reload()">Muat ulang</button>
      @else
        <a class="btn" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
      @endif
      <button class="btn secondary" type="button" onclick="if (window.opener) { window.close(); } else { window.history.back(); }">
        Kembali / tutup
      </button>
    </div>
    <p class="help">Jika masalah berulang, hubungi administrator dan sampaikan kode error {{ $status }}.</p>
  </main>
</body>
</html>
