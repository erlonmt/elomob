document.addEventListener('DOMContentLoaded',()=>{
 document.querySelectorAll('[data-destination]').forEach(button=>button.addEventListener('click',()=>{const select=document.querySelector('select[name="destination_id"]');select.value=button.dataset.destination;select.focus();document.querySelector('.search-panel').scrollIntoView({block:'start'});}));
 document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{const submit=form.querySelector('button[type="submit"],button:not([type])');if(submit){submit.dataset.label=submit.textContent;submit.textContent='Aguarde…';setTimeout(()=>{submit.textContent=submit.dataset.label;},10000);}}));
 const toast=document.querySelector('.toast');if(toast)setTimeout(()=>toast.remove(),4500);
 const gps=document.querySelector('[data-gps]');
 if(gps)gps.addEventListener('click',()=>{
  const message=text=>{let p=document.querySelector('#gps-message');if(!p){p=document.createElement('p');p.id='gps-message';p.setAttribute('role','status');p.className='demo-note';gps.closest('form').append(p);}p.textContent=text;};
  if(!navigator.geolocation){message('Localização indisponível. Escolha a origem na lista.');return;}
  gps.disabled=true;
  navigator.geolocation.getCurrentPosition(async position=>{
   try{
    const response=await fetch('/api/v1/paradas',{headers:{Accept:'application/json'}});
    if(!response.ok)throw Error('request');
    const {data}=await response.json(); const lat=position.coords.latitude; const lng=position.coords.longitude;
    const rad=v=>v*Math.PI/180; const distance=p=>{const a=Math.sin(rad(Number(p.latitude)-lat)/2)**2+Math.cos(rad(lat))*Math.cos(rad(Number(p.latitude)))*Math.sin(rad(Number(p.longitude)-lng)/2)**2;return 6371*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));};
    data.sort((a,b)=>distance(a)-distance(b));
    if(!data.length||distance(data[0])>25){message('Nenhum ponto demonstrativo perto de você. Selecione a origem manualmente.');return;}
    document.querySelector('select[name="origin_id"]').value=data[0].id;
    message('Origem aproximada pelo ponto cadastrado mais próximo. Não há rastreamento em tempo real.');
   }catch(e){message('Não foi possível obter os locais. Escolha a origem manualmente.');}finally{gps.disabled=false;}
  },()=>{gps.disabled=false;message('Não foi possível acessar sua localização. Escolha a origem manualmente.');},{enableHighAccuracy:false,timeout:8000,maximumAge:60000});
 });
});