(function () {
  const instances = [];

  function normalize(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();
  }

  function debounce(fn, delay) {
    let timer = 0;
    return function (...args) {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => fn.apply(this, args), delay);
    };
  }

  function buildUrl(url, params) {
    const target = new URL(url, window.location.origin);
    Object.entries(params).forEach(([key, value]) => {
      if (value !== null && value !== undefined && String(value) !== '') {
        target.searchParams.set(key, String(value));
      }
    });
    return target.toString();
  }

  async function fetchItems(url) {
    const response = await fetch(url, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin'
    });
    const json = await response.json();
    if (!json.success) {
      return [];
    }
    return Array.isArray(json.data && json.data.items) ? json.data.items : [];
  }

  function renderMenu(menu, items, onChoose, emptyText) {
    menu.innerHTML = '';

    if (items.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'catalog-combo__empty';
      empty.textContent = emptyText;
      menu.appendChild(empty);
    } else {
      items.forEach((item) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'catalog-combo__option';
        button.textContent = item.nombre;
        if (item.marca) {
          const small = document.createElement('small');
          small.textContent = item.marca;
          button.appendChild(small);
        }
        button.addEventListener('click', () => onChoose(item));
        menu.appendChild(button);
      });
    }

    menu.classList.remove('d-none');
  }

  function close(menu) {
    menu.classList.add('d-none');
  }

  function setupCatalog(root) {
    const scope = root.closest('.row') || root.parentElement || document;
    const brandUrl = root.dataset.brandUrl;
    const modelUrl = root.dataset.modelUrl;
    const brandInput = root.querySelector('[data-brand-input]');
    const brandId = root.querySelector('[data-brand-id]');
    const brandMenu = root.querySelector('[data-brand-menu]');
    const modelInput = scope.querySelector('[data-model-input]');
    const modelId = scope.querySelector('[data-model-id]');
    const modelMenu = scope.querySelector('[data-model-menu]');

    if (!brandUrl || !modelUrl || !brandInput || !brandId || !brandMenu || !modelInput || !modelId || !modelMenu) {
      return;
    }

    async function searchBrands(openOnEmpty) {
      const q = brandInput.value.trim();
      if (!openOnEmpty && q.length < 1) {
        close(brandMenu);
        return;
      }
      const items = await fetchItems(buildUrl(brandUrl, { q }));
      renderMenu(brandMenu, items, (item) => {
        brandInput.value = item.nombre || '';
        brandId.value = item.id || '';
        modelId.value = '';
        close(brandMenu);
        modelInput.focus();
        if (modelInput.value.trim() !== '') {
          searchModels(true);
        }
        brandInput.dispatchEvent(new Event('change', { bubbles: true }));
      }, 'Sin coincidencias. Se guardara como marca nueva al enviar.');
    }

    async function searchModels(openOnEmpty) {
      const q = modelInput.value.trim();
      if (!openOnEmpty && q.length < 1) {
        close(modelMenu);
        return;
      }
      const items = await fetchItems(buildUrl(modelUrl, {
        marca_id: brandId.value,
        marca: brandInput.value,
        q
      }));
      renderMenu(modelMenu, items, (item) => {
        modelInput.value = item.nombre || '';
        modelId.value = item.id || '';
        if (item.marca_id && !brandId.value) {
          brandId.value = item.marca_id;
        }
        if (item.marca && brandInput.value.trim() === '') {
          brandInput.value = item.marca;
        }
        close(modelMenu);
        modelInput.dispatchEvent(new Event('change', { bubbles: true }));
      }, brandInput.value.trim() === '' ? 'Escribe primero una marca o deja el modelo libre.' : 'Sin coincidencias. Se guardara como modelo nuevo al enviar.');
    }

    brandInput.addEventListener('input', () => {
      brandId.value = '';
      modelId.value = '';
      debounceSearchBrands();
    });
    brandInput.addEventListener('focus', () => searchBrands(true));
    brandInput.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        close(brandMenu);
      }
    });

    modelInput.addEventListener('input', () => {
      modelId.value = '';
      debounceSearchModels();
    });
    modelInput.addEventListener('focus', () => searchModels(true));
    modelInput.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        close(modelMenu);
      }
    });

    const debounceSearchBrands = debounce(() => searchBrands(false), 180);
    const debounceSearchModels = debounce(() => searchModels(false), 180);

    instances.push({
      root,
      brandInput,
      brandId,
      modelInput,
      modelId,
      resetIds() {
        brandId.value = '';
        modelId.value = '';
      }
    });

    document.addEventListener('click', (event) => {
      if (!root.contains(event.target) && !modelMenu.parentElement.contains(event.target)) {
        close(brandMenu);
        close(modelMenu);
      }
    });
  }

  document.querySelectorAll('[data-equipo-catalogo]').forEach(setupCatalog);

  document.addEventListener('equipo-catalogo:limpiar-ids', (event) => {
    instances.forEach((instance) => {
      if (!event.target || instance.root.closest('form') === event.target.closest('form')) {
        instance.resetIds();
      }
    });
  });
})();
