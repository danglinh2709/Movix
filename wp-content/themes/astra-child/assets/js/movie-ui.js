/**
 * ============================================================
 * Movie UI JS (Netflix/MotPhim-style) for Astra Child
 * File: assets/js/movie-ui.js
 * ============================================================
 */

(function () {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const store = {
    get(key, fallback) {
      try {
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
      } catch {
        return fallback;
      }
    },
    set(key, val) {
      try {
        localStorage.setItem(key, JSON.stringify(val));
      } catch {
        /* ignore */
      }
    },
  };

  function debounce(fn, wait) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  // Sticky header: transparent -> solid
  const header = document.querySelector("[data-mu-header]");
  if (header) {
    const onScroll = () =>
      header.classList.toggle("is-solid", window.scrollY > 16);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  // Mobile drawer
  const drawer = document.querySelector("[data-mu-drawer]");
  const openDrawer = document.querySelector("[data-mu-open-drawer]");
  const closeDrawerEls = $$("[data-mu-close-drawer]");
  function setDrawerOpen(open) {
    if (!drawer) return;
    drawer.classList.toggle("is-open", open);
    document.documentElement.style.overflow = open ? "hidden" : "";
  }
  if (openDrawer)
    openDrawer.addEventListener("click", () => setDrawerOpen(true));
  closeDrawerEls.forEach((el) =>
    el.addEventListener("click", () => setDrawerOpen(false)),
  );
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setDrawerOpen(false);
  });

  // Cinematic Trailer Modal
  const trailerModal = document.createElement("div");
  trailerModal.className = "mu-modal mu-trailer-modal";
  trailerModal.innerHTML = `
    <div class="mu-modal__backdrop"></div>
    <div class="mu-modal__content">
      <button class="mu-modal__close" aria-label="Close">&times;</button>
      <div class="mu-modal__video"></div>
    </div>
  `;
  document.body.appendChild(trailerModal);

  async function openTrailer(id, embedUrl = null) {
    trailerModal.classList.add("is-visible");
    document.body.style.overflow = "hidden";
    const videoArea = trailerModal.querySelector(".mu-modal__video");
    videoArea.innerHTML = '<div class="mu-hc-loading"></div>';

    if (embedUrl) {
      videoArea.innerHTML = `<iframe src="${embedUrl}" frameborder="0" allowfullscreen allow="autoplay"></iframe>`;
    } else {
      try {
        const res = await fetch(
          `${
            (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php"
          }?action=mu_get_movie_preview&post_id=${id}`,
        );
        const json = await res.json();
        if (json.success && json.data.trailer) {
          videoArea.innerHTML = `<iframe src="${json.data.trailer}" frameborder="0" allowfullscreen allow="autoplay"></iframe>`;
        } else {
          videoArea.innerHTML =
            '<div class="mu-modal__error">Trailer unavailable.</div>';
        }
      } catch (e) {
        videoArea.innerHTML =
          '<div class="mu-modal__error">Error loading trailer.</div>';
      }
    }
  }

  const closeTrailer = () => {
    trailerModal.classList.remove("is-visible");
    document.body.style.overflow = "";
    trailerModal.querySelector(".mu-modal__video").innerHTML = "";
  };

  trailerModal.querySelector(".mu-modal__close").onclick = closeTrailer;
  trailerModal.querySelector(".mu-modal__backdrop").onclick = closeTrailer;

  document.addEventListener("click", (e) => {
    const playBtn = e.target.closest("[data-mu-smart-play]");
    if (playBtn) {
      e.preventDefault();
      const id = playBtn.dataset.id;
      const trailer = playBtn.dataset.trailer;
      openTrailer(id, trailer);
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeTrailer();
  });

  // Swiper init
  function initSwipers() {
    if (typeof Swiper === "undefined") return;
    const initOne = (el) => {
      if (el.__muInited) return;
      el.__muInited = true;
      const row = el.closest(".mu-row");
      const prev = row ? row.querySelector(".mu-navbtn--prev") : null;
      const next = row ? row.querySelector(".mu-navbtn--next") : null;
      // eslint-disable-next-line no-new
      const s = new Swiper(el, {
        slidesPerView: "auto",
        spaceBetween: 14,
        speed: 650,
        grabCursor: true,
        freeMode: false, // Better for premium arrow paging
        navigation: {
          prevEl: prev,
          nextEl: next,
          disabledClass: "is-disabled",
        },
        breakpoints: {
          0: { spaceBetween: 12 },
          720: { spaceBetween: 14 },
        },
      });
      // Show/hide arrows based on progress
      s.on("fromEdge", () => row.classList.add("is-paged"));
      s.on("reachBeginning", () => row.classList.remove("is-paged"));
    };

    const rows = $$('[data-mu-swiper="row"]');
    if ("IntersectionObserver" in window) {
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((en) => {
            if (en.isIntersecting) {
              initOne(en.target);
              io.unobserve(en.target);
            }
          });
        },
        { rootMargin: "260px 0px" },
      );
      rows.forEach((el) => io.observe(el));
    } else {
      rows.forEach(initOne);
    }

    // Hero slider (optional)
    const hero = document.querySelector('[data-mu-swiper="hero"]');
    if (hero && !hero.__muInitedHero) {
      hero.__muInitedHero = true;
      // eslint-disable-next-line no-new
      new Swiper(hero, {
        effect: "fade",
        fadeEffect: { crossFade: true },
        speed: 900,
        autoplay: { delay: 4500, disableOnInteraction: false },
        loop: true,
        pagination: { el: "[data-mu-hero-dots]", clickable: true },
      });
    }
  }
  initSwipers();

  // Touch-friendly card overlay (tap to open)
  document.addEventListener("click", (e) => {
    const card = e.target.closest(".mu-card");
    if (!card) return;
    const isAction = e.target.closest(".mu-btn, a, button");
    if (isAction) return;
    if (window.matchMedia("(max-width: 860px)").matches) {
      e.preventDefault();
      const already = card.classList.contains("is-open");
      $$(".mu-card.is-open").forEach((c) => c.classList.remove("is-open"));
      if (!already) card.classList.add("is-open");
    }
  });
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".mu-card"))
      $$(".mu-card.is-open").forEach((c) => c.classList.remove("is-open"));
  });

  // Favorites (localStorage for guests; DB for logged-in if AJAX exists)
  const KEY_FAVS = "mu_favorites";
  const KEY_FAVS_SYNCED = "mu_favorites_synced_v1";
  function getFavs() {
    return new Set(store.get(KEY_FAVS, []));
  }
  function setFavs(set) {
    store.set(KEY_FAVS, Array.from(set));
  }
  function syncFavButtons() {
    const favs = getFavs();
    $$(".mu-fav").forEach((btn) => {
      const id = btn.getAttribute("data-id");
      btn.classList.toggle("is-on", id && favs.has(String(id)));
    });
  }
  syncFavButtons();

  // Sync guest favorites -> DB after login (once)
  async function syncFavoritesToDbOnce() {
    if (!(window.MOVIE_UI && MOVIE_UI.isLoggedIn)) return;
    if (store.get(KEY_FAVS_SYNCED, false)) return;
    const favs = Array.from(getFavs());
    if (!favs.length) {
      store.set(KEY_FAVS_SYNCED, true);
      return;
    }
    const fd = new FormData();
    fd.append("action", "movie_ui_sync_favorites");
    fd.append("nonce", (window.MOVIE_UI && MOVIE_UI.nonce) || "");
    favs.forEach((id) => fd.append("ids[]", id));
    try {
      const res = await fetch(
        (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
        {
          method: "POST",
          body: fd,
          credentials: "same-origin",
        },
      );
      const data = await res.json();
      if (data && data.success) store.set(KEY_FAVS_SYNCED, true);
    } catch {
      // ignore, will retry next load
    }
  }
  syncFavoritesToDbOnce();

  async function toggleFavoriteServer(movieId) {
    const body = new URLSearchParams();
    body.set("action", "toggle_favorite"); // your existing handler
    body.set("movie_id", movieId);
    body.set("nonce", (window.MOVIE_UI && MOVIE_UI.favNonce) || "");
    const res = await fetch(
      (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
      {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body.toString(),
        credentials: "same-origin",
      },
    );
    const data = await res.json();
    if (data && data.success && data.data && data.data.status)
      return data.data.status;
    return null;
  }

  document.addEventListener("click", async (e) => {
    const btn = e.target.closest(".mu-fav");
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const movieId = btn.getAttribute("data-id");
    if (!movieId) return;

    btn.disabled = true;
    btn.style.opacity = "0.65";

    try {
      if (window.MOVIE_UI && MOVIE_UI.isLoggedIn) {
        const status = await toggleFavoriteServer(movieId);
        // We still mirror into localStorage for UI consistency across pages
        const favs = getFavs();
        if (status === "added") favs.add(String(movieId));
        if (status === "removed") favs.delete(String(movieId));
        setFavs(favs);
      } else {
        const favs = getFavs();
        if (favs.has(String(movieId))) favs.delete(String(movieId));
        else favs.add(String(movieId));
        setFavs(favs);
      }
      syncFavButtons();
    } finally {
      btn.disabled = false;
      btn.style.opacity = "";
    }
  });

  // Continue watching progress (localStorage)
  // Saved as: { [postId]: { t: seconds, d: durationSeconds, updatedAt } }
  const KEY_PROGRESS = "mu_progress";
  function setProgress(id, t, d) {
    const all = store.get(KEY_PROGRESS, {});
    all[id] = {
      t: Math.max(0, Math.floor(t || 0)),
      d: Math.max(0, Math.floor(d || 0)),
      updatedAt: Date.now(),
    };
    store.set(KEY_PROGRESS, all);
  }
  function getProgress() {
    return store.get(KEY_PROGRESS, {});
  }
  // Update progress bars in UI cards (if present)
  function renderProgressBars() {
    const all = getProgress();
    $$("[data-mu-progress]").forEach((el) => {
      const id = el.getAttribute("data-mu-progress");
      const p = all[id];
      if (!p || !p.d) return;
      const pct = Math.max(0, Math.min(100, Math.round((p.t / p.d) * 100)));
      el.style.width = `${pct}%`;
    });
  }
  renderProgressBars();

  // Player auto-hide UI + next episode behavior
  const player = document.querySelector("[data-mu-player]");
  if (player) {
    let t;
    const showUI = () => {
      player.classList.remove("is-hide-ui");
      clearTimeout(t);
      t = setTimeout(() => player.classList.add("is-hide-ui"), 2200);
    };
    showUI();
    player.addEventListener("mousemove", showUI, { passive: true });
    player.addEventListener("touchstart", showUI, { passive: true });

    const video = document.querySelector("[data-mu-video]");
    const postId = player.getAttribute("data-post-id");
    if (video && postId) {
      // Resume playback
      const all = getProgress();
      const saved = all[postId];
      const url = new URL(window.location.href);
      const forceStart = url.searchParams.get("start") === "1";
      if (saved && saved.t && !forceStart) {
        video.addEventListener(
          "loadedmetadata",
          () => {
            try {
              if (saved.t < (video.duration || 0) - 8)
                video.currentTime = saved.t;
            } catch {
              /* ignore */
            }
          },
          { once: true },
        );
      }

      // Save local progress frequently (every few seconds)
      let lastSent = 0;
      const tick = () => {
        const now = Date.now();
        if (now - lastSent < 3000) return;
        lastSent = now;
        const d = video.duration || 0;
        const tcur = video.currentTime || 0;
        setProgress(postId, tcur, d);
        renderProgressBars();

        // Save to DB if logged in
        if (window.MOVIE_UI && MOVIE_UI.isLoggedIn) {
          const pct = d ? Math.round((tcur / d) * 100) : 0;
          const body = new URLSearchParams();
          body.set("action", "movie_ui_save_progress");
          body.set("nonce", (window.MOVIE_UI && MOVIE_UI.progressNonce) || "");
          body.set("post_id", postId);
          body.set("t", String(Math.floor(tcur)));
          body.set("d", String(Math.floor(d)));
          body.set("p", String(Math.max(0, Math.min(100, pct))));
          fetch(
            (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
            {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: body.toString(),
              credentials: "same-origin",
              keepalive: true,
            },
          ).catch(() => {});
        }
      };

      video.addEventListener("timeupdate", tick);
      video.addEventListener("loadedmetadata", () =>
        setProgress(postId, video.currentTime, video.duration || 0),
      );
      video.addEventListener("ended", () =>
        setProgress(postId, video.duration || 0, video.duration || 0),
      );

      // Next episode countdown overlay (UI)
      const nextHref = player.getAttribute("data-next-href");
      if (nextHref) {
        const overlay = document.createElement("div");
        overlay.style.position = "absolute";
        overlay.style.right = "18px";
        overlay.style.bottom = "18px";
        overlay.style.padding = "12px 14px";
        overlay.style.borderRadius = "16px";
        overlay.style.background = "rgba(10,10,10,.60)";
        overlay.style.border = "1px solid rgba(255,255,255,.10)";
        overlay.style.backdropFilter = "blur(16px) saturate(140%)";
        overlay.style.display = "none";
        overlay.innerHTML = `<div style="font-weight:900;margin-bottom:6px;">Up Next</div>
          <div class="mu-muted" style="font-size:12px;margin-bottom:10px;">Next episode in <span data-mu-count>10</span>s</div>
          <div style="display:flex;gap:10px;">
            <a class="mu-btn mu-btn--primary" href="${nextHref}">Play Next</a>
            <button class="mu-btn mu-btn--ghost" type="button" data-mu-cancel>Cancel</button>
          </div>`;
        player.appendChild(overlay);
        const countEl = overlay.querySelector("[data-mu-count]");
        const cancel = overlay.querySelector("[data-mu-cancel]");
        let timer = null;
        let left = 10;
        const stop = () => {
          overlay.style.display = "none";
          if (timer) clearInterval(timer);
          timer = null;
        };
        cancel.addEventListener("click", stop);
        video.addEventListener("ended", () => {
          left = 10;
          if (countEl) countEl.textContent = String(left);
          overlay.style.display = "block";
          timer = setInterval(() => {
            left -= 1;
            if (countEl) countEl.textContent = String(left);
            if (left <= 0) window.location.href = nextHref;
          }, 1000);
        });
      }
    }

    // Side panel toggle â€” works with any [data-mu-side-toggle] button
    const sidePanel = document.querySelector("[data-mu-side]");
    function setSideOpen(open) {
      if (!sidePanel) return;
      sidePanel.classList.toggle("is-open", open);
    }
    document.addEventListener("click", (e) => {
      if (e.target.closest("[data-mu-side-toggle]")) {
        setSideOpen(!sidePanel?.classList.contains("is-open"));
      }
    });

    // Episode sidebar: seasons + episodes via AJAX
    const seasonSel = document.querySelector("[data-mu-season-select]");
    const epList = document.querySelector("[data-mu-episode-list]");
    async function fetchSeasons(tvShowId) {
      const fd = new FormData();
      fd.append("action", "movie_ui_get_seasons");
      fd.append("nonce", (window.MOVIE_UI && MOVIE_UI.nonce) || "");
      fd.append("tv_show_id", tvShowId);
      const res = await fetch(
        (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
        { method: "POST", body: fd, credentials: "same-origin" },
      );
      return res.json();
    }
    async function fetchEpisodes(tvShowId, season, currentId) {
      const fd = new FormData();
      fd.append("action", "movie_ui_get_episodes");
      fd.append("nonce", (window.MOVIE_UI && MOVIE_UI.nonce) || "");
      fd.append("tv_show_id", tvShowId);
      fd.append("season", String(season));
      fd.append("current_id", String(currentId || "0"));
      const res = await fetch(
        (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
        { method: "POST", body: fd, credentials: "same-origin" },
      );
      return res.json();
    }
    function scrollToCurrent() {
      if (!epList) return;
      const cur = epList.querySelector(".mu-ep.is-current");
      if (cur) cur.scrollIntoView({ block: "nearest" });
    }
    async function initEpisodeSidebar() {
      if (!seasonSel || !epList) return;
      const tvShowId = seasonSel.getAttribute("data-tv-show-id");
      const currentId = seasonSel.getAttribute("data-current-id");
      if (!tvShowId) return;

      try {
        const s = await fetchSeasons(tvShowId);
        if (s && s.success && s.data && Array.isArray(s.data.seasons)) {
          const current = seasonSel.value || "1";
          seasonSel.innerHTML = "";
          s.data.seasons.forEach((sn) => {
            const opt = document.createElement("option");
            opt.value = String(sn);
            opt.textContent = `Season ${sn}`;
            seasonSel.appendChild(opt);
          });
          seasonSel.value = current;
        }
      } catch {
        /* ignore */
      }

      const load = async () => {
        const season = seasonSel.value || "1";
        const resp = await fetchEpisodes(tvShowId, season, currentId);
        if (resp && resp.success && resp.data) {
          epList.innerHTML = resp.data.html || "";
          renderProgressBars();
          scrollToCurrent();
        }
      };
      seasonSel.addEventListener("change", load);
      await load();
      scrollToCurrent();
    }
    initEpisodeSidebar();

    // Keyboard shortcuts (basic)
    document.addEventListener("keydown", (e) => {
      if (!video) return;
      if (["INPUT", "TEXTAREA"].includes((e.target && e.target.tagName) || ""))
        return;
      if (e.key === " ") {
        e.preventDefault();
        if (video.paused) video.play();
        else video.pause();
      }
      if (e.key === "ArrowRight") video.currentTime += 10;
      if (e.key === "ArrowLeft") video.currentTime -= 10;
      if (e.key.toLowerCase() === "m") video.muted = !video.muted;
      if (e.key.toLowerCase() === "f") {
        if (document.fullscreenElement) document.exitFullscreen();
        else player.requestFullscreen && player.requestFullscreen();
      }
    });
  }

  // History page: remove/clear/load more (AJAX)
  const histRoot = document.querySelector("[data-mu-history]");
  if (histRoot) {
    const list = histRoot.querySelector("[data-mu-history-list]");
    const btnMore = histRoot.querySelector("[data-mu-history-more]");
    const input = histRoot.querySelector("[data-mu-history-q]");
    let offset = parseInt(histRoot.getAttribute("data-offset") || "0", 10) || 0;

    async function fetchMore(reset = false) {
      const q = input ? (input.value || "").trim() : "";
      const fd = new FormData();
      fd.append("action", "movie_ui_history_fetch");
      fd.append("nonce", (window.MOVIE_UI && MOVIE_UI.nonce) || "");
      fd.append("offset", String(reset ? 0 : offset));
      fd.append("limit", "24");
      fd.append("q", q);
      const res = await fetch(
        (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
        { method: "POST", body: fd, credentials: "same-origin" },
      );
      const data = await res.json();
      if (data && data.success && data.data) {
        if (reset) {
          list.innerHTML = "";
          offset = 0;
        }
        list.insertAdjacentHTML("beforeend", data.data.html || "");
        offset += data.data.count || 0;
        histRoot.setAttribute("data-offset", String(offset));
        if (btnMore)
          btnMore.style.display = (data.data.count || 0) < 24 ? "none" : "";
      }
    }

    if (btnMore) btnMore.addEventListener("click", () => fetchMore(false));
    if (input)
      input.addEventListener(
        "input",
        debounce(() => fetchMore(true), 320),
      );

    document.addEventListener("click", async (e) => {
      const rm = e.target.closest("[data-mu-history-remove]");
      if (rm) {
        const id = rm.getAttribute("data-mu-history-remove");
        const body = new URLSearchParams();
        body.set("action", "movie_ui_history_remove");
        body.set("nonce", (window.MOVIE_UI && MOVIE_UI.nonce) || "");
        body.set("post_id", id);
        await fetch(
          (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body.toString(),
            credentials: "same-origin",
          },
        );
        rm.closest("div")?.remove();
        window.MU_TOAST && MU_TOAST.info("Removed from history");
      }

      const clear = e.target.closest("[data-mu-history-clear]");
      if (clear) {
        const body = new URLSearchParams();
        body.set("action", "movie_ui_history_clear");
        body.set("nonce", (window.MOVIE_UI && MOVIE_UI.nonce) || "");
        await fetch(
          (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body.toString(),
            credentials: "same-origin",
          },
        );
        list.innerHTML = '<div class="mu-empty">Cleared.</div>';
        window.MU_TOAST && MU_TOAST.success("History cleared");
      }
    });

    // initial progressive load if empty list container exists
    if (list && list.children.length === 0) fetchMore(true);
  }

  // Search overlay + live results
  const search = document.querySelector("[data-mu-search]");
  const openSearch = document.querySelector("[data-mu-open-search]");
  const closeSearch = $$("[data-mu-close-search]");
  const input = document.querySelector("[data-mu-search-input]");
  const results = document.querySelector("[data-mu-search-results]");
  const skel = document.querySelector("[data-mu-skel]");
  const suggestWrap = document.querySelector("[data-mu-suggest]");

  function setSearchOpen(open) {
    if (!search) return;
    search.classList.toggle("is-open", open);
    document.documentElement.style.overflow = open ? "hidden" : "";
    if (open && input) setTimeout(() => input.focus(), 40);
  }
  if (openSearch)
    openSearch.addEventListener("click", () => setSearchOpen(true));
  closeSearch.forEach((el) =>
    el.addEventListener("click", () => setSearchOpen(false)),
  );
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setSearchOpen(false);
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") {
      e.preventDefault();
      setSearchOpen(true);
    }
  });

  async function runSearch(q) {
    if (!results) return;
    if (skel) skel.style.display = "grid";

    const fd = new FormData();
    fd.append("action", "movie_ui_search");
    fd.append("nonce", (window.MOVIE_UI && MOVIE_UI.nonce) || "");
    fd.append("q", q);

    const res = await fetch(
      (window.MOVIE_UI && MOVIE_UI.ajaxUrl) || "/wp-admin/admin-ajax.php",
      {
        method: "POST",
        body: fd,
        credentials: "same-origin",
      },
    );
    const data = await res.json();
    if (skel) skel.style.display = "none";
    if (data && data.success && data.data) {
      results.innerHTML = data.data.html || "";
      // Suggestions
      if (suggestWrap && Array.isArray(data.data.suggestions)) {
        suggestWrap.innerHTML = data.data.suggestions
          .map(
            (s) =>
              `<a class="mu-chip" href="${s.url}">${escapeHtml(s.title)}</a>`,
          )
          .join("");
      }
      syncFavButtons();
      renderProgressBars();
      initSwipers();
    } else {
      results.innerHTML = '<div class="mu-empty">Search failed.</div>';
    }
  }

  const onType = debounce(() => {
    if (!input) return;
    const q = (input.value || "").trim();
    if (q.length < 2) {
      if (results) results.innerHTML = "";
      return;
    }
    runSearch(q);
  }, 240);

  if (input) input.addEventListener("input", onType);

  function escapeHtml(s) {
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // Handle 'More Info' (i) button click to redirect to detail page
  document.addEventListener("click", (e) => {
    const infoBtn = e.target.closest("[data-mu-preview]");
    if (infoBtn) {
      e.preventDefault();
      e.stopPropagation();
      const card = infoBtn.closest(".mu-card");
      if (card && card.dataset.url) {
        window.location.href = card.dataset.url;
      }
    }
  });

  // ============================================================
  // TRAILER POPOVER (Triggered on click of "+" button)
  // ============================================================
  function ytEmbed(url) {
    if (!url) return "";
    const m = url.match(/(?:youtu\.be\/|[?&]v=)([^&\s]+)/);
    if (m)
      return (
        "https://www.youtube.com/embed/" +
        m[1] +
        "?autoplay=1&mute=1&loop=1&playlist=" +
        m[1] +
        "&controls=0&modestbranding=1&rel=0"
      );
    if (url.includes("youtube.com/embed/")) return url;
    return "";
  }

  let popover = document.getElementById("mu-trailer-popover");
  if (!popover) {
    popover = document.createElement("div");
    popover.id = "mu-trailer-popover";
    popover.setAttribute("role", "dialog");
    popover.innerHTML =
      '<div class="mu-hc__media" id="mu-tp-media"><div class="mu-hc__grad"></div></div><div class="mu-hc__body"><div class="mu-hc__meta" id="mu-tp-meta"></div><div class="mu-hc__title" id="mu-tp-title"></div><div class="mu-hc__overview" id="mu-tp-ov"></div><div class="mu-hc__actions" id="mu-tp-acts"></div></div>';
    document.body.appendChild(popover);
  }
  const tpMedia = document.getElementById("mu-tp-media");
  const tpMeta = document.getElementById("mu-tp-meta");
  const tpTitle = document.getElementById("mu-tp-title");
  const tpOv = document.getElementById("mu-tp-ov");
  const tpActs = document.getElementById("mu-tp-acts");

  function positionPopover(btnEl) {
    const cardEl = btnEl.closest(".mu-card");
    const r = cardEl.getBoundingClientRect();
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const W = Math.min(340, vw - 24);
    const H = 320;

    let left = r.left + (r.width - W) / 2;
    left = Math.max(8, Math.min(left, vw - W - 8));

    // Vertical: prefer below the card
    let top = r.bottom + 8;

    // If not enough space below, flip to above
    if (top + H > vh - 8) {
      top = r.top - H - 8;
    }

    // Final clamp to ensure it's visible in the viewport
    top = Math.max(60, Math.min(top, vh - H - 8));

    popover.style.left = left + "px";
    popover.style.top = top + "px";
    popover.style.width = W + "px";
  }

  function populatePopover(card) {
    const title = card.dataset.title || "";
    const meta = card.dataset.meta || "";
    const watch = card.dataset.watch || "#";
    const url = card.dataset.url || "#";
    const trailer = card.dataset.trailer || "";
    const backdrop = card.dataset.backdrop || "";
    const overview = card.dataset.overview || "";

    const embed = ytEmbed(trailer);
    tpMedia.innerHTML = '<div class="mu-hc__grad"></div>';
    if (embed) {
      const ifr = document.createElement("iframe");
      ifr.src = embed;
      ifr.allow = "autoplay; muted";
      tpMedia.style.backgroundImage = backdrop ? "url(" + backdrop + ")" : "";
      tpMedia.insertBefore(ifr, tpMedia.firstChild);
    } else if (backdrop) {
      tpMedia.style.backgroundImage = "url(" + backdrop + ")";
    } else {
      tpMedia.style.backgroundImage = "";
    }

    const parts = meta.split(" • ");
    tpMeta.innerHTML = parts
      .map((p) =>
        p.startsWith("★")
          ? '<span class="mu-hc__rating">' + escapeHtml(p) + "</span>"
          : "<span>" + escapeHtml(p) + "</span>",
      )
      .join('<span style="opacity:.25"> · </span>');

    tpTitle.textContent = title;
    tpOv.textContent = overview;
    tpOv.style.display = overview ? "" : "none";

    tpActs.innerHTML =
      '<a class="mu-btn mu-btn--primary" href="' +
      escapeHtml(watch) +
      '" style="flex:1;justify-content:center;"><span style="display:inline-block;width:0;height:0;border-top:5px solid transparent;border-bottom:5px solid transparent;border-left:8px solid #111;flex-shrink:0;"></span>Watch</a><a class="mu-btn mu-btn--ghost" href="' +
      escapeHtml(url) +
      '" title="More Info" style="padding:6px 10px;"><span style="display:inline-flex;width:16px;height:16px;border:1.5px solid #fff;border-radius:50%;align-items:center;justify-content:center;font-size:10px;font-weight:900;">i</span></a>';
  }

  function showPopover(btnEl) {
    const card = btnEl.closest(".mu-card");
    if (!card) return;
    populatePopover(card);
    positionPopover(btnEl);
    popover.classList.add("is-visible");
  }

  function hidePopover() {
    popover.classList.remove("is-visible");
    const ifr = tpMedia ? tpMedia.querySelector("iframe") : null;
    if (ifr) ifr.src = "";
  }

  document.addEventListener("click", (e) => {
    const trailerBtn = e.target.closest("[data-mu-trailer]");
    if (trailerBtn) {
      e.preventDefault();
      e.stopPropagation();
      if (popover.classList.contains("is-visible")) {
        hidePopover();
      } else {
        showPopover(trailerBtn);
      }
      return;
    }

    // Close popover if clicked outside
    if (popover.classList.contains("is-visible")) {
      if (!e.target.closest("#mu-trailer-popover")) {
        hidePopover();
      }
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && popover.classList.contains("is-visible")) {
      hidePopover();
    }
  });

  // ============================================================
  // ARCHIVE FILTERS (PJAX & SYNC)
  // ============================================================
  const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  };

  const sbForm = document.getElementById("mu-sidebar-form");
  const tbForm = document.getElementById("mu-topbar-form");

  if (sbForm && tbForm) {
    const updateParams = () => {
      const fd1 = new FormData(sbForm);
      const fd2 = new FormData(tbForm);
      const p = new URLSearchParams();

      // Merge, favoring topbar if both changed, but actually they will sync inputs
      for (const [k, v] of fd1.entries()) if (v) p.set(k, v);
      for (const [k, v] of fd2.entries()) if (v) p.set(k, v);

      return p.toString();
    };

    const syncForms = (sourceForm) => {
      const srcFd = new FormData(sourceForm);
      const targetForm = sourceForm === sbForm ? tbForm : sbForm;

      // Checkboxes/Radios
      targetForm
        .querySelectorAll('input[type="radio"], input[type="checkbox"]')
        .forEach((i) => {
          if (srcFd.has(i.name)) {
            i.checked = i.value === srcFd.get(i.name);
          } else {
            i.checked = false;
          }
        });
      // Selects
      targetForm.querySelectorAll("select").forEach((s) => {
        if (srcFd.has(s.name)) {
          s.value = srcFd.get(s.name);
        } else {
          s.value = "";
        }
      });
      // Inputs
      targetForm
        .querySelectorAll('input[type="text"], input[type="hidden"]')
        .forEach((i) => {
          if (
            i.name === "s" ||
            i.name === "sort" ||
            i.name === "genre" ||
            i.name === "year" ||
            i.name === "country" ||
            i.name === "rating"
          ) {
            if (srcFd.has(i.name)) {
              i.value = srcFd.get(i.name);
            } else if (i.type === "hidden") {
              i.value = "";
            }
          }
        });

      // Sync chips visual active state
      if (targetForm === tbForm || sourceForm === tbForm) {
        tbForm.querySelectorAll(".mu-chip").forEach((c) => {
          const input = c.querySelector("input");
          if (input) c.classList.toggle("is-active", input.checked);
        });
      }
    };

    const doFilter = debounce(async (sourceForm) => {
      syncForms(sourceForm);
      const qs = updateParams();
      const url = window.location.pathname + (qs ? "?" + qs : "");

      // Update URL
      window.history.pushState({ path: url }, "", url);

      // Visual loading state
      const mainArea = document.querySelector(".mu-movies-content-area");
      if (mainArea) mainArea.style.opacity = "0.5";

      try {
        const res = await fetch(url);
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, "text/html");

        const newArea = doc.querySelector(".mu-movies-content-area");
        if (newArea && mainArea) {
          mainArea.innerHTML = newArea.innerHTML;
          mainArea.style.opacity = "1";
          initSwipers(); // reinit any swipers if they appeared
          syncFavButtons();
          renderProgressBars();
        }

        // Show/hide reset buttons
        const isFiltered = qs.length > 0 && !qs.startsWith("sort=latest");
        document.querySelectorAll(".mu-sb-reset").forEach((r) => {
          r.style.display = isFiltered ? "" : "none";
        });
      } catch (err) {
        if (mainArea) mainArea.style.opacity = "1";
      }
    }, 300);

    // Listeners
    sbForm.addEventListener("change", () => doFilter(sbForm));
    tbForm.addEventListener("change", () => doFilter(tbForm));

    // Search input needs 'input' event (debounced via doFilter)
    sbForm.addEventListener("input", (e) => {
      if (e.target.type === "text") doFilter(sbForm);
    });
    tbForm.addEventListener("input", (e) => {
      if (e.target.type === "text") doFilter(tbForm);
    });

    // "Show more" toggles in sidebar
    document.addEventListener("click", (e) => {
      const moreBtn = e.target.closest("[data-mu-show-more]");
      if (moreBtn) {
        const targetId = moreBtn.getAttribute("data-mu-show-more");
        const list = document.getElementById("mu-list-" + targetId);
        if (list) {
          const hidden = list.querySelectorAll('[data-mu-hidden="true"]');
          hidden.forEach((h) => {
            h.style.display = h.style.display === "none" ? "flex" : "none";
          });
          const isExpanded = hidden[0] && hidden[0].style.display === "flex";
          moreBtn.innerHTML = isExpanded
            ? 'Show less <svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle; margin-left:4px; transform:rotate(180deg);"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : 'Show more <svg width="10" height="6" viewBox="0 0 10 6" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:inline-block; vertical-align:middle; margin-left:4px;"><path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }
      }

      const titleToggle = e.target.closest("[data-mu-toggle]");
      if (titleToggle) {
        const targetId = titleToggle.getAttribute("data-mu-toggle");
        const list = document.getElementById("mu-list-" + targetId);
        if (list) {
          const isCollapsed = list.style.display === "none";
          list.style.display = isCollapsed ? "flex" : "none";
          titleToggle.style.opacity = isCollapsed ? "1" : "0.6";
          // rotate caret
          titleToggle.style.setProperty(
            "--mu-rot",
            isCollapsed ? "45deg" : "-45deg",
          );
        }
      }
    });

    window.addEventListener("popstate", () => {
      window.location.reload();
    });
  }
})();
