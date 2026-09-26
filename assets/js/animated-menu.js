(function () {
  'use strict';

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function init(root) {
    if (!root || root.dataset.wyaReady === '1') return;
    root.dataset.wyaReady = '1';

    const stage = root.querySelector('.wya-menu__stage');
    const media = root.querySelector('.wya-menu__media');
    const mediaWrap = root.querySelector('.wya-menu__media-wrap');
    const status = root.querySelector('.wya-menu__sr-status');
    const items = Array.from(root.querySelectorAll('.wya-menu__item'));
    const pivotX = parseFloat(root.dataset.pivotX || '50');
    const pivotY = parseFloat(root.dataset.pivotY || '50');
    const radius = parseFloat(root.dataset.hoverRadius || '180');
    const maxTilt = parseFloat(root.dataset.maxTilt || '7');

    root.style.setProperty('--wya-pivot-x', `${pivotX}%`);
    root.style.setProperty('--wya-pivot-y', `${pivotY}%`);
    mediaWrap.style.transformOrigin = `${pivotX}% ${pivotY}%`;

    const motionItems = items.map((item) => ({
      item,
      x: parseFloat(item.dataset.x || '0'),
      y: parseFloat(item.dataset.y || '0'),
      z: parseFloat(item.dataset.z || '0'),
      orbitRadius: parseFloat(item.dataset.orbitRadius || '0'),
      orbitAngle: parseFloat(item.dataset.orbitAngle || '0'),
      orbitSpeed: parseFloat(item.dataset.orbitSpeed || '0'),
      amplitude: parseFloat(item.dataset.orbitAmplitude || '0'),
      phase: parseFloat(item.dataset.phase || '0'),
      scale: parseFloat(item.dataset.scale || '1')
    }));

    motionItems.forEach(({ item, x, y, z, scale }) => {
      item.style.setProperty('--wya-x', `${x}%`);
      item.style.setProperty('--wya-y', `${y}%`);
      item.style.setProperty('--wya-z', `${z}px`);
      item.style.setProperty('--wya-scale', scale);
    });

    let active = null;
    let lastTouchTime = 0;
    let startTime = performance.now();
    let raf = 0;

    function animate(now) {
      const elapsed = (now - startTime) / 1000;
      motionItems.forEach((m) => {
        const a = (m.orbitAngle + m.phase) * Math.PI / 180 + elapsed * m.orbitSpeed * Math.PI * 2;
        const ox = Math.cos(a) * m.orbitRadius;
        const oy = Math.sin(a) * m.orbitRadius;
        const bob = m.amplitude ? Math.sin(a * 2) * m.amplitude : 0;
        m.item.style.setProperty('--wya-x', `${(m.x + ox).toFixed(3)}%`);
        m.item.style.setProperty('--wya-y', `${(m.y + oy + bob).toFixed(3)}%`);
      });
      raf = requestAnimationFrame(animate);
    }
    raf = requestAnimationFrame(animate);

    function aimAt(clientX, clientY) {
      const rect = stage.getBoundingClientRect();
      const cx = rect.left + rect.width * (pivotX / 100);
      const cy = rect.top + rect.height * (pivotY / 100);
      const dx = clientX - cx;
      const dy = clientY - cy;
      const dist = Math.hypot(dx, dy);
      const strength = clamp(1 - dist / radius, 0, 1);
      const tilt = clamp((dx / Math.max(rect.width, 1)) * maxTilt * 2.6 * strength, -maxTilt, maxTilt);
      mediaWrap.style.setProperty('--wya-tilt', `${tilt.toFixed(2)}deg`);
      root.style.setProperty('--wya-pointer-x', `${clientX - rect.left}px`);
      root.style.setProperty('--wya-pointer-y', `${clientY - rect.top}px`);
    }

    function focusItem(item, pointerEvent) {
      if (!item) return;
      if (active && active !== item) active.classList.remove('is-focused', 'is-previewing');
      active = item;
      aimAt(pointerEvent.clientX, pointerEvent.clientY);
      item.classList.add('is-focused');
      status.textContent = item.querySelector('.wya-menu__item-title')?.textContent || '';

      const preview = item.dataset.preview;
      if (preview && media && media.tagName === 'VIDEO') {
        media.dataset.baseSrc ||= media.currentSrc || media.src;
        if (media.src !== preview) {
          media.pause();
          media.src = preview;
          media.load();
          media.play().catch(() => {});
        }
        item.classList.add('is-previewing');
      }
    }

    function clearFocus() {
      items.forEach((item) => item.classList.remove('is-focused'));
      if (active) active.classList.remove('is-previewing');
      active = null;
      mediaWrap.style.setProperty('--wya-tilt', '0deg');
      status.textContent = '';
    }

    root.addEventListener('pointermove', (event) => {
      let closest = null;
      let closestDistance = Infinity;
      items.forEach((item) => {
        const ir = item.getBoundingClientRect();
        const ix = ir.left + ir.width / 2;
        const iy = ir.top + ir.height / 2;
        const d = Math.hypot(event.clientX - ix, event.clientY - iy);
        if (d < closestDistance) {
          closestDistance = d;
          closest = item;
        }
      });
      if (closest && closestDistance <= radius * 0.9) {
        focusItem(closest, event);
      } else if (event.pointerType === 'mouse') {
        clearFocus();
      }
    });

    root.addEventListener('pointerleave', (event) => {
      if (event.pointerType === 'mouse') clearFocus();
    });

    items.forEach((item) => {
      item.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'touch') return;
        const now = Date.now();
        const isSecondTap = active === item && (now - lastTouchTime) < 850;
        lastTouchTime = now;
        focusItem(item, event);
        if (!isSecondTap) event.preventDefault();
      });
    });

    root.addEventListener('remove', () => cancelAnimationFrame(raf));
  }

  document.querySelectorAll('.wya-menu').forEach(init);
})();
