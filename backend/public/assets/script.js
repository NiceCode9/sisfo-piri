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
     MINI CHARTS (Canvas-based)
     ============================================ */
  function initCharts() {
    drawBarChart('revenueChart');
    drawLineChart('usersChart');
    drawDonutChart('trafficChart');
    drawAreaChart('perfChart');
  }

  function getThemeColors() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    return {
      primary: '#4f46e5',
      secondary: '#7c3aed',
      success: '#22c55e',
      warning: '#f59e0b',
      grid: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
      text: isDark ? '#94a3b8' : '#94a3b8',
    };
  }

  function drawBarChart(id) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const c = getThemeColors();
    const data = [42, 67, 55, 78, 90, 82, 74, 95, 88, 110, 98, 115];
    const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const W = canvas.width, H = canvas.height;
    const pad = { top: 20, right: 16, bottom: 36, left: 40 };
    const bw = (W - pad.left - pad.right) / data.length;
    const maxV = Math.max(...data) * 1.15;

    ctx.clearRect(0, 0, W, H);

    for (let i = 0; i <= 4; i++) {
      const y = pad.top + (H - pad.top - pad.bottom) * (1 - i/4);
      ctx.strokeStyle = c.grid;
      ctx.lineWidth = 1;
      ctx.setLineDash([4, 4]);
      ctx.beginPath();
      ctx.moveTo(pad.left, y);
      ctx.lineTo(W - pad.right, y);
      ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillStyle = c.text;
      ctx.font = '10px Plus Jakarta Sans';
      ctx.textAlign = 'right';
      ctx.fillText(Math.round(maxV * i / 4), pad.left - 6, y + 3);
    }

    data.forEach((v, i) => {
      const x = pad.left + i * bw + bw * 0.15;
      const barW = bw * 0.7;
      const barH = (v / maxV) * (H - pad.top - pad.bottom);
      const y = H - pad.bottom - barH;

      const grad = ctx.createLinearGradient(0, y, 0, H - pad.bottom);
      grad.addColorStop(0, c.primary);
      grad.addColorStop(1, c.secondary + '40');

      ctx.fillStyle = grad;
      ctx.beginPath();
      roundRect(ctx, x, y, barW, barH, [4, 4, 0, 0]);
      ctx.fill();

      ctx.fillStyle = c.text;
      ctx.font = '10px Plus Jakarta Sans';
      ctx.textAlign = 'center';
      ctx.fillText(labels[i], x + barW / 2, H - pad.bottom + 14);
    });
  }

  function drawLineChart(id) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const c = getThemeColors();
    const data = [1200, 1900, 1450, 2100, 2400, 2100, 2800, 3100, 2700, 3500, 3200, 4100];
    const W = canvas.width, H = canvas.height;
    const pad = { top: 20, right: 16, bottom: 36, left: 50 };
    const maxV = Math.max(...data) * 1.1;

    ctx.clearRect(0, 0, W, H);

    const points = data.map((v, i) => ({
      x: pad.left + (i / (data.length - 1)) * (W - pad.left - pad.right),
      y: pad.top + (1 - v / maxV) * (H - pad.top - pad.bottom)
    }));

    const areaGrad = ctx.createLinearGradient(0, pad.top, 0, H - pad.bottom);
    areaGrad.addColorStop(0, c.success + '35');
    areaGrad.addColorStop(1, c.success + '00');
    ctx.beginPath();
    ctx.moveTo(points[0].x, H - pad.bottom);
    points.forEach(p => ctx.lineTo(p.x, p.y));
    ctx.lineTo(points[points.length-1].x, H - pad.bottom);
    ctx.closePath();
    ctx.fillStyle = areaGrad;
    ctx.fill();

    ctx.beginPath();
    ctx.strokeStyle = c.success;
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.lineCap = 'round';
    points.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
    ctx.stroke();

    points.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
      ctx.fillStyle = c.success;
      ctx.fill();
    });
  }

  function drawDonutChart(id) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const c = getThemeColors();
    const W = canvas.width, H = canvas.height;
    const cx = W/2, cy = H/2;
    const radius = Math.min(W, H)/2 - 20;
    const inner = radius * 0.62;
    const data = [
      { val: 45, color: c.primary, label: 'Organic' },
      { val: 28, color: c.secondary, label: 'Direct' },
      { val: 17, color: c.warning, label: 'Social' },
      { val: 10, color: c.success, label: 'Referral' },
    ];
    const total = data.reduce((s, d) => s + d.val, 0);
    let startAngle = -Math.PI / 2;

    ctx.clearRect(0, 0, W, H);

    data.forEach(d => {
      const angle = (d.val / total) * Math.PI * 2;
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, radius, startAngle, startAngle + angle);
      ctx.closePath();
      ctx.fillStyle = d.color;
      ctx.fill();

      ctx.beginPath();
      ctx.arc(cx, cy, inner, 0, Math.PI * 2);
      ctx.fillStyle = document.documentElement.getAttribute('data-theme') === 'dark' ? '#111318' : '#ffffff';
      ctx.fill();

      startAngle += angle;
    });

    ctx.fillStyle = '#4f46e5';
    ctx.font = 'bold 22px Plus Jakarta Sans';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('45%', cx, cy - 8);
    ctx.fillStyle = '#94a3b8';
    ctx.font = '11px Plus Jakarta Sans';
    ctx.fillText('Organic', cx, cy + 12);
  }

  function drawAreaChart(id) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const c = getThemeColors();
    const data1 = [60, 72, 58, 80, 75, 90, 85, 95, 88, 100, 92, 108];
    const data2 = [40, 55, 48, 62, 58, 70, 65, 78, 70, 82, 75, 88];
    const W = canvas.width, H = canvas.height;
    const pad = { top: 20, right: 16, bottom: 36, left: 40 };
    const maxV = Math.max(...data1) * 1.15;

    ctx.clearRect(0, 0, W, H);

    function drawArea(data, color) {
      const points = data.map((v, i) => ({
        x: pad.left + (i / (data.length - 1)) * (W - pad.left - pad.right),
        y: pad.top + (1 - v / maxV) * (H - pad.top - pad.bottom)
      }));
      const grad = ctx.createLinearGradient(0, pad.top, 0, H - pad.bottom);
      grad.addColorStop(0, color + '30');
      grad.addColorStop(1, color + '00');
      ctx.beginPath();
      ctx.moveTo(points[0].x, H - pad.bottom);
      points.forEach(p => ctx.lineTo(p.x, p.y));
      ctx.lineTo(points[points.length-1].x, H - pad.bottom);
      ctx.closePath();
      ctx.fillStyle = grad;
      ctx.fill();
      ctx.beginPath();
      ctx.strokeStyle = color;
      ctx.lineWidth = 2;
      ctx.lineJoin = 'round';
      points.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
      ctx.stroke();
    }

    drawArea(data2, c.warning);
    drawArea(data1, c.primary);
  }

  function roundRect(ctx, x, y, w, h, radii) {
    const [tr, br, bl, tl] = Array.isArray(radii)
      ? [radii[0], radii[1]||radii[0], radii[2]||radii[0], radii[3]||radii[0]]
      : [radii, radii, radii, radii];
    ctx.moveTo(x + tl, y);
    ctx.lineTo(x + w - tr, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + tr);
    ctx.lineTo(x + w, y + h - br);
    ctx.quadraticCurveTo(x + w, y + h, x + w - br, y + h);
    ctx.lineTo(x + bl, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - bl);
    ctx.lineTo(x, y + tl);
    ctx.quadraticCurveTo(x, y, x + tl, y);
  }

  document.addEventListener('click', function(e) {
    if (e.target.closest('[data-theme-toggle]')) {
      setTimeout(() => {
        drawBarChart('revenueChart');
        drawLineChart('usersChart');
        drawDonutChart('trafficChart');
        drawAreaChart('perfChart');
      }, 350);
    }
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
