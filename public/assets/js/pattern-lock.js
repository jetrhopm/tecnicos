(function () {
  /*
   * Selector de patron / clave de desbloqueo para el registro de orden.
   * - Modo "Patron": rejilla 3x3; se arrastra o se hace clic/teclado para unir
   *   puntos. El resultado se serializa como "Patron: 1-2-3-6".
   * - Modo "Clave / PIN": se escribe la clave del equipo (con teclado en pantalla
   *   opcional). El texto va tal cual.
   * Fuente unica de verdad: el hidden #password_equipo, que es lo que viaja al
   *   backend (mismo campo de siempre; no se agrega nada nuevo a la base).
   */
  const root = document.querySelector('[data-pattern-lock]');
  if (!root) {
    return;
  }

  const hidden = root.querySelector('#password_equipo');
  const grid = root.querySelector('[data-pattern-grid]');
  const path = root.querySelector('[data-pattern-path]');
  const readout = root.querySelector('[data-pattern-readout]');
  const clearBtn = root.querySelector('[data-pattern-clear]');
  const claveInput = root.querySelector('[data-lock-clave]');
  const tabs = Array.from(root.querySelectorAll('[data-lock-tab]'));
  const panels = Array.from(root.querySelectorAll('[data-lock-panel]'));
  const dots = Array.from(root.querySelectorAll('.pattern-dot'));

  if (!hidden || !grid || !path || dots.length !== 9) {
    return;
  }

  let mode = 'patron';
  let sequence = [];
  let dragging = false;

  function dotCenter(n) {
    // viewBox 0..300; celdas centradas en 50/150/250.
    const col = (n - 1) % 3;
    const row = Math.floor((n - 1) / 3);
    return { x: 50 + col * 100, y: 50 + row * 100 };
  }

  function redrawLines() {
    const points = sequence.map((n) => {
      const c = dotCenter(n);
      return c.x + ',' + c.y;
    });
    path.setAttribute('points', points.join(' '));
  }

  function updateValue() {
    if (mode !== 'patron') {
      return;
    }
    if (sequence.length >= 2) {
      hidden.value = 'Patron: ' + sequence.join('-');
      readout.textContent = 'Patron ' + sequence.join(' - ');
    } else {
      hidden.value = '';
      readout.textContent = 'Sin patron';
    }
  }

  function addDot(n) {
    if (mode !== 'patron' || sequence.includes(n)) {
      return;
    }
    sequence.push(n);
    const dot = dots[n - 1];
    dot.classList.add('is-on');
    dot.textContent = String(sequence.length);
    dot.setAttribute('aria-pressed', 'true');
    redrawLines();
    updateValue();
  }

  function clearPattern() {
    sequence = [];
    dots.forEach((dot) => {
      dot.classList.remove('is-on');
      dot.textContent = '';
      dot.setAttribute('aria-pressed', 'false');
    });
    redrawLines();
    updateValue();
  }

  // --- Interaccion con la rejilla (arrastre + clic + teclado) ---
  dots.forEach((dot) => {
    const n = Number(dot.dataset.dot);

    dot.addEventListener('pointerdown', (event) => {
      event.preventDefault();
      dragging = true;
      addDot(n);
    });

    dot.addEventListener('pointerenter', () => {
      if (dragging) {
        addDot(n);
      }
    });

    // Clic/Enter/Espacio: agrega en orden (cubre teclado y taps sueltos).
    dot.addEventListener('click', () => addDot(n));
  });

  window.addEventListener('pointerup', () => {
    dragging = false;
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', clearPattern);
  }

  // --- Cambio de modo ---
  function setMode(next) {
    mode = next;
    tabs.forEach((tab) => {
      const active = tab.dataset.lockTab === next;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    panels.forEach((panel) => {
      panel.classList.toggle('d-none', panel.dataset.lockPanel !== next);
    });

    if (next === 'clave') {
      clearPattern();
      hidden.value = claveInput ? claveInput.value.trim() : '';
    } else {
      if (claveInput) {
        claveInput.value = '';
      }
      updateValue();
    }
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => setMode(tab.dataset.lockTab));
  });

  // --- Modo clave: input de texto + teclado en pantalla ---
  if (claveInput) {
    claveInput.addEventListener('input', () => {
      if (mode === 'clave') {
        hidden.value = claveInput.value.trim();
      }
    });
  }

  root.querySelectorAll('[data-keypad]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!claveInput) {
        return;
      }
      const key = btn.dataset.keypad;
      if (key === 'back') {
        claveInput.value = claveInput.value.slice(0, -1);
      } else {
        claveInput.value += key;
      }
      claveInput.dispatchEvent(new Event('input', { bubbles: true }));
      claveInput.focus();
    });
  });

  // Estado inicial coherente.
  clearPattern();
})();
