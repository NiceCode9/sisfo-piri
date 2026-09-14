(function () {
  'use strict';

  const canvas = document.getElementById('particleCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let W, H;
  let particles = [];
  let mouse = { x: -9999, y: -9999 };
  let animId;

  const COUNT = 110;
  const CONNECT_DIST = 120;
  const MOUSE_RADIUS = 140;

  function resize() {
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  function isDark() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
  }

  function getColors() {
    return isDark()
      ? { particle: '255,255,255', link: '255,255,255', glow: '79,70,229' }
      : { particle: '79,70,229', link: '79,70,229', glow: '79,70,229' };
  }

  class Particle {
    constructor() {
      this.reset();
    }
    reset() {
      this.x = Math.random() * W;
      this.y = Math.random() * H;
      this.vx = (Math.random() - 0.5) * 0.5;
      this.vy = (Math.random() - 0.5) * 0.5;
      this.radius = Math.random() * 2 + 1.5;
      this.baseVx = this.vx;
      this.baseVy = this.vy;
    }
    update() {
      const dx = this.x - mouse.x;
      const dy = this.y - mouse.y;
      const dist = Math.sqrt(dx * dx + dy * dy);

      if (dist < MOUSE_RADIUS) {
        const force = (MOUSE_RADIUS - dist) / MOUSE_RADIUS;
        const angle = Math.atan2(dy, dx);
        this.vx += Math.cos(angle) * force * 0.8;
        this.vy += Math.sin(angle) * force * 0.8;
      }

      this.vx += (this.baseVx - this.vx) * 0.01;
      this.vy += (this.baseVy - this.vy) * 0.01;

      this.x += this.vx;
      this.y += this.vy;

      if (this.x < -20) this.x = W + 20;
      if (this.x > W + 20) this.x = -20;
      if (this.y < -20) this.y = H + 20;
      if (this.y > H + 20) this.y = -20;
    }
    draw() {
      const c = getColors();
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${c.particle},0.6)`;
      ctx.fill();
    }
  }

  function initParticles() {
    particles = [];
    for (let i = 0; i < COUNT; i++) {
      particles.push(new Particle());
    }
  }

  function drawLinks() {
    const c = getColors();
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < CONNECT_DIST) {
          const alpha = (1 - dist / CONNECT_DIST) * 0.2;
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = `rgba(${c.link},${alpha})`;
          ctx.lineWidth = 0.6;
          ctx.stroke();
        }
      }
    }
  }

  function drawCursorGlow() {
    const c = getColors();
    const grad = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, MOUSE_RADIUS);
    grad.addColorStop(0, `rgba(${c.glow},0.06)`);
    grad.addColorStop(1, `rgba(${c.glow},0)`);
    ctx.fillStyle = grad;
    ctx.fillRect(mouse.x - MOUSE_RADIUS, mouse.y - MOUSE_RADIUS, MOUSE_RADIUS * 2, MOUSE_RADIUS * 2);
  }

  function animate() {
    ctx.clearRect(0, 0, W, H);
    drawCursorGlow();
    drawLinks();
    particles.forEach(p => {
      p.update();
      p.draw();
    });
    animId = requestAnimationFrame(animate);
  }

  function onMouseMove(e) {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
  }

  function onMouseLeave() {
    mouse.x = -9999;
    mouse.y = -9999;
  }

  function onThemeChange() {
    // Redraw instantly on next frame
  }

  window.addEventListener('resize', () => {
    resize();
    particles.forEach(p => {
      if (p.x > W) p.x = Math.random() * W;
      if (p.y > H) p.y = Math.random() * H;
    });
  });

  document.addEventListener('mousemove', onMouseMove);
  document.addEventListener('mouseleave', onMouseLeave);

  // Watch for theme changes
  const observer = new MutationObserver(() => {
    // colors update naturally via getColors() each frame
  });
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

  resize();
  initParticles();
  animate();
})();
