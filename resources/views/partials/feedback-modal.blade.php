@php
  $feedbackMessage = $feedbackMessage
      ?? session('ok')
      ?? session('err')
      ?? ($errors->any() ? $errors->first() : null);
  $feedbackType = $feedbackType
      ?? (session('ok') ? 'success' : 'error');
  $feedbackTitle = $feedbackTitle
      ?? session('feedback_title')
      ?? ($feedbackType === 'success' ? 'Berhasil' : 'Perlu diperiksa');
  $feedbackActionUrl = $feedbackActionUrl
      ?? session('feedback_action_url')
      ?? null;
  $feedbackActionLabel = $feedbackActionLabel
      ?? session('feedback_action_label')
      ?? null;
  $feedbackActionTarget = $feedbackActionTarget
      ?? session('feedback_action_target')
      ?? null;
@endphp

@if(filled($feedbackMessage))
  <div class="feedback-modal" data-feedback-modal role="dialog" aria-modal="true" aria-labelledby="feedback-title">
    <div class="feedback-modal__backdrop" data-feedback-close></div>
    <section class="feedback-modal__card" tabindex="-1">
      <button class="feedback-modal__close" type="button" data-feedback-close aria-label="Tutup pesan">×</button>
      <div class="feedback-modal__icon is-{{ $feedbackType }}" aria-hidden="true">
        {{ $feedbackType === 'success' ? '✓' : '!' }}
      </div>
      <h2 id="feedback-title">{{ $feedbackTitle }}</h2>
      <p>{{ $feedbackMessage }}</p>
      <div class="feedback-modal__actions">
        @if($feedbackActionUrl && $feedbackActionLabel)
          <a class="btn" href="{{ $feedbackActionUrl }}"
             @if($feedbackActionTarget) target="{{ $feedbackActionTarget }}" rel="noopener" @endif>
            {{ $feedbackActionLabel }}
          </a>
        @endif
        <button class="btn gray" type="button" data-feedback-close>Tutup</button>
      </div>
    </section>
  </div>
  <script>
  (function () {
    var modal = document.querySelector('[data-feedback-modal]');
    if (!modal) return;
    var card = modal.querySelector('.feedback-modal__card');
    var previousFocus = document.activeElement;
    document.body.classList.add('has-feedback-modal');
    window.requestAnimationFrame(function () { if (card) card.focus(); });
    modal.querySelectorAll('[data-feedback-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        modal.remove();
        document.body.classList.remove('has-feedback-modal');
        if (previousFocus && previousFocus.focus) previousFocus.focus();
      });
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && document.body.contains(modal)) {
        modal.remove();
        document.body.classList.remove('has-feedback-modal');
      }
    });
  })();
  </script>
@endif
