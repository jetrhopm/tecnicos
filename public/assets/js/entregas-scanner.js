(function () {
  /*
   * Escaneo para entrega:
   * - Usa camara local o lector compatible para leer codigo de barras/QR.
   * - Solo llena el campo "codigo_entrega" y envia el formulario de busqueda.
   * - La liberacion real del equipo ocurre en PHP, con sesion y validaciones.
   * - En celulares, los navegadores suelen exigir HTTPS para permitir camara.
   */
  const form = document.getElementById('delivery-scan-form');
  const input = document.getElementById('codigo_entrega');
  const startButton = document.getElementById('start-camera-scan');
  const stopButton = document.getElementById('stop-camera-scan');
  const wrap = document.getElementById('camera-scanner-wrap');
  const alertBox = document.getElementById('camera-scan-alert');
  const readerId = 'camera-scanner';
  let scanner = null;
  let running = false;

  if (!form || !input || !startButton || !stopButton || !wrap || !alertBox) {
    return;
  }

  function showMessage(message, type = 'warning') {
    alertBox.className = 'alert alert-' + type + ' mt-3 mb-0';
    alertBox.textContent = message;
    alertBox.classList.remove('d-none');
  }

  function hideMessage() {
    alertBox.classList.add('d-none');
    alertBox.textContent = '';
  }

  function normalizeCode(value) {
    return String(value || '').trim().toUpperCase();
  }

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

  document.documentElement.setAttribute('data-delivery-scanner', getScannerClass() ? 'ready' : 'missing');

  function canUseCameraHere() {
    // Por seguridad del navegador, getUserMedia funciona en HTTPS o localhost.
    // En una IP local con HTTP la camara normalmente queda bloqueada.
    return window.isSecureContext || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
  }

  async function stopScanner() {
    if (!scanner || !running) {
      wrap.classList.add('d-none');
      stopButton.classList.add('d-none');
      startButton.disabled = false;
      return;
    }

    try {
      await scanner.stop();
      await scanner.clear();
    } catch (error) {
      // La camara pudo cerrarse sola despues de leer; no hace falta interrumpir al usuario.
    } finally {
      running = false;
      wrap.classList.add('d-none');
      stopButton.classList.add('d-none');
      startButton.disabled = false;
    }
  }

  async function startScanner() {
    hideMessage();

    if (!canUseCameraHere()) {
      showMessage('El navegador bloquea la camara en HTTP desde una IP local. Para usar camara desde celular necesitas HTTPS, por ejemplo en Hostinger con SSL activo. Mientras tanto puedes usar lector USB o escribir la clave.');
      return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      showMessage('Este navegador no expone acceso a camara. Usa lector USB o escribe la clave manualmente.');
      return;
    }

    const ScannerClass = getScannerClass();
    const SupportedFormats = getSupportedFormats();

    if (!ScannerClass) {
      showMessage('No se pudo cargar el lector de codigos. Revisa la conexion o recarga la pagina.');
      return;
    }

    startButton.disabled = true;
    stopButton.classList.remove('d-none');
    wrap.classList.remove('d-none');

    try {
      scanner = scanner || new ScannerClass(readerId);
      const formats = SupportedFormats ? [
        SupportedFormats.CODE_128,
        SupportedFormats.CODE_39,
        SupportedFormats.EAN_13,
        SupportedFormats.QR_CODE
      ] : [];

      await scanner.start(
        { facingMode: 'environment' },
        {
          fps: 10,
          qrbox: { width: 280, height: 160 },
          aspectRatio: 1.777,
          formatsToSupport: formats.length ? formats : undefined
        },
        async (decodedText) => {
          // El codigo leido no trae permisos ni datos sensibles: es una llave
          // de busqueda. El servidor confirma si existe y si puede entregarse.
          const code = normalizeCode(decodedText);
          if (!code) {
            return;
          }

          input.value = code;
          showMessage('Codigo detectado: ' + code, 'success');
          await stopScanner();
          form.submit();
        }
      );

      running = true;
    } catch (error) {
      running = false;
      wrap.classList.add('d-none');
      stopButton.classList.add('d-none');
      startButton.disabled = false;
      showMessage('No se pudo abrir la camara. Verifica permisos del navegador, HTTPS y que ninguna otra app este usando la camara.');
    }
  }

  startButton.addEventListener('click', startScanner);
  stopButton.addEventListener('click', stopScanner);
})();
