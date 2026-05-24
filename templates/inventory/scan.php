<?php /* Scan page — JS lives in inventory/index.php, this is a standalone page */ ?>

<div class="page-header">
  <h1 class="page-title"><i class="ti ti-barcode" style="font-size:1.4rem"></i> Scan Bottles</h1>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;max-width:700px;margin-bottom:2rem">

  <!-- Quick Scan -->
  <div class="card" style="padding:1.75rem;display:flex;flex-direction:column;gap:12px">
    <div style="font-size:36px">⚡</div>
    <h2 style="font-family:var(--font-display);font-size:1.2rem">Quick Scan</h2>
    <p style="font-size:13px;color:var(--text-muted);flex:1">
      Camera stays open. Each barcode is looked up and <strong style="color:var(--text)">saved instantly</strong>
      — no review step. Great for logging a whole shelf fast.
    </p>
    <button class="primary" onclick="startQuickScan()" style="justify-content:center">
      <i class="ti ti-bolt"></i> Start Quick Scan
    </button>
  </div>

  <!-- Bulk Review -->
  <div class="card" style="padding:1.75rem;display:flex;flex-direction:column;gap:12px">
    <div style="font-size:36px">📋</div>
    <h2 style="font-family:var(--font-display);font-size:1.2rem">Bulk Review</h2>
    <p style="font-size:13px;color:var(--text-muted);flex:1">
      Scans queue up on screen. <strong style="color:var(--text)">Review and edit</strong> each bottle
      before saving the batch — perfect when you want to add notes or correct details.
    </p>
    <button onclick="startReviewScan()" style="justify-content:center">
      <i class="ti ti-list-check"></i> Start Bulk Review
    </button>
  </div>

</div>

<div class="card" style="padding:1.5rem;max-width:700px">
  <h2 style="font-family:var(--font-display);font-size:1.1rem;margin-bottom:.75rem">
    <i class="ti ti-info-circle" style="color:var(--accent-light)"></i> Tips
  </h2>
  <ul style="font-size:13px;color:var(--text-muted);display:flex;flex-direction:column;gap:8px;padding-left:1rem">
    <li>Barcode scanning requires <strong style="color:var(--text)">camera permission</strong> — your browser will ask the first time.</li>
    <li>If camera doesn't work, use the <strong style="color:var(--text)">manual entry field</strong> in the scanner overlay to type or paste a barcode number.</li>
    <li>Lookups use <strong style="color:var(--text)">Open Food Facts</strong> and <strong style="color:var(--text)">UPC Item DB</strong> — most commercial spirits, wines, and beers are covered.</li>
    <li>Rare or small-batch bottles may not be in the database — they'll be queued/saved with an "Unknown" name for you to edit.</li>
    <li>In Quick Scan, the same barcode won't be saved twice within 3 seconds (debounce).</li>
  </ul>
</div>

<!-- Reuse scanner overlay from index but standalone -->
<div id="scanner-overlay" class="scanner-overlay" style="display:none">
  <video id="scan-video" class="scanner-video" autoplay playsinline muted></video>
  <div id="scan-frame" class="scanner-frame"><div class="scan-line"></div></div>
  <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.92));padding:2rem 1.5rem;display:flex;flex-direction:column;gap:14px">
    <p id="scan-msg" style="color:var(--text-muted);text-align:center;font-size:13px"></p>
    <div id="scan-queue" style="display:none;max-height:200px;overflow-y:auto;background:rgba(255,255,255,.05);border-radius:8px;padding:10px">
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:8px">Scanned bottles:</p>
      <div id="queue-list" style="display:flex;flex-direction:column;gap:6px"></div>
    </div>
    <!-- Review edit panel -->
    <div id="review-edit" style="display:none;background:rgba(255,255,255,.06);border-radius:8px;padding:12px">
      <p style="font-size:12px;color:var(--accent-light);margin-bottom:8px;font-weight:600">Edit before saving:</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <input id="re-name"     placeholder="Name"    style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2);color:#fff;font-size:13px">
        <input id="re-brand"    placeholder="Brand"   style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2);color:#fff;font-size:13px">
        <select id="re-category" style="background:rgba(40,30,15,.9);border-color:rgba(255,255,255,.2);color:#fff;font-size:13px">
          <?php foreach (['Whiskey','Bourbon','Scotch','Wine','Red Wine','White Wine','Rosé','Vodka','Rum','Gin','Tequila','Mezcal','Cognac','Brandy','Champagne','Beer','Other'] as $c): ?>
          <option value="<?= $c ?>"><?= $c ?></option>
          <?php endforeach; ?>
        </select>
        <input id="re-abv" type="number" placeholder="ABV %" step="0.1" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2);color:#fff;font-size:13px">
      </div>
      <div style="display:flex;gap:8px;margin-top:8px">
        <button onclick="confirmReviewItem()" class="primary" style="flex:1;justify-content:center;font-size:13px">✓ Add to queue</button>
        <button onclick="cancelReviewItem()" style="flex:1;justify-content:center;font-size:13px;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#ddd">Skip</button>
      </div>
    </div>
    <div style="display:flex;gap:8px">
      <input id="manual-code" placeholder="Or type barcode number…" style="flex:1;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#fff">
      <button class="primary" onclick="lookupManual()">Look up</button>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="closeScanner()" style="flex:1;background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.15);color:#ddd;justify-content:center">✕ Cancel</button>
      <button id="save-queue-btn" onclick="saveBulkQueue()" style="display:none;flex:1;justify-content:center" class="primary">
        Save all (<span id="queue-count">0</span>)
      </button>
    </div>
  </div>
