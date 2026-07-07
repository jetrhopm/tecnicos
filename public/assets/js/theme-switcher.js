/* =======================================================================
   Selector de temas (checklist por usuario)
   Aplica y guarda el tema elegido en localStorage, separado por usuario.
   No toca la logica del backend: el tema vive en el navegador.
   ======================================================================= */
(function () {
  // Clave por usuario (definida temprano en el <head> del layout).
  var key = window.__themeKey || 'tecnico-theme';
  var fallbackKey = 'tecnico-theme';

  function applyTheme(value) {
    if (value) {
      document.documentElement.setAttribute('data-theme', value);
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var current = document.documentElement.getAttribute('data-theme') || '';
    var inputs = document.querySelectorAll('input[name="theme-choice"]');

    inputs.forEach(function (input) {
      input.checked = input.value === current;
      input.addEventListener('change', function () {
        if (!input.checked) {
          return;
        }
        applyTheme(input.value);
        try {
          localStorage.setItem(key, input.value);
          localStorage.setItem(fallbackKey, input.value);
        } catch (e) {
          /* almacenamiento no disponible: el tema solo dura la sesion */
        }
      });
    });

    // Si el tema venia de una clave por usuario antigua, sincronizalo con la
    // clave global para que login y portal publico usen el mismo tema.
    if (current) {
      try {
        localStorage.setItem(fallbackKey, current);
      } catch (e) {
        /* almacenamiento no disponible: no hay nada mas que sincronizar */
      }
    }
  });
})();
