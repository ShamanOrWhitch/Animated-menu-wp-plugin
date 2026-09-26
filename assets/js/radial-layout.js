(function () {
  'use strict';

  function clamp(v, min, max) {
    return Math.max(min, Math.min(max, v));
  }

  function rects(group) {
    return group.map(function (item) {
      const r = item.getBoundingClientRect();
      return {left:r.left,right:r.right,top:r.top,bottom:r.bottom};
    });
  }

  function overlaps(a, b, gap) {
    return !(a.right + gap <= b.left || b.right + gap <= a.left || a.bottom + gap <= b.top || b.bottom + gap <= a.top);
  }

  function layout(root) {
    const stage = root.querySelector('.wya-menu__stage');
    const items = Array.from(root.querySelectorAll('.wya-menu__item'));
    if (!stage || !items.length) return;

    const mobile = window.matchMedia('(max-width:900px)').matches;
    const base = parseFloat(root.dataset[mobile ? 'radiusMobile' : 'radiusDesktop']) || 205;
    const ex = parseFloat(root.dataset.ellipseX) || 1.12;
    const ey = parseFloat(root.dataset.ellipseY) || 0.72;
    const start = parseFloat(root.dataset.startAngle) || 0;
    const arc = parseFloat(root.dataset.arcRange) || 120;
    const gap = 10;
    const ringGap = parseFloat(root.dataset.ringGap) || 135;
    const mode = root.dataset.layoutMode || 'side-balance';
    const stageBox = stage.getBoundingClientRect();

    const rings = new Map();
    items.slice().sort(function(a,b){
      const r = (parseInt(a.dataset.ring || '0',10)||0) - (parseInt(b.dataset.ring || '0',10)||0);
      return r || ((parseInt(a.dataset.order || '0',10)||0) - (parseInt(b.dataset.order || '0',10)||0));
    }).forEach(function(item){
      const ring = clamp(parseInt(item.dataset.ring || '0',10)||0,0,3);
      if (!rings.has(ring)) rings.set(ring,[]);
      rings.get(ring).push(item);
    });

    function point(ring, index, count, scale) {
      const radius = (base + ring * ringGap) * scale;
      let deg;
      if (mode === 'side-balance') {
        const right = Math.ceil(count / 2);
        if (index < right) {
          const step = right > 1 ? arc / (right - 1) : 0;
          deg = -arc / 2 + step * index;
        } else {
          const leftCount = count - right;
          const li = index - right;
          const step = leftCount > 1 ? arc / (leftCount - 1) : 0;
          deg = 180 - arc / 2 + step * li;
        }
      } else {
        const step = count > 1 ? 360 / count : 0;
        deg = -90 + step * index;
      }
      deg += start;
      const rad = deg * Math.PI / 180;
      return {
        x: Math.cos(rad) * radius * ex,
        y: Math.sin(rad) * radius * ey
      };
    }

    function apply(group, scale) {
      group.forEach(function(item, i){
        const p = point(parseInt(item.dataset.ring || '0',10)||0, i, group.length, scale);
        item.style.setProperty('--wya-x', p.x.toFixed(2) + 'px');
        item.style.setProperty('--wya-y', p.y.toFixed(2) + 'px');
        item.style.setProperty('--wya-z', ((parseInt(item.dataset.ring || '0',10)||0) * 14).toFixed(2) + 'px');
      });
    }

    Array.from(rings.entries()).forEach(function(entry){
      const group = entry[1];
      let scale = 1;

      for (let i=0;i<16;i++) {
        apply(group, scale);
        const rr = rects(group);

        let collision = false;
        for (let a=0;a<rr.length && !collision;a++) {
          for (let b=a+1;b<rr.length;b++) {
            if (overlaps(rr[a],rr[b],gap)) {
              collision = true;
              break;
            }
          }
        }

        const outside = rr.some(function(r){
          return r.left < stageBox.left + 10 ||
                 r.right > stageBox.right - 10 ||
                 r.top < stageBox.top + 10 ||
                 r.bottom > stageBox.bottom - 10;
        });

        if (outside) {
          /*
           * Keep the complete menu inside the actual cover/stage.
           * This is deliberately allowed to shrink below 1: the old
           * implementation only enlarged rings and could leave nodes outside.
           */
          scale *= 0.88;
        } else if (collision) {
          scale *= 1.08;
        } else {
          break;
        }

        scale = Math.max(0.48, Math.min(scale, mobile ? 1.45 : 1.32));
      }
    });

    const ordered = Array.from(rings.entries()).sort(function(a,b){return a[0]-b[0];});
    for (let r=1;r<ordered.length;r++) {
      const outer = ordered[r][1];
      for (let tries=0;tries<6;tries++) {
        const outerRects = rects(outer);
        const innerRects = ordered.slice(0,r).flatMap(function(e){return rects(e[1]);});
        let hit = false;
        outerRects.forEach(function(a){ innerRects.forEach(function(b){ if(overlaps(a,b,12)) hit=true; }); });
        if (!hit) break;
        const current = parseFloat(root.dataset[mobile ? 'radiusMobile' : 'radiusDesktop']) || base;
        const currentRing = parseInt(outer[0].dataset.ring || '1',10)||1;
        const scale = (current + currentRing * ringGap + 18) / (current + currentRing * ringGap);
        apply(outer, scale);
      }
    }
  }

  window.WYARadialLayout = {layout: layout};
})();
