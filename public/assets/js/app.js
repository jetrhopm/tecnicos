document.addEventListener('DOMContentLoaded', () => {
  /*
   * Comportamientos generales de interfaz:
   * - Busqueda asincrona local en tablas ya renderizadas.
   * - Menu lateral movil.
   * - Confirmaciones e impresion.
   * Estas ayudas no sustituyen validaciones del backend; solo mejoran uso.
   */
  function normalizeSearchText(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function setupTableSearch(tableWrap) {
    // Cada tabla obtiene su propio buscador. La informacion ya esta en HTML,
    // asi que se filtra en memoria sin pedir de nuevo a la base de datos.
    if (tableWrap.dataset.tableSearchReady === '1' || tableWrap.dataset.tableSearch === 'off') {
      return;
    }

    const table = tableWrap.querySelector('table');
    const tbody = table ? table.querySelector('tbody') : null;
    if (!table || !tbody) {
      return;
    }

    tableWrap.dataset.tableSearchReady = '1';

    const columnCount = table.querySelectorAll('thead th').length || table.querySelectorAll('tr:first-child > *').length || 1;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const builtInEmptyRows = rows.filter((row) => {
      const cells = row.querySelectorAll('td');
      return cells.length === 1 && Number(cells[0].getAttribute('colspan') || 1) >= columnCount;
    });
    const dataRows = rows.filter((row) => !builtInEmptyRows.includes(row));

    const emptyRow = document.createElement('tr');
    emptyRow.className = 'table-search-empty d-none';
    emptyRow.innerHTML = '<td class="text-center text-muted py-4" colspan="' + columnCount + '">Sin coincidencias en esta tabla.</td>';
    tbody.appendChild(emptyRow);

    dataRows.forEach((row) => {
      row.dataset.searchText = normalizeSearchText(row.textContent);
    });

    const sourceInputs = Array.from(document.querySelectorAll('[data-table-search-source]'));
    const tableCount = document.querySelectorAll('.table-wrap').length;
    let input = null;

    if (sourceInputs.length === 1 && tableCount === 1) {
      input = sourceInputs[0];
      input.setAttribute('autocomplete', 'off');
    } else {
      const searchBar = document.createElement('div');
      searchBar.className = 'table-searchbar';
      searchBar.innerHTML = '<label class="form-label small text-muted mb-1">Buscar en esta tabla</label><input class="form-control form-control-sm" type="search" autocomplete="off" placeholder="Escribe para filtrar por cualquier campo">';

      tableWrap.parentNode.insertBefore(searchBar, tableWrap);
      input = searchBar.querySelector('input');
    }

    let frame = 0;

    function applyFilter() {
      // El filtro compara texto normalizado para que acentos/mayusculas no
      // rompan la busqueda visual.
      const term = normalizeSearchText(input.value);
      let visibleRows = 0;

      dataRows.forEach((row) => {
        const matches = term === '' || row.dataset.searchText.includes(term);
        row.classList.toggle('d-none', !matches);
        if (matches) {
          visibleRows += 1;
        }
      });

      builtInEmptyRows.forEach((row) => {
        row.classList.toggle('d-none', term !== '' || dataRows.length > 0);
      });

      emptyRow.classList.toggle('d-none', visibleRows > 0 || (term === '' && builtInEmptyRows.length > 0));
    }

    input.addEventListener('input', () => {
      window.cancelAnimationFrame(frame);
      frame = window.requestAnimationFrame(applyFilter);
    });

    applyFilter();
  }

  document.querySelectorAll('.table-wrap').forEach(setupTableSearch);

  const sidebar = document.getElementById('app-sidebar');
  const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  const sidebarCloseElements = document.querySelectorAll('[data-sidebar-close]');

  function setSidebarOpen(open) {
    // Estado visual del menu. No guarda informacion sensible.
    document.body.classList.toggle('sidebar-open', open);
    if (sidebarToggle) {
      sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
  }

  if (sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      setSidebarOpen(!document.body.classList.contains('sidebar-open'));
    });

    sidebarCloseElements.forEach((element) => {
      element.addEventListener('click', () => setSidebarOpen(false));
    });

    sidebar.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => setSidebarOpen(false));
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setSidebarOpen(false);
      }
    });
  }

  document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('submit', (event) => {
      const message = element.getAttribute('data-confirm') || 'Confirmar accion';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });

  const bindQuotePartSelect = (select) => {
    if (select.dataset.quoteBound === '1') {
      return;
    }
    select.dataset.quoteBound = '1';
    select.addEventListener('change', () => {
      const scope = select.closest('[data-quote-row]') || select.closest('[data-quote-form]') || document;
      const description = scope.querySelector('[data-quote-description]');
      const price = scope.querySelector('[data-quote-price]');
      const type = scope.querySelector('[data-quote-type]');
      const option = select.selectedOptions[0];
      if (!option || !select.value) {
        return;
      }

      if (description) {
        description.value = option.dataset.description || description.value;
      }
      if (price) {
        price.value = option.dataset.price || price.value || '0';
      }
      if (type) {
        type.value = 'refaccion';
      }
    });
  };

  document.querySelectorAll('[data-quote-part-select]').forEach(bindQuotePartSelect);

  document.querySelectorAll('[data-quote-form]').forEach((form) => {
    const addButton = form.querySelector('[data-add-quote-item]');
    const itemsContainer = form.querySelector('[data-quote-items]');
    const template = form.querySelector('[data-quote-item-template]');
    let nextIndex = 1;

    const refreshRemoveButtons = () => {
      form.querySelectorAll('[data-remove-quote-item]').forEach((button) => {
        const row = button.closest('[data-quote-row]');
        button.disabled = !row;
      });
    };

    if (addButton && itemsContainer && template) {
      addButton.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        nextIndex += 1;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;
        if (!row) {
          return;
        }
        itemsContainer.appendChild(row);
        row.querySelectorAll('[data-quote-part-select]').forEach(bindQuotePartSelect);
        refreshRemoveButtons();
      });
    }

    form.addEventListener('click', (event) => {
      const button = event.target.closest('[data-remove-quote-item]');
      if (!button) {
        return;
      }
      const row = button.closest('[data-quote-row]');
      if (row) {
        row.remove();
      }
      refreshRemoveButtons();
    });

    refreshRemoveButtons();
  });

  const formatMoney = (value) => {
    try {
      return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
    } catch (error) {
      return '$' + Number(value || 0).toFixed(2);
    }
  };

  document.querySelectorAll('[data-pos-form]').forEach((form) => {
    const searchInput = form.querySelector('[data-pos-search]');
    const clearSearch = form.querySelector('[data-pos-clear-search]');
    const resultsBox = form.querySelector('[data-pos-results]');
    const cart = form.querySelector('[data-pos-cart]');
    const emptyRow = form.querySelector('[data-pos-empty]');
    const template = form.querySelector('[data-pos-row-template]');
    const totalInput = form.querySelector('[data-pos-total]');
    const discountInput = form.querySelector('[data-pos-discount]');
    const searchUrl = form.dataset.posSearchUrl || '';
    let nextIndex = 1;
    let searchTimer = null;

    const updateTotal = () => {
      let subtotal = 0;
      form.querySelectorAll('[data-pos-row]').forEach((row) => {
        const qty = Number(row.querySelector('[data-pos-qty]')?.value || 0);
        const price = Number(row.querySelector('[data-pos-price]')?.value || 0);
        const lineTotal = Math.max(0, qty) * Math.max(0, price);
        subtotal += lineTotal;
        const lineTotalCell = row.querySelector('[data-pos-line-total]');
        if (lineTotalCell) {
          lineTotalCell.textContent = formatMoney(lineTotal);
        }
      });
      const discount = Math.max(0, Number(discountInput?.value || 0));
      if (totalInput) {
        totalInput.value = formatMoney(Math.max(0, subtotal - discount));
      }
      if (emptyRow) {
        emptyRow.classList.toggle('d-none', form.querySelectorAll('[data-pos-row]').length > 0);
      }
    };

    const syncNames = () => {
      form.querySelectorAll('[data-pos-row]').forEach((row, index) => {
        row.querySelector('[data-pos-field="refaccion_id"]')?.setAttribute('name', `items[${index}][refaccion_id]`);
        row.querySelector('[data-pos-qty]')?.setAttribute('name', `items[${index}][cantidad]`);
        row.querySelector('[data-pos-price]')?.setAttribute('name', `items[${index}][precio_unitario]`);
      });
    };

    const addProduct = (product) => {
      if (!product || !template || !cart) {
        return;
      }

      const existing = form.querySelector(`[data-pos-row][data-refaccion-id="${product.id}"]`);
      if (existing) {
        const qtyInput = existing.querySelector('[data-pos-qty]');
        if (qtyInput) {
          qtyInput.value = String(Math.max(1, Number(qtyInput.value || 0) + 1));
        }
        updateTotal();
        syncNames();
        return;
      }

      const wrapper = document.createElement('tbody');
      wrapper.innerHTML = template.innerHTML.trim();
      const row = wrapper.firstElementChild;
      if (!row) {
        return;
      }

      row.dataset.refaccionId = String(product.id);
      row.querySelector('[data-pos-name]').textContent = product.nombre || 'Refaccion';
      row.querySelector('[data-pos-sku]').textContent = product.sku || '';
      row.querySelector('[data-pos-stock]').textContent = product.stock_actual ?? '0';
      row.querySelector('[data-pos-field="refaccion_id"]').value = product.id;
      row.querySelector('[data-pos-price]').value = product.precio_venta || '0';
      cart.appendChild(row);
      nextIndex += 1;
      syncNames();
      updateTotal();
    };

    const hideResults = () => {
      if (resultsBox) {
        resultsBox.classList.add('d-none');
        resultsBox.innerHTML = '';
      }
    };

    const renderResults = (items) => {
      if (!resultsBox) {
        return;
      }
      resultsBox.innerHTML = '';
      if (!items.length) {
        resultsBox.innerHTML = '<div class="pos-search__empty">Sin coincidencias.</div>';
        resultsBox.classList.remove('d-none');
        return;
      }
      items.forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'pos-search__item';
        const title = document.createElement('strong');
        title.textContent = item.nombre || 'Refaccion';
        const meta = document.createElement('small');
        meta.textContent = `${item.sku || ''} - Stock ${item.stock_actual ?? 0} - ${formatMoney(Number(item.precio_venta || 0))}`;
        button.append(title, meta);
        button.addEventListener('click', () => {
          addProduct(item);
          if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
          }
          hideResults();
        });
        resultsBox.appendChild(button);
      });
      resultsBox.classList.remove('d-none');
    };

    const searchProducts = async (immediate = false) => {
      const query = (searchInput?.value || '').trim();
      if (!query || query.length < 2 || !searchUrl) {
        hideResults();
        return;
      }
      try {
        const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
          headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        const data = payload.data || {};
        const items = Array.isArray(data.items) ? data.items : [];
        if ((data.exact || immediate) && items.length === 1 && String(items[0].sku || '').toLowerCase() === query.toLowerCase()) {
          addProduct(items[0]);
          searchInput.value = '';
          hideResults();
          return;
        }
        renderResults(items);
      } catch (error) {
        if (resultsBox) {
          resultsBox.innerHTML = '<div class="pos-search__empty">No se pudo buscar. Intenta de nuevo.</div>';
          resultsBox.classList.remove('d-none');
        }
      }
    };

    form.addEventListener('click', (event) => {
      const button = event.target.closest('[data-pos-remove-item]');
      if (!button) {
        return;
      }
      const rows = form.querySelectorAll('[data-pos-row]');
      const row = button.closest('[data-pos-row]');
      if (row && rows.length > 1) {
        row.remove();
      } else if (row) {
        row.remove();
      }
      syncNames();
      updateTotal();
    });

    form.addEventListener('input', (event) => {
      if (event.target.closest('[data-pos-search]')) {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => searchProducts(false), 220);
        return;
      }
      updateTotal();
      syncNames();
    });

    searchInput?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        window.clearTimeout(searchTimer);
        searchProducts(true);
      }
      if (event.key === 'Escape') {
        hideResults();
      }
    });

    clearSearch?.addEventListener('click', () => {
      if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
      }
      hideResults();
    });

    form.addEventListener('submit', () => {
      syncNames();
    });

    nextIndex += form.querySelectorAll('[data-pos-row]').length;
    syncNames();
    updateTotal();
  });

  if (window.bootstrap && window.bootstrap.Popover) {
    let activeHelpPopover = null;

    document.querySelectorAll('[data-help-popover]').forEach((element) => {
      const popover = new window.bootstrap.Popover(element, {
        trigger: 'manual',
        container: 'body',
        placement: element.getAttribute('data-help-placement') || 'auto',
      });

      element.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (activeHelpPopover && activeHelpPopover !== popover) {
          activeHelpPopover.hide();
        }

        popover.toggle();
        activeHelpPopover = popover;
      });
    });

    document.addEventListener('click', (event) => {
      if (!activeHelpPopover) {
        return;
      }

      const clickedHelp = event.target.closest('[data-help-popover]');
      const clickedPopover = event.target.closest('.popover');
      if (!clickedHelp && !clickedPopover) {
        activeHelpPopover.hide();
        activeHelpPopover = null;
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && activeHelpPopover) {
        activeHelpPopover.hide();
        activeHelpPopover = null;
      }
    });
  }
});
