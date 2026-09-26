(function(){
  'use strict';

  const clamp=(v,min,max)=>Math.max(min,Math.min(max,v));

  function init(root){
    if(!root || root.dataset.wyaReady==='1') return;
    root.dataset.wyaReady='1';

    const stage=root.querySelector('.wya-menu__stage');
    const media=root.querySelector('.wya-menu__media');
    const wrap=root.querySelector('.wya-menu__media-wrap');
    const items=Array.from(root.querySelectorAll('.wya-menu__item'));
    const status=root.querySelector('.wya-menu__sr-status');

    const pivotX=parseFloat(root.dataset.pivotX||'50');
    const pivotY=parseFloat(root.dataset.pivotY||'50');
    const desktopRadius=parseFloat(root.dataset.radiusDesktop||'300');
    const mobileRadius=parseFloat(root.dataset.radiusMobile||'175');
    const ellipseY=parseFloat(root.dataset.ellipseY||'0.72');
    const ringGap=parseFloat(root.dataset.ringGap||'105');
    const labelSize=parseFloat(root.dataset.labelSize||'14');
    const labelWidth=parseFloat(root.dataset.labelWidth||'150');
    const maxYaw=parseFloat(root.dataset.maxYaw||'10');
    const maxPitch=parseFloat(root.dataset.maxPitch||'6');
    const smoothing=parseFloat(root.dataset.lookatSmoothing||'0.16');

    root.style.setProperty('--wya-pivot-x',pivotX+'%');
    root.style.setProperty('--wya-pivot-y',pivotY+'%');
    root.style.setProperty('--wya-label-size',labelSize+'px');
    root.style.setProperty('--wya-label-width',labelWidth+'px');
    wrap.style.transformOrigin=pivotX+'% '+pivotY+'%';

    const groups=new Map();
    items.sort((a,b)=>{
      const ra=parseInt(a.dataset.ring||'0')-parseInt(b.dataset.ring||'0');
      return ra || (parseInt(a.dataset.order||'0')-parseInt(b.dataset.order||'0'));
    }).forEach(item=>{
      const ring=Math.max(0,parseInt(item.dataset.ring||'0'));
      if(!groups.has(ring)) groups.set(ring,[]);
      groups.get(ring).push(item);
    });

    let targetYaw=0,targetPitch=0,currentYaw=0,currentPitch=0;
    let active=null;

    function getRadius(){
      return window.matchMedia('(max-width:900px)').matches ? mobileRadius : desktopRadius;
    }

    function layout(){
      const radius=getRadius();
      const ringEntries=[...groups.entries()].sort((a,b)=>a[0]-b[0]);
      const totalRings=Math.max(1,ringEntries.length);

      ringEntries.forEach(([ring,group])=>{
        const ringRadius=radius + ring*ringGap;
        const step=(Math.PI*2)/Math.max(group.length,1);
        const offset=(ring%2 ? step/2 : 0) - Math.PI/2;

        group.forEach((item,index)=>{
          const a=offset+step*index;
          let x=Math.cos(a)*ringRadius;
          let y=Math.sin(a)*ringRadius*ellipseY;
          item.style.setProperty('--wya-x',x.toFixed(2)+'px');
          item.style.setProperty('--wya-y',y.toFixed(2)+'px');
          item.style.setProperty('--wya-z',(ring*18).toFixed(2)+'px');
          item.style.setProperty('--wya-scale',ring===0?'1':'0.96');
        });
      });

      // One extra pass: if anchors still collide, push the affected ring outward.
      ringEntries.forEach(([ring,group])=>{
        if(group.length<2) return;
        let collided=true, tries=0;
        while(collided && tries<5){
          collided=false; tries++;
          const rects=group.map(x=>x.getBoundingClientRect());
          outer: for(let i=0;i<rects.length;i++){
            for(let j=i+1;j<rects.length;j++){
              const a=rects[i],b=rects[j];
              const overlap=!(a.right+8<b.left || a.left>b.right+8 || a.bottom+8<b.top || a.top>b.bottom+8);
              if(overlap){
                collided=true;
                const scale=1.12;
                group.forEach(item=>{
                  const x=parseFloat(getComputedStyle(item).getPropertyValue('--wya-x'))||0;
                  const y=parseFloat(getComputedStyle(item).getPropertyValue('--wya-y'))||0;
                  item.style.setProperty('--wya-x',(x*scale).toFixed(2)+'px');
                  item.style.setProperty('--wya-y',(y*scale).toFixed(2)+'px');
                });
                break outer;
              }
            }
          }
        }
      });
    }

    function setLookAt(item){
      const rect=stage.getBoundingClientRect();
      const itemRect=item.getBoundingClientRect();
      const pivot={
        x:rect.left + rect.width*(pivotX/100),
        y:rect.top + rect.height*(pivotY/100)
      };
      const target={
        x:itemRect.left+itemRect.width/2,
        y:itemRect.top+itemRect.height/2
      };
      const dx=target.x-pivot.x;
      const dy=target.y-pivot.y;
      const halfW=Math.max(rect.width*0.5,1);
      const halfH=Math.max(rect.height*0.5,1);
      targetYaw=clamp((dx/halfW)*maxYaw,-maxYaw,maxYaw);
      targetPitch=clamp((dy/halfH)*maxPitch,-maxPitch,maxPitch);
    }

    function clearLook(){
      targetYaw=0; targetPitch=0;
      if(active) active.classList.remove('is-focused');
      active=null;
      status.textContent='';
    }

    function frame(){
      currentYaw += (targetYaw-currentYaw)*smoothing;
      currentPitch += (targetPitch-currentPitch)*smoothing;
      wrap.style.setProperty('--wya-yaw',currentYaw.toFixed(2)+'deg');
      wrap.style.setProperty('--wya-pitch',(-currentPitch).toFixed(2)+'deg');
      requestAnimationFrame(frame);
    }

    items.forEach(item=>{
      item.addEventListener('pointerenter',(event)=>{
        active=item;
        items.forEach(other=>{ if(other!==item) other.classList.remove('is-focused'); });
        item.classList.add('is-focused');
        setLookAt(item);
        const title=item.querySelector('.wya-menu__item-title');
        status.textContent=title?title.textContent:'';
      });

      item.addEventListener('focusin',()=>{ setLookAt(item); item.classList.add('is-focused'); });
      item.addEventListener('pointerleave',()=>{ if(active===item) clearLook(); });

      item.addEventListener('pointerdown',(event)=>{
        if(event.pointerType==='touch'){
          const now=Date.now();
          const secondTap=active===item && item.dataset.lastTap && now-parseInt(item.dataset.lastTap,10)<850;
          item.dataset.lastTap=String(now);
          if(!secondTap) event.preventDefault();
          active=item;
          item.classList.add('is-focused');
          setLookAt(item);
        }
      });
    });

    root.addEventListener('pointerleave',(event)=>{
      if(event.pointerType==='mouse') clearLook();
    });

    window.addEventListener('resize',layout,{passive:true});
    layout();
    frame();
  }

  document.querySelectorAll('.wya-menu').forEach(init);
})();