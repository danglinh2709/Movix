(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function debounce(fn, wait) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  // Sticky transparent header -> solid on scroll
  const header = document.querySelector("[data-ms-header]");
  if (header) {
    const onScroll = () => {
      if (window.scrollY > 16) header.classList.add("is-solid");
      else header.classList.remove("is-solid");
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  // Swiper init (rows)
  function initSwipers() {
    if (typeof Swiper === "undefined") return;
    $$(".ms-swiper").forEach((el) => {
      if (el.__msInited) return;
      el.__msInited = true;
      const row = el.closest(".ms-row");
      const prev = row ? $(".ms-nav--prev", row) : null;
      const next = row ? $(".ms-nav--next", row) : null;
      // eslint-disable-next-line no-new
      new Swiper(el, {
        slidesPerView: "auto",
        spaceBetween: 14,
        speed: 650,
        grabCursor: true,
        freeMode: { enabled: true, sticky: false },
        navigation: prev && next ? { prevEl: prev, nextEl: next } : undefined,
        breakpoints: {
          0: { spaceBetween: 12 },
          720: { spaceBetween: 14 },
        },
      });
    });
  }
  initSwipers();

  // Touch-friendly card overlay (tap to open)
  document.addEventListener("click", (e) => {
    const card = e.target.closest(".ms-card");
    if (!card) return;
    // If user clicked a button inside overlay, allow it
    const isAction = e.target.closest(".ms-btn, a");
    if (isAction) return;

    // Toggle open state on touch devices / small screens
    if (window.matchMedia("(max-width: 860px)").matches) {
      e.preventDefault();
      const already = card.classList.contains("is-open");
      $$(".ms-card.is-open").forEach((c) => c.classList.remove("is-open"));
      if (!already) card.classList.add("is-open");
    }
  });
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".ms-card")) {
      $$(".ms-card.is-open").forEach((c) => c.classList.remove("is-open"));
    }
  });

  // Search modal (home header)
  const searchModal = document.querySelector("[data-ms-search-modal]");
  const openSearch = document.querySelector("[data-ms-open-search]");
  const closeSearchEls = $$("[data-ms-close-search]");
  const searchInput = document.querySelector("[data-ms-search-input]");
  const searchResults = document.querySelector("[data-ms-search-results]");
  const skeleton = document.querySelector("[data-ms-skeleton]");

  function setSearchOpen(open) {
    if (!searchModal) return;
    searchModal.classList.toggle("is-open", open);
    searchModal.setAttribute("aria-hidden", open ? "false" : "true");
    document.documentElement.style.overflow = open ? "hidden" : "";
    if (open && searchInput) setTimeout(() => searchInput.focus(), 40);
  }

  if (openSearch)
    openSearch.addEventListener("click", () => setSearchOpen(true));
  closeSearchEls.forEach((el) =>
    el.addEventListener("click", () => setSearchOpen(false)),
  );
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setSearchOpen(false);
  });

  async function runSearch(q) {
    if (!searchResults) return;
    if (skeleton) skeleton.style.display = "grid";

    const fd = new FormData();
    fd.append("action", "ms_search_movies");
    fd.append("nonce", (window.MS_UI && MS_UI.nonce) || "");
    fd.append("q", q);

    const res = await fetch(
      (window.MS_UI && MS_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
      {
        method: "POST",
        body: fd,
        credentials: "same-origin",
      },
    );
    const data = await res.json();
    if (skeleton) skeleton.style.display = "none";
    if (data && data.success && data.data) {
      searchResults.innerHTML =
        data.data.html || '<div class="ms-empty">No results.</div>';
      initSwipers(); // in case results include swipers later
    } else {
      searchResults.innerHTML = '<div class="ms-empty">Search failed.</div>';
    }
  }

  const onType = debounce(() => {
    if (!searchInput) return;
    const q = (searchInput.value || "").trim();
    if (q.length < 2) {
      if (skeleton) skeleton.style.display = "grid";
      if (searchResults) searchResults.innerHTML = "";
      return;
    }
    runSearch(q);
  }, 220);

  if (searchInput) searchInput.addEventListener("input", onType);

  // Search page button
  const runBtn = document.querySelector("[data-ms-run-search]");
  if (runBtn) {
    runBtn.addEventListener("click", () => {
      const q = (
        searchInput && searchInput.value ? searchInput.value : ""
      ).trim();
      if (q.length >= 2) runSearch(q);
    });
  }

  // Tabs (favorites/history)
  const tabsRoot = document.querySelector("[data-ms-tabs]");
  if (tabsRoot) {
    tabsRoot.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-ms-tab]");
      if (!btn) return;
      const key = btn.getAttribute("data-ms-tab");
      $$("[data-ms-tab]").forEach((b) =>
        b.classList.toggle("is-active", b === btn),
      );
      $$("[data-ms-pane]").forEach((p) =>
        p.classList.toggle("is-active", p.getAttribute("data-ms-pane") === key),
      );
    });
  }

  // Favorites toggle on cards (uses your existing toggle_favorite AJAX action)
  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".ms-fav-toggle");
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    if (!(window.MS_UI && MS_UI.isLoggedIn)) {
      // Soft-fail: send user to WP login
      window.location.href = "/wp-login.php";
      return;
    }

    const card = btn.closest(".ms-card");
    const movieId = card ? card.getAttribute("data-movie-id") : null;
    if (!movieId) return;

    btn.disabled = true;
    btn.style.opacity = "0.6";

    const body = new URLSearchParams();
    body.set("action", "toggle_favorite");
    body.set("movie_id", movieId);
    body.set("nonce", (window.MS_UI && MS_UI.favNonce) || "");

    try {
      const res = await fetch(
        (window.MS_UI && MS_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
        {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: body.toString(),
          credentials: "same-origin",
        },
      );
      const data = await res.json();
      const status =
        data && data.success && data.data ? data.data.status : null;
      if (status === "added") {
        btn.classList.add("is-on");
      } else if (status === "removed") {
        btn.classList.remove("is-on");
      }
    } finally {
      btn.disabled = false;
      btn.style.opacity = "";
    }
  });

  // Player: auto-hide top controls on inactivity
  const player = document.querySelector("[data-ms-player]");
  if (player) {
    let t;
    const show = () => {
      player.classList.remove("is-hidden-ui");
      clearTimeout(t);
      t = setTimeout(() => player.classList.add("is-hidden-ui"), 2200);
    };
    show();
    player.addEventListener("mousemove", show, { passive: true });
    player.addEventListener("touchstart", show, { passive: true });
    player.addEventListener("keydown", show);
  }
})();