</div>

<script>
let scanMode='single', scanQueue=[], zxingReader=null, scanStream=null;
let lastCode='', lastCodeTime=0, pendingReview=null;

const DEFAULT_FILL = <?= (int)($settings['default_fill']??100) ?>;

function startQuickScan()  { openScanner('quick'); }
function startReviewScan() { openScanner('review'); }

function openScanner(mode) {
  scanMode=mode; scanQueue=[]; lastCode=''; pendingReview=null;
  document.getElementById('scanner-overlay').style.display='flex';
  document.getElementById('scan-queue').style.display   = mode==='review' ? '' : 'none';
  document.getElementById('save-queue-btn').style.display = 'none';
  document.getElementById('review-edit').style.display  = 'none';
  document.getElementById('scan-msg').textContent =
    mode==='quick'  ? '⚡ Quick scan — auto-saving each bottle' :
                      '📋 Bulk review — scan bottles to queue them';
  startCamera();
}

async function startCamera() {
  let stream;
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video:{facingMode:{ideal:'environment'},width:{ideal:1280}},audio:false
    });
  } catch(err) {
    const msgs={NotAllowedError:'Camera permission denied.',NotFoundError:'No camera found.',NotReadableError:'Camera in use.'};
    document.getElementById('scan-msg').textContent=(msgs[err.name]||err.message)+' Use manual entry below.';
    document.getElementById('scan-msg').style.color='var(--danger)';
    return;
  }
  scanStream=stream;
  const video=document.getElementById('scan-video');
  video.srcObject=stream; await video.play();

  if(!window.ZXing){
    await new Promise((res,rej)=>{
      const s=document.createElement('script');
      s.src='https://cdnjs.cloudflare.com/ajax/libs/zxing-js/0.19.1/zxing.min.js';
      s.onload=res; s.onerror=rej; document.head.appendChild(s);
    });
  }
  const hints=new Map();
  hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS,[
    ZXing.BarcodeFormat.EAN_13,ZXing.BarcodeFormat.EAN_8,
    ZXing.BarcodeFormat.UPC_A,ZXing.BarcodeFormat.UPC_E,
    ZXing.BarcodeFormat.CODE_128,ZXing.BarcodeFormat.CODE_39,
  ]);
  hints.set(ZXing.DecodeHintType.TRY_HARDER,true);
  zxingReader=new ZXing.BrowserMultiFormatReader(hints,400);
  zxingReader.decodeFromStream(stream,video,(result)=>{ if(result) onBarcodeDetected(result.getText()); });
}

async function onBarcodeDetected(code) {
  const now=Date.now();
  if(code===lastCode && now-lastCodeTime<3000) return;
  lastCode=code; lastCodeTime=now;
  if(pendingReview) return; // wait for user to confirm current review item

  if(scanMode==='quick')  await quickSave(code);
  else                    await reviewItem(code);
}

