// assets/js/main.js

// ── Navbar scroll effect ──────────────────────────────────────
const navbar = document.getElementById('navbar');
if (navbar) {
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 60);
  });
}

// ── Hamburger menu ────────────────────────────────────────────
const hamburger = document.getElementById('hamburger');
const navLinks  = document.querySelector('.nav-links');
if (hamburger && navLinks) {
  hamburger.addEventListener('click', () => {
    navLinks.style.display = navLinks.style.display === 'flex' ? 'none' : 'flex';
    navLinks.style.flexDirection = 'column';
    navLinks.style.position = 'absolute';
    navLinks.style.top = '70px';
    navLinks.style.left = '0'; navLinks.style.right = '0';
    navLinks.style.background = 'var(--dark-2)';
    navLinks.style.padding = '1rem 1.5rem';
  });
}

// ── Category filter (menu page) ───────────────────────────────
const catPills = document.querySelectorAll('.cat-pill');
const menuCards = document.querySelectorAll('.menu-card');
catPills.forEach(pill => {
  pill.addEventListener('click', () => {
    catPills.forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    const cat = pill.dataset.cat;
    menuCards.forEach(card => {
      card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
    });
  });
});

// ── Toast notification ────────────────────────────────────────
function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.textContent = msg;
  toast.style.cssText = `
    position:fixed;bottom:24px;right:24px;z-index:9999;
    background:${type === 'success' ? '#4caf50' : '#f44336'};
    color:#fff;padding:.75rem 1.5rem;border-radius:10px;
    font-size:.93rem;font-weight:500;box-shadow:0 6px 24px rgba(0,0,0,.4);
    animation:fadeUp .3s ease;
  `;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// ── Cart quantity controls ────────────────────────────────────
document.querySelectorAll('.qty-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const action  = this.dataset.action;
    const itemId  = this.dataset.id;
    const numEl   = this.parentElement.querySelector('.qty-num');
    fetch('/restaurant/customer/cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=${action}&item_id=${itemId}`
    }).then(r => r.json()).then(d => {
      if (d.success) {
        if (d.new_qty === 0) {
          this.closest('.cart-item')?.remove();
        } else {
          numEl.textContent = d.new_qty;
        }
        const badge = document.querySelector('.badge');
        if (badge) badge.textContent = d.cart_total_qty;
        const subtotal = document.getElementById('subtotal');
        const total    = document.getElementById('total');
        if (subtotal) subtotal.textContent = '৳' + d.subtotal;
        if (total)    total.textContent    = '৳' + d.total;
        showToast(d.message);
      }
    });
  });
});

// ── Add to cart buttons ───────────────────────────────────────
document.querySelectorAll('.add-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const itemId = this.dataset.id;
    fetch('/restaurant/customer/cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add&item_id=${itemId}`
    }).then(r => r.json()).then(d => {
      if (d.success) {
        showToast('Added to cart! 🛒');
        const badge = document.querySelector('.badge');
        if (badge) { badge.textContent = d.cart_total_qty; }
        else {
          const cartBtn = document.querySelector('.cart-btn');
          if (cartBtn) {
            const b = document.createElement('span');
            b.className = 'badge'; b.textContent = d.cart_total_qty;
            cartBtn.appendChild(b);
          }
        }
      } else {
        showToast(d.message || 'Please log in first', 'error');
        if (d.redirect) window.location.href = d.redirect;
      }
    });
  });
});

// ── Scroll reveal ─────────────────────────────────────────────
// Hero flame scene
const heroFloat = document.querySelector('.hero-float');
if (heroFloat && !heroFloat.dataset.enhanced) {
  const flameLabel = heroFloat.textContent.trim() || '🔥';
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const glowLayer = document.createElement('span');
  const auraLayer = document.createElement('span');
  const particleLayer = document.createElement('span');
  const flameIcon = document.createElement('span');

  glowLayer.className = 'hero-flame-glow';
  auraLayer.className = 'hero-flame-aura';
  particleLayer.className = 'hero-flame-particles';
  flameIcon.className = 'hero-flame-icon';
  flameIcon.textContent = flameLabel;
  flameIcon.textContent = '\uD83D\uDD25';

  heroFloat.textContent = '';
  heroFloat.dataset.enhanced = 'true';
  heroFloat.classList.add('is-enhanced');
  heroFloat.append(glowLayer, auraLayer, particleLayer, flameIcon);

  if (!prefersReducedMotion) {
    const particleCount = 18;

    for (let i = 0; i < particleCount; i++) {
      const particle = document.createElement('span');
      const isBurst = i % 5 === 0 || i % 7 === 0;
      const drift = (Math.random() * 100 - 50).toFixed(1);
      const rise = (isBurst ? -(150 + Math.random() * 90) : -(95 + Math.random() * 65)).toFixed(1);
      const size = (isBurst ? 10 + Math.random() * 10 : 6 + Math.random() * 7).toFixed(1);
      const duration = (isBurst ? 1.7 + Math.random() * 0.8 : 2.3 + Math.random() * 1.2).toFixed(2);
      const delay = (-Math.random() * 4.5).toFixed(2);
      const scale = (isBurst ? 1.35 + Math.random() * 0.5 : 0.95 + Math.random() * 0.45).toFixed(2);
      const tiltStart = (Math.random() * 24 - 12).toFixed(1);
      const tiltEnd = (Math.random() * 40 - 20).toFixed(1);
      const flash = (0.68 + Math.random() * 0.32).toFixed(2);

      particle.className = `hero-flame-particle${isBurst ? ' is-burst' : ''}`;
      particle.style.setProperty('--origin', `${(26 + Math.random() * 48).toFixed(1)}%`);
      particle.style.setProperty('--drift', `${drift}px`);
      particle.style.setProperty('--rise', `${rise}px`);
      particle.style.setProperty('--size', `${size}px`);
      particle.style.setProperty('--duration', `${duration}s`);
      particle.style.setProperty('--delay', `${delay}s`);
      particle.style.setProperty('--scale', scale);
      particle.style.setProperty('--tilt-start', `${tiltStart}deg`);
      particle.style.setProperty('--tilt-end', `${tiltEnd}deg`);
      particle.style.setProperty('--flash', flash);

      particleLayer.appendChild(particle);
    }
  }
}

const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('fade-in'); });
}, { threshold: 0.1 });
document.querySelectorAll('.menu-card, .stat-card, .section-card').forEach(el => {
  el.style.opacity = '0';
  observer.observe(el);
});
