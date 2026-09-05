@extends('layouts.admin')

@section('title', 'Pengguna')
@section('content')
<div class="page-head">
  <div>
    <h1>Pengguna</h1>
    <p class="sub">Superadmin bisa membuat akun. Hapus memakai soft delete — akun terhapus tidak bisa login sampai dipulihkan.</p>
  </div>
</div>

@include('admin.partials.scope-tabs')

@unless($trashed)
<div class="panel form-narrow">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'plus']) Tambah pengguna</div>
  <form class="form form-pad" method="post" action="{{ route('admin.users.store') }}">
    @csrf
    <div class="row2">
      <label>Nama<input name="name" value="{{ old('name') }}" required></label>
      <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
    </div>
    <div class="row2">
      <label>Password
        @include('admin.partials.password-field', ['name' => 'password', 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password'])
      </label>
      <label>Peran
        <select name="role" required>
          @foreach($roles as $role)
            <option value="{{ $role->value }}" @selected(old('role', 'admin') === $role->value)>{{ $role->label() }}</option>
          @endforeach
        </select>
      </label>
    </div>
    <button class="btn" type="submit">Tambah pengguna</button>
  </form>
</div>
@endunless

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Pengguna</th>
          <th>Keamanan login</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
          <tr class="{{ $user->trashed() ? 'is-deleted' : '' }}">
            <td>
              @if($user->trashed())
                <b>{{ $user->name }}</b>
                <small>{{ $user->email }} · {{ $user->resolvedRole()->label() }}</small>
              @else
                <form method="post" action="{{ route('admin.users.update', $user) }}" class="form user-edit">
                  @csrf
                  @method('PUT')
                  <input name="name" value="{{ old('name', $user->name) }}" required>
                  <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                  <select name="role" required>
                    @foreach($roles as $role)
                      <option value="{{ $role->value }}" @selected(old('role', $user->resolvedRole()->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                  </select>
                  <input type="password" name="password" minlength="8" placeholder="Kosong = tidak diubah" autocomplete="new-password">
                  <button class="btn gray compact" type="submit">Update</button>
                </form>
              @endif
            </td>
            <td>
              @if($user->isLoginLocked())
                <span class="badge fullbook">Terkunci</span>
                <small>Sejak {{ $user->login_locked_at?->format('d M Y H:i') }}</small>
              @elseif($user->login_failed_attempts > 0)
                <span class="badge draft">{{ $user->login_failed_attempts }} percobaan gagal</span>
              @else
                <span class="badge published">Aman</span>
              @endif
            </td>
            <td>@include('admin.partials.timestamps', ['model' => $user])</td>
            <td>
              @if($user->trashed())
                <form method="post" action="{{ route('admin.users.restore', $user) }}">
                  @csrf
                  <button class="btn gray compact" type="submit">Pulihkan</button>
                </form>
              @elseif(! $user->is(auth()->user()))
                @if($user->isLoginLocked())
                  <form method="post" action="{{ route('admin.users.unlock', $user) }}">
                    @csrf
                    <button class="btn gray compact" type="submit">Buka kunci</button>
                  </form>
                @endif
                <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus pengguna ini? Akun masih bisa dipulihkan.')">
                  @csrf
                  @method('DELETE')
                  <button class="btn red compact" type="submit">Hapus</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="empty-state">{{ $trashed ? 'Tidak ada pengguna terhapus.' : 'Belum ada pengguna.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
