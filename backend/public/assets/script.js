/**
 * NEXUS ADMIN DASHBOARD
 * Main JavaScript — Bootstrap 5 + Vanilla JS
 * ============================================
 */

(function () {
  'use strict';

  /* ── DOM Ready ───────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    initDarkMode();
    initSidebar();
    initToasts();
    initAnimations();
    initCharts();
    initTasks();
    initSearchShortcut();
    initTableActions();
  });

  /* ============================================
     DARK MODE
     ============================================ */
  function initDarkMode() {
    const html = document.documentElement;
    const toggleBtns = document.querySelectorAll('[data-theme-toggle]');
    const stored = localStorage.getItem('nexus-theme') || 'light';
    applyTheme(stored);

    toggleBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        const current = html.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem('nexus-theme', next);
      });
    });

    function applyTheme(theme) {
      html.setAttribute('data-theme', theme);
      document.querySelectorAll('.toggle-icon').forEach(icon => {
        icon.classList.remove('active');
      });
      document.querySelectorAll(`.toggle-${theme}`).forEach(icon => {
        icon.classList.add('active');
      });
    }
  }

  /* ============================================
     SIDEBAR COLLAPSE
     ============================================ */
  function initSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn   = document.getElementById('sidebarToggleBtn');
    const mobileToggle = document.getElementById('mobileSidebarToggle');

    if (!sidebar || !mainContent) return;

    const stored = localStorage.getItem('nexus-sidebar');
    if (stored === 'collapsed') setSidebarCollapsed(true);

    if (toggleBtn) {
      toggleBtn.addEventListener('click', function () {
        const isCollapsed = sidebar.classList.contains('collapsed');
        setSidebarCollapsed(!isCollapsed);
        localStorage.setItem('nexus-sidebar', !isCollapsed ? 'collapsed' : 'expanded');
      });
    }

    const currentPath = window.location.href;
    sidebar.querySelectorAll('.nav-link').forEach(link => {
      if (link.href && currentPath.includes(link.getAttribute('href'))) {
        link.classList.add('active');
      }
    });

    function setSidebarCollapsed(collapse) {
      sidebar.classList.toggle('collapsed', collapse);
      mainContent.classList.toggle('expanded', collapse);
    }
  }

  /* ============================================
     TOASTS
     ============================================ */
  function initToasts() {
    const welcomeToast = document.getElementById('welcomeToast');
    if (welcomeToast) {
      setTimeout(() => {
        const t = new bootstrap.Toast(welcomeToast, { delay: 4000 });
        t.show();
      }, 800);
    }

    document.querySelectorAll('[data-toast-trigger]').forEach(btn => {
      btn.addEventListener('click', function () {
        const target = document.getElementById(this.dataset.toastTrigger);
        if (target) {
          const t = new bootstrap.Toast(target, { delay: 3500 });
          t.show();
        }
      });
    });
  }

  /* ============================================
     ANIMATIONS — Intersection Observer
     ============================================ */
  function initAnimations() {
    if (!window.IntersectionObserver) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-in').forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(16px)';
      el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      observer.observe(el);
    });

    document.querySelectorAll('[data-count]').forEach(el => {
      const observer2 = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateCount(el);
            observer2.unobserve(el);
          }
        });
      });
      observer2.observe(el);
    });
  }

  function animateCount(el) {
    const target = parseFloat(el.dataset.count.replace(/,/g, ''));
    const suffix = el.dataset.suffix || '';
    const prefix = el.dataset.prefix || '';
    const duration = 1200;
    const steps = 60;
    const stepValue = target / steps;
    const isFloat = el.dataset.count.includes('.');
    let current = 0;
    let step = 0;

    const timer = setInterval(() => {
      step++;
      current = stepValue * step;
      if (step >= steps) {
        current = target;
        clearInterval(timer);
      }
      const display = isFloat ? current.toFixed(1) : Math.floor(current).toLocaleString();
      el.textContent = prefix + display + suffix;
    }, duration / steps);
  }

  /* ============================================
     CHARTS (Chart.js, konfigurasi dari <script data-chart>)
     ============================================
     Konfigurasi ditulis Blade ke dalam
     <script type="application/json" data-chart data-target="idCanvas">,
     lalu dibaca di sini. Tidak ada angka yang tertanam di JS lagi.

     Canvas dimuat ulang saat tema berganti karena Chart.js menyimpan warna
     yang sudah di-resolve di dalam instance-nya. */
  function initCharts() {
    const blok = document.querySelectorAll('script[type="application/json"][data-chart]');
    if (!blok.length) return;

    if (typeof Chart === 'undefined') {
      // CDN gagal atau diblokir. Angka tetap terbaca sebagai teks di markup,
      // jadi halaman tidak ikut rusak — chart memang hanya penghias.
      blok.forEach(b => {
        const canvas = document.getElementById(b.dataset.target);
        if (canvas) canvas.closest('.chart-box')?.classList.add('chart-gagal');
      });
      return;
    }

    window.__nexusCharts = [];

    blok.forEach(b => {
      let config;
      try {
        config = JSON.parse(b.textContent);
      } catch (e) {
        return; // konfigurasi rusak: biarkan angka teks yang jadi rujukan
      }

      const canvas = document.getElementById(b.dataset.target);
      if (!canvas) return;

      const c = getThemeColors();
      config.options = config.options || {};
      config.options.responsive = true;
      config.options.maintainAspectRatio = false;

      const grafik = new Chart(canvas.getContext('2d'), config);
      grafik.$warnaDasar = c;
      window.__nexusCharts.push(grafik);
    });
  }

  function getThemeColors() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    return {
      primary: '#4f46e5',
      secondary: '#7c3aed',
      success: '#22c55e',
      warning: '#f59e0b',
      danger: '#ef4444',
      info: '#3b82f6',
      grid: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
      text: '#94a3b8',
      surface: isDark ? '#111318' : '#ffffff',
    };
  }

  /** Opsi dasar yang dipakai semua chart, agar konsisten dan tidak diulang. */
  function opsiChart(c, extras) {
    return Object.assign({
      plugins: {
        legend: { display: false, labels: { color: c.text } },
        tooltip: { enabled: true },
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: c.text, maxRotation: 0, autoSkipPadding: 12 } },
        y: { beginAtZero: true, grid: { color: c.grid }, ticks: { color: c.text } },
      },
    }, extras || {});
  }

  /** Warna rgba dari hex, untuk background bar/donut. */
  function transparan(hex, alpha) {
    const n = parseInt(hex.slice(1), 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
  }

  /* Tema berganti -> chart perlu dibangun ulang. Chart.js menyimpan warna yang
     sudah di-resolve di dalam instance-nya, jadi update warna saja tidak cukup
     untuk grid, tick, dan legend. Instance lama dibuang supaya tidak menumpuk. */
  document.addEventListener('click', function(e) {
    if (!e.target.closest('[data-theme-toggle]')) return;

    setTimeout(function () {
      (window.__nexusCharts || []).forEach(grafik => grafik.destroy());
      initCharts();
    }, 350);
  });

  /* ============================================
     TASKS
     ============================================ */
  function initTasks() {
    document.querySelectorAll('.task-check').forEach(check => {
      check.addEventListener('click', function () {
        const isChecked = this.classList.contains('checked');
        this.classList.toggle('checked', !isChecked);
        this.innerHTML = !isChecked ? '<i class="fa-solid fa-check"></i>' : '';
        const text = this.closest('.task-item')?.querySelector('.task-text');
        if (text) text.classList.toggle('completed', !isChecked);
      });
    });
  }

  /* ============================================
     KEYBOARD SHORTCUT: CMD+K / CTRL+K → Search
     ============================================ */
  function initSearchShortcut() {
    document.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        const search = document.getElementById('globalSearch');
        if (search) {
          search.focus();
          search.select();
        }
      }
    });
  }

  /* ============================================
     TABLE ACTIONS
     ============================================ */
  function initTableActions() {
    const masterCheck = document.getElementById('masterCheck');
    if (masterCheck) {
      masterCheck.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(c => {
          c.checked = this.checked;
        });
      });
    }

    document.querySelectorAll('.row-check').forEach(c => {
      c.addEventListener('change', function () {
        const row = this.closest('tr');
        if (row) row.classList.toggle('table-active', this.checked);
      });
    });

    document.querySelectorAll('[data-confirm]').forEach(btn => {
      btn.addEventListener('click', function (e) {
        const msg = this.dataset.confirm;
        if (!confirm(msg)) e.preventDefault();
      });
    });
  }

})();
