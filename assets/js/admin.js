(function($){
  'use strict';
  function cssEscapeName(name){
    return name.replace(/([:\[\],=])/g,'\\$1');
  }
  function openMedia(target){
    const frame = wp.media({title:'Выбрать медиа', button:{text:'Использовать'}, multiple:false});
    frame.on('select', function(){
      const file = frame.state().get('selection').first().toJSON();
      const input = document.querySelector('[name="'+cssEscapeName(target)+'"]');
      if(input) input.value = file.url || '';
    });
    frame.open();
  }
  function renumber(){
    document.querySelectorAll('#wya-items .wya-item').forEach((row,i)=>{
      const n=row.querySelector('.wya-item-number');
      if(n) n.textContent=i+1;
    });
  }
  $(document).on('click','.wya-media-pick',function(){openMedia(this.dataset.target);});
  $(document).on('click','.wya-remove-item',function(){
    $(this).closest('.wya-item').remove();
    renumber();
  });
  $('#wya-add-item').on('click',function(){
    const n = document.querySelectorAll('#wya-items .wya-item').length;
    let html = document.getElementById('tmpl-wya-item').innerHTML
      .replaceAll('__INDEX__', n)
      .replaceAll('__NUMBER__', n+1);
    $('#wya-items').append(html);
    renumber();
  });
})(jQuery);
