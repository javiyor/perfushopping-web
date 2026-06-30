(function () {
  function applyTheme(theme) {
    var mode = theme === 'light' ? 'light' : 'dark';
    document.body.setAttribute('data-theme', mode);
    try { localStorage.setItem('perfushopping-theme', mode); } catch (e) {}
    document.querySelectorAll('[data-theme-choice]').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-theme-choice') === mode);
    });
  }

  try {
    applyTheme(localStorage.getItem('perfushopping-theme') || 'dark');
  } catch (e) {
    applyTheme('dark');
  }

  function onThumbClick(e) {
    var t = e.target;
    if (!t || !t.dataset || !t.dataset.mainTarget) return;
    var main = document.querySelector(t.dataset.mainTarget);
    if (!main) return;
    main.src = t.src;
  }

  document.addEventListener('click', function (e) {
    onThumbClick(e);
    var themeBtn = e.target && e.target.closest ? e.target.closest('[data-theme-choice]') : null;
    if (themeBtn) {
      e.preventDefault();
      applyTheme(themeBtn.getAttribute('data-theme-choice') || 'dark');
    }
  });

  function humanSize(bytes) {
    if (!bytes || bytes < 1024) return (bytes || 0) + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function renderPreview(input) {
    if (!input || !input.dataset || !input.dataset.previewContainer) return;
    var box = document.querySelector(input.dataset.previewContainer);
    if (!box) return;
    box.innerHTML = '';
    if (!input.files || !input.files.length) return;

    Array.prototype.forEach.call(input.files, function (file) {
      var item = document.createElement('div');
      item.className = 'admin-upload-chip';
      item.textContent = file.name + ' - ' + humanSize(file.size);
      box.appendChild(item);
    });
  }

  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t || !t.matches || !t.matches('input[type="file"][data-preview-container]')) return;
    renderPreview(t);
  });

  function setAiStatus(root, text, isError) {
    if (!root) return;
    root.textContent = text || '';
    root.classList.toggle('error', !!isError);
  }

  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('[data-ai-generate]') : null;
    if (!btn) return;
    e.preventDefault();

    var endpoint = btn.dataset.endpoint || '';
    var csrf = btn.dataset.csrf || '';
    var idprodu = btn.dataset.idprodu || '';
    var target = document.querySelector(btn.dataset.target || '');
    var status = btn.parentNode ? btn.parentNode.querySelector('[data-ai-status]') : null;
    if (!endpoint || !csrf || !idprodu || !target) return;

    btn.disabled = true;
    setAiStatus(status, 'Generando descripcion...', false);

    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ _csrf: csrf, idprodu: idprodu }).toString()
    })
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (!json || !json.ok) {
          throw new Error((json && json.error) || 'No se pudo generar la descripcion.');
        }
        target.value = json.description || '';
        setAiStatus(status, 'Descripcion generada. Revisa el texto antes de guardar.', false);
      })
      .catch(function (err) {
        setAiStatus(status, err.message || 'Error al generar descripcion.', true);
      })
      .finally(function () {
        btn.disabled = false;
      });
  });
})();
