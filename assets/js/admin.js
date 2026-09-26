(function ($) {
  'use strict';

  function byName(name) {
    return Array.from(document.querySelectorAll('input[name],select[name]')).find(function (n) {
      return n.name === name;
    }) || null;
  }

  function mediaType(file) {
    const mime = String(file.mime || file.type || '').toLowerCase();
    const url = String(file.url || '').toLowerCase();
    if (mime.indexOf('video/') === 0 || /\.mp4(?:$|\?)/i.test(url)) return 'mp4';
    const ext = (url.split('.').pop() || 'gif').split('?')[0].toLowerCase();
    return ['gif','png','jpg','jpeg','webp'].includes(ext) ? ext : 'gif';
  }

  function drawMedia(url, type) {
    const editor = document.querySelector('.wya-focal-editor');
    if (!editor) return;
    const holder = editor.querySelector('.wya-focal-media');
    const empty = editor.querySelector('.wya-focal-empty');
    holder.innerHTML = '';

    if (!url) {
      editor.classList.add('is-empty');
      empty.hidden = false;
      updateMarker(50, 50);
      return;
    }

    editor.classList.remove('is-empty');
    empty.hidden = true;

    const media = type === 'mp4' ? document.createElement('video') : document.createElement('img');
    media.className = 'wya-focal-media-element';
    media.src = url;
    if (type === 'mp4') {
      media.muted = true;
      media.loop = true;
      media.autoplay = true;
      media.playsInline = true;
      media.preload = 'metadata';
    } else {
      media.alt = '';
      media.draggable = false;
    }
    holder.appendChild(media);

    const ready = function () {
      updateMarker(
        parseFloat(editor.querySelector('.wya-pivot-x').value || '50'),
        parseFloat(editor.querySelector('.wya-pivot-y').value || '50')
      );
    };
    media.addEventListener(type === 'mp4' ? 'loadedmetadata' : 'load', ready, {once: true});
    if (type === 'mp4') media.play().catch(function () {});
  }

  function updateMarker(x, y) {
    const editor = document.querySelector('.wya-focal-editor');
    if (!editor) return;

    x = Math.max(0, Math.min(100, Number(x) || 50));
    y = Math.max(0, Math.min(100, Number(y) || 50));

    editor.querySelector('.wya-pivot-x').value = x.toFixed(2);
    editor.querySelector('.wya-pivot-y').value = y.toFixed(2);
    editor.querySelector('.wya-pivot-x-value').textContent = x.toFixed(2);
    editor.querySelector('.wya-pivot-y-value').textContent = y.toFixed(2);

    const marker = editor.querySelector('.wya-focal-marker');
    const cross = editor.querySelector('.wya-focal-crosshair');
    const stage = editor.querySelector('.wya-focal-stage');
    const media = editor.querySelector('.wya-focal-media-element');

    let sx = 50, sy = 50;
    if (stage && media && media.getBoundingClientRect().width && media.getBoundingClientRect().height) {
      const sr = stage.getBoundingClientRect();
      const mr = media.getBoundingClientRect();
      sx = ((mr.left + mr.width * x / 100 - sr.left) / sr.width) * 100;
      sy = ((mr.top + mr.height * y / 100 - sr.top) / sr.height) * 100;
    }
    [marker, cross].forEach(function (node) {
      if (!node) return;
      node.style.left = sx + '%';
      node.style.top = sy + '%';
    });
  }

  function focalPointer(event) {
    const editor = document.querySelector('.wya-focal-editor');
    const media = editor && editor.querySelector('.wya-focal-media-element');
    if (!media) return;
    const r = media.getBoundingClientRect();
    const x = ((event.clientX - r.left) / r.width) * 100;
    const y = ((event.clientY - r.top) / r.height) * 100;
    if (x >= 0 && x <= 100 && y >= 0 && y <= 100) updateMarker(x, y);
  }

  function pick(target) {
    const frame = wp.media({
      title: target === 'main' ? 'Выбрать центральную голову' : 'Выбрать медиа',
      button: {text:'Использовать'},
      multiple: false
    });
    frame.on('select', function () {
      const file = frame.state().get('selection').first().toJSON();
      const url = file.url || '';
      if (!url) return;

      if (target === 'main') {
        const type = mediaType(file);
        const id = file.id || 0;
        const idInput = document.querySelector('.wya-media-id');
        const urlInput = document.querySelector('.wya-media-url');
        const typeInput = document.querySelector('.wya-media-type');
        const current = document.querySelector('.wya-media-current');
        if (idInput) idInput.value = id;
        if (urlInput) urlInput.value = url;
        if (typeInput) typeInput.value = type;
        if (current) current.textContent = file.filename || url;
        const editor = document.querySelector('.wya-focal-editor');
        editor.dataset.url = url;
        editor.dataset.type = type;
        drawMedia(url, type);
        updateMarker(50, 50);
        return;
      }

      const input = byName(target);
      if (input) input.value = url;
    });
    frame.open();
  }

  function renumber() {
    document.querySelectorAll('#wya-items .wya-item').forEach(function (row, i) {
      const n = row.querySelector('.wya-item-number');
      if (n) n.textContent = i + 1;
      row.dataset.index = i;
    });
  }

  $(document).on('click','.wya-pick-main',function(){ pick('main'); });
  $(document).on('click','.wya-media-pick:not(.wya-pick-main)',function(){ pick(this.dataset.target); });
  $(document).on('click','.wya-remove-item',function(){ $(this).closest('.wya-item').remove(); renumber(); });

  $('#wya-add-item').on('click',function(){
    const i = document.querySelectorAll('#wya-items .wya-item').length;
    const t = document.getElementById('tmpl-wya-item');
    $('#wya-items').append(t.innerHTML.replaceAll('__INDEX__',i).replaceAll('__NUMBER__',i+1));
    renumber();
  });

  const editor = document.querySelector('.wya-focal-editor');
  if (editor) {
    drawMedia(editor.dataset.url || '', editor.dataset.type || 'gif');
    const stage = editor.querySelector('.wya-focal-stage');
    const marker = editor.querySelector('.wya-focal-marker');

    stage.addEventListener('pointerdown', function(event){
      if (event.target !== marker) focalPointer(event);
    });
    marker.addEventListener('pointerdown', function(event){
      event.preventDefault();
      marker.setPointerCapture(event.pointerId);
    });
    marker.addEventListener('pointermove', function(event){
      if (event.buttons) focalPointer(event);
    });
    window.addEventListener('resize', function(){
      updateMarker(
        parseFloat(editor.querySelector('.wya-pivot-x').value || '50'),
        parseFloat(editor.querySelector('.wya-pivot-y').value || '50')
      );
    }, {passive:true});
  }
})(jQuery);
