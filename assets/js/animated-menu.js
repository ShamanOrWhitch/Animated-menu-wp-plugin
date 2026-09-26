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

    mediaWrap.style.transformOrigin = `${pivotX}% ${pivotY}%`;

    items.forEach((item) => {
      const x = parseFloat(item.dataset.x || '0');
      const y = parseFloat(item.dataset.y || '0');
      const z = parseFloat(item.dataset.z || '0');
      item.style.setProperty('--wya-x', `${x}%`);
      item.style.setProperty('--wya-y', `${y}%`);
      item.style.setProperty('--wya-z', `${z}px`);
    });

    let active = null;
    let lastTouchTime = 0;

    function aimAt(clientX, clientY, item) {
      const rect = stage.getBoundingClientRect();
      const cx = rect.left + rect.width * (pivotX / 100);
      const cy = rect.top + rect.height * (pivotY / 100);
      const dx = clientX - cx;
      const dy = clientY - cy;
      const dist = Math.hypot(dx, dy);
      const strength = clamp(1 - dist / radius, 0, 1);
      const tilt = clamp((dx / Math.max(rect.width, 1)) * maxTilt * 2.6 * strength, -maxTilt, maxTilt);
      const lift = clamp((dy / Math.max(rect.height, 1)) * maxTilt * 2.6 * strength, -maxTilt, maxTilt);

      mediaWrap.style.transform = `rotate(${tilt.toFixed(2)}deg) translate(${(-lift * 0.45).toFixed(2)}px, ${(lift * 0.25).toFixed(2)}px)`;
      root.style.setProperty('--wya-pointer-x', `${clientX - rect.left}px`);
      root.style.setProperty('--wya-pointer-y', `${clientY - rect.top}px`);

      if (item) {
        item.classList.add('is-focused');
        item.style.setProperty('--wya-distance', `${dist.toFixed(1)}px`);
      }
    }

    function focusItem(item, pointerEvent) {
      if (!item) return;
      if (active && active !== item) active.classList.remove('is-focused', 'is-previewing');
      active = item;
      aimAt(pointerEvent.clientX, pointerEvent.clientY, item);
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
      mediaWrap.style.transform = '';
      status.textContent = '';
    }

    root.addEventListener('pointermove', (event) => {
      let closest = null;
      let closestDistance = Infinity;
      const rect = stage.getBoundingClientRect();
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
        if (isSecondTap) return;
        event.preventDefault();
      });

      item.addEventListener('click', (event) => {
        if (event.detail > 1) return;
        if (event.pointerType === 'touch') {
          if (active !== item) {
            event.preventDefault();
            return;
          }
        }
      });
    });
  }

  document.querySelectorAll('.wya-menu').forEach(init);
})();