async function quickSave(code) {
  document.getElementById('scan-msg').textContent=`⏳ Looking up ${code}…`;
  const r=await fetch(`/api/barcode/${code}`).then(r=>r.json()).catch(()=>({name:'Unknown',barcode:code,category:'Other'}));
  if(r.error) { r.name='Unknown'; r.barcode=code; r.category='Other'; }
  try {
    await api('POST','/api/bottles',{...r, fill:DEFAULT_FILL});
    document.getElementById('scan-msg').textContent=`✓ Saved: ${r.name}`;
    document.getElementById('scan-msg').style.color='var(--success)';
  } catch(e) {
    document.getElementById('scan-msg').textContent=`⚠ Error: ${e.message}`;
    document.getElementById('scan-msg').style.color='var(--danger)';
  }
  setTimeout(()=>{
    document.getElementById('scan-msg').textContent='⚡ Quick scan — auto-saving each bottle';
    document.getElementById('scan-msg').style.color='var(--text-muted)';
  },2500);
}

async function reviewItem(code) {
  if(scanQueue.find(q=>q.barcode===code)){
    document.getElementById('scan-msg').textContent=`Already queued: ${code}`;
    return;
  }
  document.getElementById('scan-msg').textContent=`⏳ Looking up ${code}…`;
  const r=await fetch(`/api/barcode/${code}`).then(res=>res.json()).catch(()=>({name:'Unknown',barcode:code,category:'Other'}));
  if(r.error){ r.name='Unknown'; r.barcode=code; r.category='Other'; }
  pendingReview={...r,fill:DEFAULT_FILL};
  document.getElementById('re-name').value    =r.name    ||'';
  document.getElementById('re-brand').value   =r.brand   ||'';
  document.getElementById('re-category').value=r.category||'Other';
  document.getElementById('re-abv').value     =r.abv     ||'';
  document.getElementById('review-edit').style.display='';
  document.getElementById('scan-msg').textContent=`Found: ${r.name} — edit if needed`;
  document.getElementById('scan-msg').style.color='var(--accent-light)';
}

function confirmReviewItem() {
  if(!pendingReview) return;
  pendingReview.name     =document.getElementById('re-name').value     ||pendingReview.name;
  pendingReview.brand    =document.getElementById('re-brand').value;
  pendingReview.category =document.getElementById('re-category').value;
  pendingReview.abv      =document.getElementById('re-abv').value;
  scanQueue.push(pendingReview);
  pendingReview=null;
  document.getElementById('review-edit').style.display='none';
  document.getElementById('scan-msg').textContent='📋 Scan next bottle';
  document.getElementById('scan-msg').style.color='var(--text-muted)';
  renderQueue();
}

function cancelReviewItem() {
  pendingReview=null;
  document.getElementById('review-edit').style.display='none';
  document.getElementById('scan-msg').textContent='📋 Scan next bottle';
}

function renderQueue() {
  const list=document.getElementById('queue-list');
  list.innerHTML='';
  document.getElementById('queue-count').textContent=scanQueue.length;
  document.getElementById('save-queue-btn').style.display=scanQueue.length?'':'none';
  scanQueue.forEach((b,i)=>{
    const div=document.createElement('div');
    div.style.cssText='display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.05);padding:6px 10px;border-radius:6px';
    div.innerHTML=`<span style="flex:1;font-size:13px;color:#fff;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">${b.name}</span>
      <span style="font-size:11px;color:rgba(255,255,255,.4)">${b.category}</span>
      <button onclick="scanQueue.splice(${i},1);renderQueue()" style="background:none;border:none;color:var(--danger);padding:2px 6px;font-size:16px;cursor:pointer;min-width:auto">✕</button>`;
    list.appendChild(div);
  });
}

async function saveBulkQueue() {
  if(!scanQueue.length) return;
  const btn=document.getElementById('save-queue-btn');
  btn.disabled=true; btn.textContent='Saving…';
  try {
    const r=await api('POST','/api/bottles/bulk',{bottles:scanQueue});
    showToast(`${r.created} bottle${r.created!==1?'s':''} added`);
    closeScanner();
    setTimeout(()=>location.href='/',600);
  } catch(e) {
    showToast(e.message,'err');
    btn.disabled=false;
    btn.innerHTML=`Save all (<span id="queue-count">${scanQueue.length}</span>)`;
  }
}

function closeScanner() {
  document.getElementById('scanner-overlay').style.display='none';
  zxingReader?.reset(); scanStream?.getTracks().forEach(t=>t.stop());
  zxingReader=null; scanStream=null; pendingReview=null;
}

async function lookupManual() {
  const code=document.getElementById('manual-code').value.trim();
  if(!code) return;
  document.getElementById('manual-code').value='';
  await onBarcodeDetected(code);
}
document.getElementById('manual-code').addEventListener('keydown',e=>{ if(e.key==='Enter') lookupManual(); });
</script>
