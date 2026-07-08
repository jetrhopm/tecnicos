(function () {
  /*
   * Escaner de codigo de barras reutilizable (camara).
   * Se activa en cualquier boton con:
   *   data-barcode-scan="ID_DEL_INPUT"   -> llena ese input con lo leido
   *   data-barcode-upper                  -> lo pasa a mayusculas (SKU)
   *   data-barcode-submit                 -> envia el formulario tras leer (busqueda)
   * Usa html5-qrcode (mismo vendor que Entregas). Un lector USB funciona sin
   * esto: teclea el codigo directo en el campo enfocado.
   */
  function getScannerClass() {
    if (window.Html5Qrcode) {
      return window.Html5Qrcode;
    }
    return window.__Html5QrcodeLibrary__ ? window.__Html5QrcodeLibrary__.Html5Qrcode : null;
  }

  function getSupportedFormats() {
    if (window.Html5QrcodeSupportedFormats) {
      return window.Html5QrcodeSupportedFormats;
    }
    return window.__Html5QrcodeLibrary__ ? window.__Html5QrcodeLibrary__.Html5QrcodeSupportedFormats : null;
  }

  function canUseCameraHere() {
    return window.isSecureContext || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
  }

  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-barcode-scan]'));
  if (buttons.length === 0) {
    return;
  }

  var seq = 0;
  var active = null; // { scanner, stop } del escaner en curso

  async function stopActive() {
    if (!active) {
      return;
    }
    var current = active;
    active = null;
    current.panel.classList.add('d-none');
    current.button.disabled = false;
    try {
      await current.scanner.stop();
      await current.scanner.clear();
    } catch (error) {
      /* la camara pudo cerrarse sola tras leer */
    }
  }

  buttons.forEach(function (button) {
    var targetId = button.getAttribute('data-barcode-scan');
    var input = document.getElementById(targetId);
    if (!input) {
      return;
    }

    var toUpper = button.hasAttribute('data-barcode-upper');
    var doSubmit = button.hasAttribute('data-barcode-submit');
    var readerId = 'barcode-reader-' + (seq++);

    var panel = document.createElement('div');
    panel.className = 'barcode-scan-panel d-none';
    panel.innerHTML =
      '<div class="barcode-scan-reader" id="' + readerId + '"></div>' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mt-2">' +
      '<span class="small barcode-scan-msg text-muted">Apunta al codigo de barras del producto.</span>' +
      '<button type="button" class="btn btn-outline-secondary btn-sm barcode-scan-close">Cerrar</button>' +
      '</div>';

    var anchor = input.closest('.row') || input.closest('.input-group') || input.parentNode;
    anchor.parentNode.insertBefore(panel, anchor.nextSibling);

    var msg = panel.querySelector('.barcode-scan-msg');

    function setMsg(text, danger) {
      msg.textContent = text;
      msg.classList.toggle('text-danger', !!danger);
      msg.classList.toggle('text-muted', !danger);
    }

    panel.querySelector('.barcode-scan-close').addEventListener('click', stopActive);

    button.addEventListener('click', async function () {
      await stopActive();

      if (!canUseCameraHere()) {
        panel.classList.remove('d-none');
        setMsg('La camara requiere HTTPS (o localhost). Puedes usar un lector USB o escribir el codigo.', true);
        return;
      }

      var ScannerClass = getScannerClass();
      if (!ScannerClass || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        panel.classList.remove('d-none');
        setMsg('Este navegador no permite la camara. Usa lector USB o escribe el codigo.', true);
        return;
      }

      var Formats = getSupportedFormats();
      var formats = Formats ? [
        Formats.CODE_128, Formats.CODE_39, Formats.EAN_13, Formats.EAN_8,
        Formats.UPC_A, Formats.UPC_E, Formats.ITF, Formats.QR_CODE
      ] : [];

      panel.classList.remove('d-none');
      setMsg('Apunta al codigo de barras del producto.');
      button.disabled = true;

      var scanner = new ScannerClass(readerId);
      active = { scanner: scanner, panel: panel, button: button };

      try {
        await scanner.start(
          { facingMode: 'environment' },
          {
            fps: 10,
            qrbox: { width: 260, height: 150 },
            formatsToSupport: formats.length ? formats : undefined
          },
          async function (decodedText) {
            var code = String(decodedText || '').trim();
            if (!code) {
              return;
            }
            input.value = toUpper ? code.toUpperCase() : code;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await stopActive();
            if (doSubmit && input.form) {
              input.form.submit();
            } else {
              input.focus();
            }
          }
        );
      } catch (error) {
        active = null;
        button.disabled = false;
        setMsg('No se pudo abrir la camara. Revisa permisos, HTTPS y que no este en uso.', true);
      }
    });
  });
})();
