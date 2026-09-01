<div class="password-field">
  <input
    type="password"
    name="{{ $name }}"
    @if(!empty($id)) id="{{ $id }}" @endif
    @if(!empty($value)) value="{{ $value }}" @endif
    @if(!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
    @if(!empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
    @if(!empty($minlength)) minlength="{{ $minlength }}" @endif
    @if(!empty($required)) required @endif
  >
  <button class="password-toggle" type="button" aria-pressed="false" aria-label="Tampilkan password" title="Tampilkan password">
    <svg class="eye-show" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
    <svg class="eye-hide" viewBox="0 0 24 24" hidden aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 104.2 4.2"/><path d="M9.5 5.2A11 11 0 0112 5c6.5 0 10 7 10 7a18 18 0 01-3.2 4.1"/><path d="M6.7 6.7C4.1 8.4 2 12 2 12s3.5 7 10 7c1.3 0 2.5-.2 3.6-.6"/></svg>
  </button>
</div>
