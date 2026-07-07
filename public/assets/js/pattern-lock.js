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
  let previewPoint = null;
  const initialValue = hidden.value.trim();

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

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
    if (dragging && previewPoint && sequence.length > 0) {
      points.push(previewPoint.x + ',' + previewPoint.y);
    }
    path.setAttribute('points', points.join(' '));
  }

  function pointerPoint(event) {
    const rect = grid.getBoundingClientRect();
    if (rect.width <= 0 || rect.height <= 0) {
      return null;
    }

    return {
      x: clamp(((event.clientX - rect.left) / rect.width) * 300, 0, 300),
      y: clamp(((event.clientY - rect.top) / rect.height) * 300, 0, 300)
    };
  }

  function dotFromPointer(event) {
    const point = pointerPoint(event);
    if (!point) {
      return null;
    }

    let closest = null;
    let closestDistance = Infinity;
    for (let n = 1; n <= 9; n += 1) {
      const center = dotCenter(n);
      const distance = Math.hypot(point.x - center.x, point.y - center.y);
      if (distance < closestDistance) {
        closestDistance = distance;
        closest = n;
      }
    }

    return closestDistance <= 46 ? closest : null;
  }

  function trackPointer(event) {
    if (!dragging || mode !== 'patron') {
      return;
    }

    event.preventDefault();
    previewPoint = pointerPoint(event);
    const n = dotFromPointer(event);
    if (n) {
      addDot(n);
    } else {
      redrawLines();
    }
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
    hidden.dispatchEvent(new Event('input', { bubbles: true }));
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
    previewPoint = null;
    dots.forEach((dot) => {
      dot.classList.remove('is-on');
      dot.textContent = '';
      dot.setAttribute('aria-pressed', 'false');
    });
    redrawLines();
    hidden.value = '';
    readout.textContent = 'Sin patron';
    hidden.dispatchEvent(new Event('input', { bubbles: true }));
  }

  // --- Interaccion con la rejilla (arrastre + clic + teclado) ---
  grid.addEventListener('pointerdown', (event) => {
    if (mode !== 'patron') {
      return;
    }

    event.preventDefault();
    dragging = true;
    previewPoint = pointerPoint(event);
    if (grid.setPointerCapture && event.pointerId !== undefined) {
      grid.setPointerCapture(event.pointerId);
    }
    const n = dotFromPointer(event);
    if (n) {
      addDot(n);
    } else {
      redrawLines();
    }
  });

  grid.addEventListener('pointermove', trackPointer);

  function stopDragging(event) {
    if (!dragging) {
      return;
    }
    dragging = false;
    previewPoint = null;
    if (event && grid.releasePointerCapture && event.pointerId !== undefined) {
      try {
        grid.releasePointerCapture(event.pointerId);
      } catch (error) {
        // El navegador puede liberar automaticamente la captura al terminar el gesto.
      }
    }
    redrawLines();
  }

  grid.addEventListener('pointerup', stopDragging);
  grid.addEventListener('pointercancel', stopDragging);
  grid.addEventListener('lostpointercapture', stopDragging);

  dots.forEach((dot) => {
    const n = Number(dot.dataset.dot);

    // Clic/Enter/Espacio: agrega en orden (cubre teclado y taps sueltos).
    dot.addEventListener('click', () => addDot(n));
  });

  window.addEventListener('pointerup', stopDragging);

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
        hidden.dispatchEvent(new Event('input', { bubbles: true }));
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

  // Estado inicial coherente. Si el formulario trae valor guardado (edicion de
  // equipo), se restaura sin obligar al usuario a capturarlo de nuevo.
  function setExternalValue(value) {
    const nextValue = String(value || '').trim();
    clearPattern();

    if (nextValue.startsWith('Patron:')) {
      nextValue.replace('Patron:', '').split('-').forEach((part) => {
        const n = Number(part.trim());
        if (n >= 1 && n <= 9) {
          addDot(n);
        }
      });
      return;
    }

    if (nextValue !== '' && claveInput) {
      setMode('clave');
      claveInput.value = nextValue;
      hidden.value = nextValue;
      return;
    }

    setMode('patron');
  }

  root.addEventListener('pattern-lock:set-value', (event) => {
    setExternalValue(event.detail && event.detail.value ? event.detail.value : '');
  });

  clearPattern();
  if (initialValue.startsWith('Patron:')) {
    initialValue.replace('Patron:', '').split('-').forEach((part) => {
      const n = Number(part.trim());
      if (n >= 1 && n <= 9) {
        addDot(n);
      }
    });
  } else if (initialValue !== '' && claveInput) {
    setMode('clave');
    claveInput.value = initialValue;
    hidden.value = initialValue;
  }
})();
