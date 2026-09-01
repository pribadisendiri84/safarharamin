@extends('layouts.admin')

@section('title', 'Testimoni')
@section('content')
<div class="page-head">
  <div>
    <h1>Testimoni</h1>
    <p class="sub">Tampil di beranda dan halaman /testimoni jika status Tayang.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn" href="{{ route('admin.testimonials.create') }}">@include('admin.partials.icon', ['name' => 'plus']) Tambah testimoni</a>
  </div>
</div>

@include('admin.partials.scope-tabs')
<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Jamaah</th>
          <th>Paket</th>
          <th>Isi</th>
          <th>Status</th>
          <th>Waktu</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($testimonials as $item)
          <tr class="{{ $item->trashed() ? 'is-deleted' : '' }}">
            <td>
              <b>{{ $item->name }}</b>
              @if($item->city)<small>{{ $item->city }}</small>@endif
            </td>
            <td>{{ $item->package_title ?: '—' }}</td>
            <td>{{ \Illuminate\Support\Str::limit($item->quote, 90) }}</td>
            <td><span class="badge {{ $item->is_published ? 'tayang' : 'draft' }}">{{ $item->is_published ? 'Tayang' : 'Draft' }}</span></td>
            <td>@include('admin.partials.timestamps', ['model' => $item])</td>
            <td>
              @include('admin.partials.row-actions', [
                'item' => $item,
                'edit' => route('admin.testimonials.edit', $item),
                'destroy' => route('admin.testimonials.destroy', $item),
                'restore' => route('admin.testimonials.restore', $item),
                'confirm' => 'Hapus testimoni ini?',
              ])
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty-state">{{ $trashed ? 'Tidak ada testimoni terhapus.' : 'Belum ada testimoni.' }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
