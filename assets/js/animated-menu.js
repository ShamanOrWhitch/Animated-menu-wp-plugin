(function () {
  'use strict';

  const clamp = (v,min,max) => Math.max(min,Math.min(max,v));

  function targetFor(root) {
    const selector = root.dataset.targetSelector;
    if (!selector) return null;
    try { return document.querySelector(selector); } catch(e) { return null; }
  }

  function mount(root) {
    if (root.dataset.replaceTarget !== '1') return;
    const target = targetFor(root);
    if (!target) return;

    if (!target.contains(root)) target.appendChild(root);

    target.classList.add('wya-menu-host');
    target.style.setProperty('background-image','none','important');
    target.style.setProperty('overflow','visible','important');
    if (target.getAttribute('role') === 'img') target.removeAttribute('role');
  }

  function preview(root,item) {
    const box = root.querySelector('.wya-menu__preview');
    const mediaBox = root.querySelector('.wya-menu__preview-media');
    const titleBox = root.querySelector('.wya-menu__preview-title');
    if (!box || !mediaBox) return;

    const enabled = root.dataset.previewEnabled === '1';
    const url = enabled && item ? item.dataset.preview : '';
    if (!url || window.matchMedia('(max-width:900px)').matches) {
      box.hidden = true;
      mediaBox.innerHTML = '';
      return;
    }

    mediaBox.innerHTML = '';
    const media = /\.(mp4|webm|ogg)(?:$|\?)/i.test(url) ? document.createElement('video') : document.createElement('img');
    media.src = url;
    if (media.tagName === 'VIDEO') {
      media.muted = true; media.loop = true; media.autoplay = true; media.playsInline = true; media.preload = 'metadata';
      media.play().catch(function(){});
    } else {
      media.alt = ''; media.draggable = false;
    }
    mediaBox.appendChild(media);

    const title = item.querySelector('.wya-menu__item-title');
    titleBox.textContent = title ? title.textContent : '';
    box.style.width = (parseFloat(root.dataset.previewWidth || '210') || 210) + 'px';

    const stage = root.querySelector('.wya-menu__stage');
    const ir = item.getBoundingClientRect(), sr = stage.getBoundingClientRect();
    const width = box.offsetWidth || 210;
    let left = ir.left - sr.left + ir.width / 2;
    let top = ir.top - sr.top - 14;
    left = clamp(left,width/2+8,sr.width-width/2-8);
    top = Math.max(8,top);
    box.style.left = left + 'px';
    box.style.top = top + 'px';
    box.style.transform = 'translate(-50%,-100%)';
    box.hidden = false;
  }

  function init(root) {
    if (!root || root.dataset.wyaReady === '1') return;
    root.dataset.wyaReady = '1';
    mount(root);

    const stage = root.querySelector('.wya-menu__stage');
    const wrap = root.querySelector('.wya-menu__media-wrap');
    const items = Array.from(root.querySelectorAll('.wya-menu__item'));
    const status = root.querySelector('.wya-menu__sr-status');
    if (!stage || !wrap || !items.length) return;

    const pivotX = parseFloat(root.dataset.pivotX || '50');
    const pivotY = parseFloat(root.dataset.pivotY || '50');
    const yawMax = parseFloat(root.dataset.maxYaw || '10');
    const pitchMax = parseFloat(root.dataset.maxPitch || '6');
    const smoothing = parseFloat(root.dataset.lookatSmoothing || '0.14');

    root.style.setProperty('--wya-pivot-x',pivotX+'%');
    root.style.setProperty('--wya-pivot-y',pivotY+'%');
    root.style.setProperty('--wya-label-size',(parseFloat(root.dataset.labelSize||'13'))+'px');
    root.style.setProperty('--wya-label-width',(parseFloat(root.dataset.labelWidth||'140'))+'px');
    root.style.setProperty('--wya-item-size',(parseFloat(root.dataset.itemSize||'58'))+'px');

    function sceneMetrics() {
      const mobile = window.matchMedia('(max-width:900px)').matches;
      root.style.setProperty('--wya-head-size',(parseFloat(root.dataset[mobile?'headMobile':'headDesktop'])||145)+'px');
      root.style.setProperty('--wya-stage-height','100%');
    }

    let active = null, targetYaw = 0, targetPitch = 0, yaw = 0, pitch = 0;
    let lastTouchItem = null, lastTouchTime = 0, suppressUntil = 0;

    function focus(item) {
      active = item;
      items.forEach(function(x){ if(x !== item) x.classList.remove('is-focused'); });
      item.classList.add('is-focused');

      const sr = stage.getBoundingClientRect(), ir = item.getBoundingClientRect();
      const px = sr.left + sr.width * pivotX / 100;
      const py = sr.top + sr.height * pivotY / 100;
      targetYaw = clamp((ir.left + ir.width/2 - px) / (sr.width * .42) * yawMax,-yawMax,yawMax);
      targetPitch = clamp((ir.top + ir.height/2 - py) / (sr.height * .42) * pitchMax,-pitchMax,pitchMax);

      const title = item.querySelector('.wya-menu__item-title');
      if (status) status.textContent = title ? title.textContent : '';
      preview(root,item);
    }

    function clear() {
      active = null; targetYaw = 0; targetPitch = 0;
      items.forEach(function(x){x.classList.remove('is-focused');});
      if (status) status.textContent = '';
      preview(root,null);
    }

    items.forEach(function(item){
      item.addEventListener('pointerenter',function(e){
        if (e.pointerType !== 'touch') focus(item);
      });
      item.addEventListener('focusin',function(){ focus(item); });
      item.addEventListener('pointerup',function(e){
        if (e.pointerType !== 'touch') return;
        const now = Date.now();
        const second = lastTouchItem === item && now-lastTouchTime < 900;
        lastTouchItem = item;
        lastTouchTime = now;
        suppressUntil = now + 850;
        focus(item);
        item.dataset.allowTouchClick = second ? '1' : '0';
      });
      item.addEventListener('click',function(e){
        if (Date.now() < suppressUntil) {
          if (item.dataset.allowTouchClick === '1') {
            item.dataset.allowTouchClick = '0';
            suppressUntil = 0;
            return;
          }
          e.preventDefault();
          e.stopPropagation();
        }
      });
      item.addEventListener('pointerleave',function(e){
        if (e.pointerType !== 'touch' && active === item) clear();
      });
    });

    root.addEventListener('pointerleave',function(e){
      if (e.pointerType !== 'touch') clear();
    });

    function loop() {
      yaw += (targetYaw-yaw)*smoothing;
      pitch += (targetPitch-pitch)*smoothing;
      wrap.style.setProperty('--wya-yaw',yaw.toFixed(2)+'deg');
      wrap.style.setProperty('--wya-pitch',(-pitch).toFixed(2)+'deg');
      requestAnimationFrame(loop);
    }

    function relayout() {
      sceneMetrics();
      if (window.WYARadialLayout) window.WYARadialLayout.layout(root);
      if (active) requestAnimationFrame(function(){focus(active);});
    }

    if (typeof ResizeObserver !== 'undefined') {
      const ro = new ResizeObserver(relayout);
      ro.observe(stage);
    }
    window.addEventListener('resize',relayout,{passive:true});
    relayout();
    requestAnimationFrame(relayout);
    loop();
  }

  function boot() {
    document.querySelectorAll('.wya-menu').forEach(init);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',boot);
  else boot();
})();
