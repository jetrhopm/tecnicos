(function () {
  /*
   * Alta rapida de orden:
   * - Lee selecciones/campos del formulario en el navegador.
   * - Muestra u oculta bloques de cliente/equipo nuevo para agilizar captura.
   * - No guarda nada ni decide permisos; al enviar, PHP vuelve a validar todo.
   */
  const clienteSearch = document.getElementById('cliente_search');
  const clienteSelect = document.getElementById('cliente_id');
  const equipoSearch = document.getElementById('equipo_search');
  const equipoSelect = document.getElementById('equipo_id');
  const newClient = document.querySelector('[data-new-client]');
  const newEquipment = document.querySelector('[data-new-equipment]');
  const existingClientNote = document.querySelector('[data-existing-client-note]');
  const existingEquipmentNote = document.querySelector('[data-existing-equipment-note]');

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
  }

  function syncClient() {
    // Si hay cliente existente, se desactiva el bloque de alta nueva para que
    // no viajen campos duplicados al backend.
    const hasClient = clienteSelect.value !== '';
    newClient.classList.toggle('d-none', hasClient);
    existingClientNote.classList.toggle('d-none', !hasClient);
    setContainerEnabled(newClient, !hasClient);
    setRequired(newClient, '[data-client-required]', !hasClient);
    syncEquipment();
  }

  function syncEquipment() {
    // Mantiene el equipo filtrado por cliente seleccionado. Si el usuario elige
    // un equipo primero, tambien sincroniza su cliente propietario.
    const selectedClientId = clienteSelect.value;
    const hasClient = selectedClientId !== '';
    const selectedEquipment = equipoSelect.selectedOptions[0];
    const equipmentClientId = selectedEquipment ? selectedEquipment.dataset.clienteId : '';

    filterOptions(equipoSelect, equipoSearch ? equipoSearch.value : '', (option) => {
      return !hasClient || option.dataset.clienteId === selectedClientId;
    });

    if (hasClient && equipoSelect.value !== '' && equipmentClientId !== selectedClientId) {
      equipoSelect.value = '';
    }

    if (!hasClient && equipoSelect.value !== '' && equipmentClientId) {
      clienteSelect.value = equipmentClientId;
      syncClient();
      return;
    }

    const hasEquipment = equipoSelect.value !== '';
    newEquipment.classList.toggle('d-none', hasEquipment);
    existingEquipmentNote.classList.toggle('d-none', !hasEquipment);
    setContainerEnabled(newEquipment, !hasEquipment);
    setRequired(newEquipment, '[data-equipment-required]', !hasEquipment);
  }

  if (clienteSearch) {
    clienteSearch.addEventListener('input', () => {
      filterOptions(clienteSelect, clienteSearch.value);
    });
  }

  if (equipoSearch) {
    equipoSearch.addEventListener('input', syncEquipment);
  }

  clienteSelect.addEventListener('change', syncClient);
  equipoSelect.addEventListener('change', syncEquipment);

  syncClient();
})();
