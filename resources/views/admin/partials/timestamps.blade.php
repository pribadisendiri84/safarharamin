<div class="time-stack">
  <span>Dibuat {{ $model->created_at?->format('d M Y H:i') ?? '—' }}</span>
  <span>Diubah {{ $model->updated_at?->format('d M Y H:i') ?? '—' }}</span>
  @if($model->trashed())
    <span>Dihapus {{ $model->deleted_at?->format('d M Y H:i') }}</span>
  @endif
  @if($model->creator)
    <span>Oleh {{ $model->creator->name }}</span>
  @endif
</div>
