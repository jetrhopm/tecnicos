(function () {
  /*
   * Alta rapida de orden:
   * - Lee selecciones/campos del formulario en el navegador.
   * - Mantiene visibles cliente/equipo y rellena datos cuando se reutiliza uno existente.
   * - No guarda nada ni decide permisos; al enviar, PHP vuelve a validar todo.
   */
  const clienteSelect = document.getElementById('cliente_id');
  const equipoSelect = document.getElementById('equipo_id');
  const newClient = document.querySelector('[data-new-client]');
  const newEquipment = document.querySelector('[data-new-equipment]');
  const existingClientNote = document.querySelector('[data-existing-client-note]');
  const existingEquipmentNote = document.querySelector('[data-existing-equipment-note]');
  const clientAction = document.querySelector('[data-client-action]');
  const equipmentAction = document.querySelector('[data-equipment-action]');
  const clientUpdateCheck = document.querySelector('[data-client-update-check]');
  const clientUpdateLabel = document.querySelector('[data-client-update-label]');
  const equipmentActionStatus = document.querySelector('[data-equipment-action-status]');
  const equipmentActionLabel = document.querySelector('[data-equipment-action-label]');
  const equipmentDecision = document.querySelector('[data-equipment-decision]');
  const equipmentDecisionOptions = Array.from(document.querySelectorAll('[data-equipment-decision-option]'));
  const entityCombos = new Map();
  let clientBaseline = {};
  let equipmentBaseline = {};
  let syncingForm = false;

  if (!clienteSelect || !equipoSelect || !newClient || !newEquipment) {
    return;
  }

  function normalize(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function setRequired(container, selector, enabled) {
    container.querySelectorAll(selector).forEach((field) => {
      field.required = enabled;
    });
  }

  function setContainerEnabled(container, enabled) {
    container.querySelectorAll('input, select, textarea').forEach((field) => {
      field.disabled = !enabled;
    });
  }

  function setRadioEnabled(container, enabled) {
    container.querySelectorAll('.equipment-type-card input').forEach((field) => {
      field.disabled = !enabled;
    });
  }

  function fillNamedFields(container, values) {
    Object.entries(values).forEach(([name, value]) => {
      const field = container.querySelector('[name="' + name + '"]');
      if (field) {
        field.value = value || '';
      }
    });
  }

  function clearNamedFields(container, names) {
    names.forEach((name) => {
      const field = container.querySelector('[name="' + name + '"]');
      if (field) {
        field.value = '';
      }
    });
  }

  function fieldValue(container, name) {
    const field = container.querySelector('[name="' + name + '"]');
    if (!field) {
      return '';
    }
    return String(field.value || '').trim();
  }

  function selectedEquipmentType() {
    const checked = newEquipment.querySelector('input[name="tipo"]:checked');
    return checked ? checked.value : '';
  }

  function captureClientBaseline() {
    clientBaseline = {
      nombre_completo: fieldValue(newClient, 'nombre_completo'),
      telefono: fieldValue(newClient, 'telefono'),
      whatsapp: fieldValue(newClient, 'whatsapp'),
      email: fieldValue(newClient, 'email'),
      domicilio: fieldValue(newClient, 'domicilio'),
      ciudad: fieldValue(newClient, 'ciudad'),
      estado_cliente: fieldValue(newClient, 'estado_cliente'),
      notas_cliente: fieldValue(newClient, 'notas_cliente')
    };
  }

  function captureEquipmentBaseline() {
    equipmentBaseline = {
      tipo: selectedEquipmentType(),
      marca: fieldValue(newEquipment, 'marca'),
      modelo: fieldValue(newEquipment, 'modelo'),
      numero_serie: fieldValue(newEquipment, 'numero_serie'),
      imei: fieldValue(newEquipment, 'imei'),
      color: fieldValue(newEquipment, 'color'),
      password_equipo: fieldValue(newEquipment, 'password_equipo'),
      accesorios_recibidos: fieldValue(newEquipment, 'accesorios_recibidos'),
      estado_fisico: fieldValue(newEquipment, 'estado_fisico'),
      observaciones_equipo: fieldValue(newEquipment, 'observaciones_equipo')
    };
  }

  function isChanged(current, baseline) {
    return Object.keys(current).some((key) => String(current[key] || '') !== String(baseline[key] || ''));
  }

  function currentClientValues() {
    return {
      nombre_completo: fieldValue(newClient, 'nombre_completo'),
      telefono: fieldValue(newClient, 'telefono'),
      whatsapp: fieldValue(newClient, 'whatsapp'),
      email: fieldValue(newClient, 'email'),
      domicilio: fieldValue(newClient, 'domicilio'),
      ciudad: fieldValue(newClient, 'ciudad'),
      estado_cliente: fieldValue(newClient, 'estado_cliente'),
      notas_cliente: fieldValue(newClient, 'notas_cliente')
    };
  }

  function currentEquipmentValues() {
    return {
      tipo: selectedEquipmentType(),
      marca: fieldValue(newEquipment, 'marca'),
      modelo: fieldValue(newEquipment, 'modelo'),
      numero_serie: fieldValue(newEquipment, 'numero_serie'),
      imei: fieldValue(newEquipment, 'imei'),
      color: fieldValue(newEquipment, 'color'),
      password_equipo: fieldValue(newEquipment, 'password_equipo'),
      accesorios_recibidos: fieldValue(newEquipment, 'accesorios_recibidos'),
      estado_fisico: fieldValue(newEquipment, 'estado_fisico'),
      observaciones_equipo: fieldValue(newEquipment, 'observaciones_equipo')
    };
  }

  function setClientAction(mode, checked) {
    if (clientAction) {
      clientAction.value = mode;
    }
    if (clientUpdateCheck) {
      clientUpdateCheck.checked = checked;
    }
    if (clientUpdateLabel) {
      clientUpdateLabel.textContent = mode === 'crear' ? 'Crear cliente nuevo' : 'Actualizar cliente seleccionado';
    }
  }

  function equipmentActionText(mode) {
    const labels = {
      crear: 'Crear equipo nuevo',
      reutilizar: 'Usar equipo seleccionado sin modificar',
      actualizar: 'Actualizar equipo seleccionado',
      duplicar: 'Crear nuevo equipo con estos datos',
      pendiente: 'Cambios detectados: elige que hacer con el equipo'
    };
    return labels[mode] || labels.pendiente;
  }

  function setEquipmentDecisionRequired(required, clearSelection) {
    if (equipmentDecision) {
      equipmentDecision.classList.toggle('d-none', !required);
    }
    equipmentDecisionOptions.forEach((option) => {
      option.required = required;
      if (clearSelection) {
        option.checked = false;
      }
    });
  }

  function selectedEquipmentDecision() {
    const selected = equipmentDecisionOptions.find((option) => option.checked);
    return selected ? selected.value : '';
  }

  function setEquipmentAction(mode, requireDecision = false, clearDecision = false) {
    if (equipmentAction) {
      equipmentAction.value = mode;
    }
    if (equipmentActionLabel) {
      equipmentActionLabel.textContent = equipmentActionText(mode);
    }
    if (equipmentActionStatus) {
      equipmentActionStatus.classList.toggle('is-alert', requireDecision);
    }
    setEquipmentDecisionRequired(requireDecision, clearDecision);
  }

  function updateClientActionFromChanges() {
    if (syncingForm) {
      return;
    }
    if (clienteSelect.value === '') {
      setClientAction('crear', true);
      return;
    }
    const changed = isChanged(currentClientValues(), clientBaseline);
    setClientAction(changed ? 'actualizar' : 'reutilizar', changed);
  }

  function updateEquipmentActionFromChanges() {
    if (syncingForm) {
      return;
    }
    if (equipoSelect.value === '') {
      setEquipmentAction('crear', false, true);
      return;
    }
    const changed = isChanged(currentEquipmentValues(), equipmentBaseline);
    if (!changed) {
      setEquipmentAction('reutilizar', false, true);
      return;
    }
    const decision = selectedEquipmentDecision();
    setEquipmentAction(decision || 'pendiente', true, false);
  }

  function setEquipmentType(value) {
    const selectedType = value || 'celular';
    const radio = newEquipment.querySelector('input[name="tipo"][value="' + selectedType + '"]');
    const fallback = newEquipment.querySelector('input[name="tipo"][value="otro"]');
    if (radio) {
      radio.checked = true;
    } else if (fallback) {
      fallback.checked = true;
    }
  }

  function setPatternValue(value) {
    const pattern = newEquipment.querySelector('[data-pattern-lock]');
    if (!pattern) {
      return;
    }

    pattern.dispatchEvent(new CustomEvent('pattern-lock:set-value', {
      bubbles: true,
      detail: { value: value || '' }
    }));
  }

  function resetClientBaselineAndAction() {
    captureClientBaseline();
    setClientAction(clienteSelect.value === '' ? 'crear' : 'reutilizar', clienteSelect.value === '');
  }

  function resetEquipmentBaselineAndAction() {
    captureEquipmentBaseline();
    setEquipmentAction(equipoSelect.value === '' ? 'crear' : 'reutilizar', false, true);
  }

  function filterOptions(select, term, predicate) {
    // Filtro local: evita recargar pagina mientras el usuario busca clientes o
    // equipos ya cargados en la vista.
    const normalizedTerm = normalize(term);
    Array.from(select.options).forEach((option, index) => {
      if (index === 0) {
        option.hidden = false;
        return;
      }

      const searchable = normalize(option.dataset.search || option.textContent);
      option.hidden = (normalizedTerm !== '' && !searchable.includes(normalizedTerm)) || (predicate && !predicate(option));
    });
    refreshEntityCombo(select);
  }

  function syncEntityInput(select) {
    const combo = entityCombos.get(select);
    if (!combo) {
      return;
    }

    const option = select.selectedOptions[0];
    combo.input.value = option && option.value ? option.textContent.trim() : '';
  }

  function refreshEntityCombo(select) {
    const combo = entityCombos.get(select);
    if (!combo || combo.menu.classList.contains('d-none')) {
      return;
    }

    combo.render(combo.input.value);
  }

  function setupEntityCombos() {
    document.querySelectorAll('[data-entity-combo]').forEach((combo) => {
      const input = combo.querySelector('[data-entity-input]');
      const select = combo.querySelector('[data-entity-select]');
      const toggle = combo.querySelector('[data-entity-toggle]');
      const menu = combo.querySelector('[data-entity-menu]');

      if (!input || !select || !menu) {
        return;
      }

      function closeMenu() {
        menu.classList.add('d-none');
      }

      function choose(option) {
        select.value = option.value;
        input.value = option.value ? option.textContent.trim() : '';
        closeMenu();
        select.dispatchEvent(new Event('change', { bubbles: true }));
      }

      function render(term = '') {
        const normalizedTerm = normalize(term);
        const options = Array.from(select.options).filter((option, index) => {
          if (index === 0) {
            return normalizedTerm === '';
          }
          if (option.hidden) {
            return false;
          }
          const searchable = normalize(option.dataset.search || option.textContent);
          return normalizedTerm === '' || searchable.includes(normalizedTerm);
        });

        menu.innerHTML = '';

        if (options.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'entity-combo__empty';
          empty.textContent = 'Sin coincidencias.';
          menu.appendChild(empty);
        } else {
          options.forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'entity-combo__option';
            button.textContent = option.textContent.trim();
            if (option.value === '') {
              button.dataset.createNew = '1';
            }
            button.addEventListener('click', () => choose(option));
            menu.appendChild(button);
          });
        }

        menu.classList.remove('d-none');
      }

      entityCombos.set(select, { input, menu, render });

      input.addEventListener('focus', () => render(''));
      input.addEventListener('click', () => {
        if (menu.classList.contains('d-none')) {
          render('');
        }
      });
      input.addEventListener('input', () => render(input.value));
      input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          closeMenu();
        }
      });

      if (toggle) {
        toggle.addEventListener('click', () => {
          if (menu.classList.contains('d-none')) {
            input.focus();
            render('');
          } else {
            closeMenu();
          }
        });
      }

      select.addEventListener('change', () => syncEntityInput(select));

      document.addEventListener('click', (event) => {
        if (!combo.contains(event.target)) {
          closeMenu();
        }
      });
    });
  }

  function syncClient() {
    // Si hay cliente existente, los campos quedan visibles y editables; si se
    // modifican, se activa la bandera para actualizar el registro original.
    syncingForm = true;
    const hasClient = clienteSelect.value !== '';
    setRequired(newClient, '[data-client-required]', !hasClient);

    if (existingClientNote) {
      existingClientNote.classList.toggle('d-none', !hasClient);
      existingClientNote.textContent = 'Se usara el cliente seleccionado. Si cambias telefono, WhatsApp u otro dato, se actualizara su ficha al guardar.';
    }

    if (hasClient) {
      const option = clienteSelect.selectedOptions[0];
      fillNamedFields(newClient, {
        nombre_completo: option.dataset.nombreCompleto,
        telefono: option.dataset.telefono,
        whatsapp: option.dataset.whatsapp,
        email: option.dataset.email,
        domicilio: option.dataset.domicilio,
        ciudad: option.dataset.ciudad,
        estado_cliente: option.dataset.estado,
        notas_cliente: option.dataset.notasCliente
      });
    } else {
      clearNamedFields(newClient, ['nombre_completo', 'telefono', 'whatsapp', 'email', 'domicilio', 'ciudad', 'estado_cliente', 'notas_cliente']);
    }

    resetClientBaselineAndAction();
    syncingForm = false;
    syncEquipment();
  }

  function syncEquipment() {
    // Mantiene el equipo filtrado por cliente seleccionado. Si el usuario elige
    // un equipo primero, tambien sincroniza su cliente propietario.
    syncingForm = true;
    const selectedClientId = clienteSelect.value;
    const hasClient = selectedClientId !== '';
    const selectedEquipment = equipoSelect.selectedOptions[0];
    const equipmentClientId = selectedEquipment ? selectedEquipment.dataset.clienteId : '';

    filterOptions(equipoSelect, '', (option) => {
      return !hasClient || option.dataset.clienteId === selectedClientId;
    });

    if (hasClient && equipoSelect.value !== '' && equipmentClientId !== selectedClientId) {
      equipoSelect.value = '';
    }

    if (!hasClient && equipoSelect.value !== '' && equipmentClientId) {
      clienteSelect.value = equipmentClientId;
      syncEntityInput(clienteSelect);
      syncingForm = false;
      syncClient();
      return;
    }

    const hasEquipment = equipoSelect.value !== '';
    setRequired(newEquipment, '[data-equipment-required]', !hasEquipment);

    if (existingEquipmentNote) {
      existingEquipmentNote.classList.toggle('d-none', !hasEquipment);
      existingEquipmentNote.textContent = 'Se usara el equipo seleccionado. Puedes actualizar marca, modelo, tipo, patron/clave y condiciones al guardar.';
    }

    if (hasEquipment && selectedEquipment) {
      setEquipmentType(selectedEquipment.dataset.tipo);
      fillNamedFields(newEquipment, {
        marca: selectedEquipment.dataset.marca,
        modelo: selectedEquipment.dataset.modelo,
        numero_serie: selectedEquipment.dataset.numeroSerie,
        imei: selectedEquipment.dataset.imei,
        color: selectedEquipment.dataset.color,
        accesorios_recibidos: selectedEquipment.dataset.accesoriosRecibidos,
        estado_fisico: selectedEquipment.dataset.estadoFisico,
        observaciones_equipo: selectedEquipment.dataset.observaciones
      });
      setPatternValue(selectedEquipment.dataset.passwordEquipo);
    } else {
      setEquipmentType('celular');
      clearNamedFields(newEquipment, ['marca', 'modelo', 'numero_serie', 'imei', 'color', 'accesorios_recibidos', 'estado_fisico', 'observaciones_equipo']);
      setPatternValue('');
    }

    setRadioEnabled(newEquipment, true);
    resetEquipmentBaselineAndAction();
    syncingForm = false;
  }

  setupEntityCombos();

  clienteSelect.addEventListener('change', syncClient);
  equipoSelect.addEventListener('change', syncEquipment);

  newClient.querySelectorAll('input, textarea').forEach((field) => {
    if (field.type === 'hidden' || field === clientUpdateCheck) {
      return;
    }
    field.addEventListener('input', updateClientActionFromChanges);
    field.addEventListener('change', updateClientActionFromChanges);
  });

  if (clientUpdateCheck) {
    clientUpdateCheck.addEventListener('change', () => {
      if (clienteSelect.value === '') {
        setClientAction('crear', true);
      } else {
        setClientAction(clientUpdateCheck.checked ? 'actualizar' : 'reutilizar', clientUpdateCheck.checked);
      }
    });
  }

  newEquipment.querySelectorAll('input, textarea').forEach((field) => {
    if ((field.type === 'hidden' && field.name !== 'password_equipo') || field.dataset.equipmentDecisionOption !== undefined) {
      return;
    }
    field.addEventListener('input', updateEquipmentActionFromChanges);
    field.addEventListener('change', updateEquipmentActionFromChanges);
  });

  const patternLock = newEquipment.querySelector('[data-pattern-lock]');
  if (patternLock) {
    ['click', 'pointerup', 'keyup'].forEach((eventName) => {
      patternLock.addEventListener(eventName, () => {
        window.setTimeout(updateEquipmentActionFromChanges, 0);
      });
    });
  }

  equipmentDecisionOptions.forEach((option) => {
    option.addEventListener('change', updateEquipmentActionFromChanges);
  });

  document.querySelectorAll('[data-service-combo]').forEach((combo) => {
    const input = combo.querySelector('[data-service-input]');
    const toggle = combo.querySelector('[data-service-toggle]');
    const menu = combo.querySelector('[data-service-menu]');
    const empty = combo.querySelector('[data-service-empty]');
    const options = Array.from(combo.querySelectorAll('[data-service-option]'));
    let frame = 0;

    if (!input || !menu || options.length === 0) {
      return;
    }

    function openMenu() {
      menu.classList.remove('d-none');
    }

    function closeMenu() {
      menu.classList.add('d-none');
    }

    function filterServices() {
      const term = normalize(input.value);
      let visible = 0;

      options.forEach((option) => {
        const matches = term === '' || normalize(option.dataset.serviceOption || option.textContent).includes(term);
        option.classList.toggle('d-none', !matches);
        if (matches) {
          visible += 1;
        }
      });

      if (empty) {
        empty.classList.toggle('d-none', visible > 0);
      }

      openMenu();
    }

    function scheduleFilter() {
      window.cancelAnimationFrame(frame);
      frame = window.requestAnimationFrame(filterServices);
    }

    input.addEventListener('focus', filterServices);
    input.addEventListener('input', scheduleFilter);

    if (toggle) {
      toggle.addEventListener('click', () => {
        if (menu.classList.contains('d-none')) {
          filterServices();
          input.focus();
        } else {
          closeMenu();
        }
      });
    }

    options.forEach((option) => {
      option.addEventListener('click', () => {
        input.value = option.dataset.serviceOption || option.textContent.trim();
        closeMenu();
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });

    input.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeMenu();
      }
    });

    document.addEventListener('click', (event) => {
      if (!combo.contains(event.target)) {
        closeMenu();
      }
    });
  });

  syncClient();
})();
