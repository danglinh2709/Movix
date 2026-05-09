/**
 * Toast notification system — lightweight standalone.
 * Provides window.MU_TOAST API.
 */
(function () {
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function createRoot() {
    const el = document.createElement('div');
    el.className = 'mu-toast-root';
    document.body.appendChild(el);
    return el;
  }

  function dismiss(el) {
    if (!el || !el.parentNode) return;
    el.classList.add('is-leaving');
    setTimeout(() => { if (el.parentNode) el.parentNode.removeChild(el); }, 280);
  }

  window.MU_TOAST = {
    success(msg, title) { this._show('success', title || '', msg || ''); },
    error(msg, title)    { this._show('error',   title || '', msg || ''); },
    warning(msg, title)  { this._show('warning', title || '', msg || ''); },
    info(msg, title)    { this._show('info',     title || '', msg || ''); },
    _show(type, title, msg) {
      const root = document.querySelector('.mu-toast-root') || createRoot();
      const toast = document.createElement('div');
      toast.className = 'mu-toast mu-toast--' + type;
      toast.innerHTML =
        '<div class="mu-toast__bar"></div>' +
        '<div class="mu-toast__body">' +
        (title ? '<div class="mu-toast__title">' + escapeHtml(title) + '</div>' : '') +
        (msg   ? '<div class="mu-toast__msg">'    + escapeHtml(msg)   + '</div>' : '') +
        '</div>' +
        '<button class="mu-toast__close" type="button" aria-label="Close">&times;</button>';
      toast.querySelector('.mu-toast__close').addEventListener('click', () => dismiss(toast));
      root.appendChild(toast);
      setTimeout(() => dismiss(toast), 4000);
    }
  };
})();
