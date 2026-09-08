<div class="itinerary-pdf-modal" id="itinerary-pdf-modal" hidden>
  <div class="itinerary-pdf-modal-backdrop" data-itinerary-close></div>
  <div class="itinerary-pdf-modal-panel" role="dialog" aria-modal="true" aria-labelledby="itinerary-pdf-modal-title">
    <div class="itinerary-pdf-modal-head">
      <h3 id="itinerary-pdf-modal-title">Itinerary</h3>
      <div class="itinerary-pdf-modal-actions">
        <a class="btn light compact itinerary-pdf-download" id="itinerary-pdf-download" href="#" download>
          <i class="bi bi-download" aria-hidden="true"></i> Download
        </a>
        <button type="button" class="itinerary-pdf-modal-close" data-itinerary-close aria-label="Tutup">×</button>
      </div>
    </div>
    <iframe id="itinerary-pdf-frame" title="Itinerary PDF" src=""></iframe>
  </div>
</div>

@once
  @push('scripts')
  <script>
  (function () {
    var modal = document.getElementById('itinerary-pdf-modal');
    if (!modal) return;

    var titleEl = document.getElementById('itinerary-pdf-modal-title');
    var frame = document.getElementById('itinerary-pdf-frame');
    var downloadLink = document.getElementById('itinerary-pdf-download');

    function openItineraryPdf(trigger) {
      var pdfUrl = trigger.dataset.pdf || '';
      var label = trigger.dataset.label || 'Itinerary';
      var filename = trigger.dataset.download || 'itinerary.pdf';

      titleEl.textContent = 'Itinerary — ' + label;
      frame.src = pdfUrl;
      downloadLink.href = pdfUrl;
      downloadLink.setAttribute('download', filename);
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function closeItineraryPdf() {
      modal.hidden = true;
      frame.src = '';
      downloadLink.href = '#';
      document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('.itinerary-pdf-trigger');
      if (trigger) {
        openItineraryPdf(trigger);
        return;
      }

      if (e.target.closest('[data-itinerary-close]')) {
        closeItineraryPdf();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !modal.hidden) closeItineraryPdf();
    });
  })();
  </script>
  @endpush
@endonce
