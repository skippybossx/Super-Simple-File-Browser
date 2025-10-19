<?php
// ==================================
//  Super Simple File Browser v1.1
//  "Nord Dark Touch Edition"
//  Updated: smoother modals, larger touch UI, Copy+Download buttons
// ==================================

$root = realpath(__DIR__);
$dir  = isset($_GET['dir']) ? $_GET['dir'] : '.';
$path = realpath($dir);
if ($path === false || strpos($path, $root) !== 0) die('Invalid path.');

$baseUrl = dirname($_SERVER['SCRIPT_NAME']);
if ($baseUrl === '/') $baseUrl = '';

function humanSize($b) {
  $u = ['B','KB','MB','GB','TB']; $i = 0;
  while ($b >= 1024 && $i < count($u)-1) { $b /= 1024; $i++; }
  return round($b, 2) . ' ' . $u[$i];
}

$items = array_diff(scandir($path), ['.','..']);

if (isset($_GET['view'])) {
  $f = realpath($_GET['view']);
  if ($f && strpos($f, $root) === 0 && is_file($f)) {

    // If ?view=download, send as attachment
    if (isset($_GET['download'])) {
      header('Content-Type: text/plain');
      header('Content-Disposition: attachment; filename="'.basename($f).'"');
      readfile($f);
      exit;
    }

    // Normal inline view
    header('Content-Type: text/plain; charset=utf-8');
    echo htmlspecialchars(file_get_contents($f));
    exit;
  }

  http_response_code(404);
  echo "File not found.";
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Simple File Browser</title>
<link rel="icon" type="image/png" href="favicon.png">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<meta name="viewport" content="width=100, initial-scale=0.8, maximum-scale=3.0, user-scalable=yes">

<style>
:root{
  --bg-main:#2e3440;--bg-dark:#232831;--bg-hover:#3b4252;--text:#eceff4;--text-dim:#d8dee9;
  --accent:#81a1c1;--accent2:#5e81ac;--yellow:#ebcb8b;--green:#a3be8c;--border:#434c5e;
  --blur:10px;
}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{font-family:'JetBrains Mono',monospace;background:var(--bg-main);color:var(--text);margin:0;overflow-x:hidden;}
header{background:linear-gradient(90deg,var(--accent2),var(--accent));padding:16px 22px;color:#fff;font-size:1.3em;
display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 6px rgba(0,0,0,.3);}
.container{padding:14px 20px 60px;}

/* Table */
table{width:100%;border-collapse:collapse;margin-top:10px;border-radius:10px;overflow:hidden;}
th,td{padding:14px 18px;border-bottom:1px solid var(--border);}
th{background:var(--bg-dark);color:var(--text-dim);font-weight:500;text-align:left;user-select:none;cursor:pointer;position:relative;font-size:.95em;}
th .label{display:inline-block;padding-right:18px;}
th .arrow{position:absolute;right:10px;top:50%;transform:translateY(-50%);opacity:.8;}
th.sortable:hover{color:#fff;}
td{color:var(--text);}
tr:hover{background:var(--bg-hover);}
a{color:var(--accent);text-decoration:none;}
a:hover{color:var(--accent2);}
.folder i{color:var(--yellow);}
.file i{color:var(--green);}
.size,.date{text-align:right;white-space:nowrap;}
.icon{width:24px;text-align:center;margin-right:10px;}

/* Hover image preview */
.preview{position:absolute;display:none;opacity:0;transform:scale(.95);
border:1px solid var(--border);background:var(--bg-dark);padding:6px;border-radius:10px;z-index:100;
box-shadow:0 4px 12px rgba(0,0,0,.4);transition:opacity .25s ease,transform .25s ease;}
.preview.show{display:block;opacity:1;transform:scale(1);}
.preview img{max-width:300px;max-height:300px;border-radius:6px;}

/* Modal base */
.modal{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);
display:none;align-items:center;justify-content:center;opacity:0;transition:opacity .35s ease;z-index:200;backdrop-filter:blur(var(--blur));}
.modal.show{display:flex;opacity:1;}
.modal-content{
  background:var(--bg-dark);
  color:var(--text);
  padding:20px;
  border-radius:14px;
  max-width:85%;
  max-height:80%;
  overflow:auto;
  white-space:pre-wrap;
  font-family:'JetBrains Mono',monospace;
  box-shadow:0 0 25px rgba(0,0,0,.6);
  transform:translateY(-20px);
  transition:transform .35s ease;
}
.modal-content::-webkit-scrollbar-corner{background:transparent;}
.modal.show .modal-content{transform:translateY(0);}

/* Buttons */
.modal-close,.modal-copy{
  position:absolute;
  top:20px;
  font-size:17px;
  cursor:pointer;
  color:#fff;
  background:var(--accent2);
  border:none;
  padding:10px 18px;
  border-radius:6px;
  transition:background .2s,transform .15s;
}
.modal-close:hover,.modal-copy:hover{background:var(--accent);transform:scale(1.05);}
.modal-copy{right:auto;}
#modalCopy{ right:300px; }
#modalDownloadText{ right:160px; }
#imgCopy{ right:320px; }
#imgDownload{ right:180px; }
.modal-close{ right:30px; }

/* Image modal specifics */
#imgContent{text-align:center;white-space:normal;}
#imgFull{max-width:100%;max-height:75vh;border-radius:10px;transition:transform .25s ease;}
#imgFull:hover{transform:scale(1.02);}

/* Gallery navigation buttons */
.img-nav{
  position:absolute;
  top:50%;
  transform:translateY(-50%) scale(1);
  background:rgba(0,0,0,.4);
  border:1px solid var(--border);
  border-radius:50%;
  width:52px;height:52px;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  user-select:none;
  font-size:20px;
  color:#fff;
  transition:background .2s,border-color .2s,transform .2s ease-in-out;
  z-index:999;
}
.img-nav:hover{
  background:rgba(0,0,0,.6);
  border-color:#666;
  transform:translateY(-50%) scale(1.2);
}
#imgPrev{left:28px;} #imgNext{right:28px;}

/* Scrollbars (dark) */
::-webkit-scrollbar{width:10px;height:10px;}
::-webkit-scrollbar-thumb{background:var(--accent2);border-radius:8px;}
::-webkit-scrollbar-thumb:hover{background:var(--accent);}
::-webkit-scrollbar-track{background:var(--bg-dark);}

/* Mobile / Touch */
@media (max-width:780px){
  header{font-size:1.1em;padding:14px 16px;}
  th,td{padding:18px 14px;font-size:1.1em;}
  a{font-size:1.05em;}
  .modal-close,.modal-copy{padding:14px 20px;font-size:18px;}
  #imgPrev,#imgNext{width:60px;height:60px;font-size:24px;}
  .preview img{max-width:200px;max-height:200px;}
  body{overflow-y:scroll;}
  .container{
    padding:10px 12px 60px;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }
  table{
    min-width:700px;
  }
}
</style>
</head>
<body>
<header>
  <div><i class="fa-solid fa-folder-tree"></i> Super Simple File Browser</div>
  <div><?=htmlspecialchars(str_replace($root,'',$path)?:'/')?></div>
</header>

<div class="container">
<table id="fileTable">
  <thead>
    <tr>
      <th class="sortable" data-sort="name"><span class="label">Name</span><span class="arrow"></span></th>
      <th class="sortable size" data-sort="size"><span class="label">Size</span><span class="arrow"></span></th>
      <th class="sortable date" data-sort="date"><span class="label">Modified</span><span class="arrow"></span></th>
    </tr>
  </thead>
  <tbody id="fileTbody">
<?php
if ($path !== $root) {
  $p = dirname($path);
  echo '<tr class="static-parent"><td colspan="3"><a class="folder" href="?dir=' . urlencode($p) . '"><i class="fa-solid fa-arrow-up"></i> [Parent Directory]</a></td></tr>';
}

$textT = ['txt','log','cfg','ini','json','yml','yaml','md','xml','html','css','js','conf','config'];
$imgT  = ['jpg','jpeg','png','gif','webp','bmp'];
$skipFiles = ['index.php','ftp.php','ssfb.php'];

foreach ($items as $it) {
  if (in_array($it, $skipFiles)) continue;
  $f = $path . DIRECTORY_SEPARATOR . $it;
  if (pathinfo($it, PATHINFO_EXTENSION) === 'php') continue;

  $rel = substr($f, strlen($root));
  $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
  $url = $baseUrl . $rel;

  $name = $it;
  $nameHtml = htmlspecialchars($it);
  $mtime = @filemtime($f) ?: 0;
  $mtimeText = $mtime ? date('Y-m-d H:i:s', $mtime) : '';
  $isDir = is_dir($f);
  $sizeBytes = ($isDir ? 0 : (@filesize($f) ?: 0));
  $sizeText = $isDir ? '—' : humanSize($sizeBytes);
  $ext = strtolower(pathinfo($it, PATHINFO_EXTENSION));

  $icon = '<i class="fa-solid fa-file-lines"></i>';
  if ($isDir) $icon = '<i class="fa-solid fa-folder"></i>';
  elseif (in_array($ext, ['zip','tar','gz','rar','7z','iso'])) $icon = '<i class="fa-solid fa-file-zipper"></i>';
  elseif (in_array($ext, ['mp4','mkv','avi','mov'])) $icon = '<i class="fa-solid fa-file-video"></i>';
  elseif (in_array($ext, ['mp3','wav','ogg'])) $icon = '<i class="fa-solid fa-file-audio"></i>';
  elseif (in_array($ext, $imgT)) $icon = '<i class="fa-solid fa-file-image"></i>';
  elseif (in_array($ext, $textT)) $icon = '<i class="fa-solid fa-file-code"></i>';

  $typeClass = $isDir ? 'folder' : 'file';
  echo '<tr class="row-item" data-type="'.($isDir?'dir':'file').'" data-name="'.htmlspecialchars(mb_strtolower($name)).'" data-size="'.$sizeBytes.'" data-date="'.$mtime.'">';
  echo '<td>';
  if ($isDir) {
    echo '<a class="folder" href="?dir='.urlencode($f).'">'.$icon.' '.$nameHtml.'</a>';
  } else {
    if (in_array($ext, $imgT)) {
      echo '<a class="file img-link" href="#" data-img="'.htmlspecialchars($url).'">'.$icon.' '.$nameHtml.'</a>';
    } elseif (in_array($ext, $textT)) {
      echo '<a class="file text-link" href="#" data-file="?view='.urlencode($f).'">'.$icon.' '.$nameHtml.'</a>';
    } else {
      echo '<a class="file" href="'.htmlspecialchars($url).'" download>'.$icon.' '.$nameHtml.'</a>';
    }
  }
  echo '</td><td class="size">'.$sizeText.'</td><td class="date">'.$mtimeText.'</td></tr>';
}
?>
  </tbody>
</table>
</div>

<!-- Hover image preview -->
<div class="preview" id="preview"><img src="" alt=""></div>

<!-- Text modal -->
<div class="modal" id="textModal">
  <button class="modal-copy" id="modalCopy"><i class="fa-regular fa-copy"></i> Copy text</button>
  <button class="modal-copy" id="modalDownloadText"><i class="fa-solid fa-download"></i> Download</button>
  <button class="modal-close" id="modalClose">✖</button>
  <div class="modal-content" id="modalContent">Loading...</div>
</div>

<!-- Image modal -->
<div class="modal" id="imgModal">
  <button class="modal-copy" id="imgCopy"><i class="fa-regular fa-copy"></i> Copy image URL</button>
  <button class="modal-copy" id="imgDownload"><i class="fa-solid fa-download"></i> Download</button>
  <button class="modal-close" id="imgClose">✖</button>
  <div class="img-nav" id="imgPrev"><i class="fa-solid fa-chevron-left"></i></div>
  <div class="img-nav" id="imgNext"><i class="fa-solid fa-chevron-right"></i></div>
  <div class="modal-content" id="imgContent"><img id="imgFull" src="" alt=""></div>
</div>

<script>
/* ---------------- Hover preview ---------------- */
const preview=document.getElementById('preview'),imgEl=preview.querySelector('img');
document.querySelectorAll('.img-link').forEach(link=>{
  link.addEventListener('mouseenter',()=>{imgEl.src=link.dataset.img;preview.style.display='block';requestAnimationFrame(()=>preview.classList.add('show'));});
  link.addEventListener('mousemove',e=>{preview.style.left=(e.pageX+20)+'px';preview.style.top=(e.pageY+20)+'px';});
  link.addEventListener('mouseleave',()=>{preview.classList.remove('show');setTimeout(()=>{if(!preview.classList.contains('show'))preview.style.display='none';},250);});
});

/* ---------------- Text modal ---------------- */
const textModal=document.getElementById('textModal'),
      textContent=document.getElementById('modalContent'),
      textCopy=document.getElementById('modalCopy'),
      textClose=document.getElementById('modalClose');
document.querySelectorAll('.text-link').forEach(l=>{
  l.addEventListener('click',e=>{
    e.preventDefault();
    document.querySelectorAll('.text-link').forEach(el=>el.removeAttribute('data-active'));
    l.setAttribute('data-active','true');
    fetch(l.dataset.file).then(r=>r.text()).then(t=>{
      textContent.textContent=t;
      textModal.style.display='flex';
      requestAnimationFrame(()=>textModal.classList.add('show'));
    }).catch(()=>{
      textContent.textContent='Failed to open file.';
      textModal.style.display='flex';
      requestAnimationFrame(()=>textModal.classList.add('show'));
    });
  });
});
textClose.onclick=()=>{textModal.classList.remove('show');setTimeout(()=>textModal.style.display='none',350);};
textCopy.onclick=()=>{navigator.clipboard.writeText(textContent.textContent).then(()=>{
  textCopy.innerHTML='<i class="fa-solid fa-check"></i> Copied!';
  setTimeout(()=>textCopy.innerHTML='<i class="fa-regular fa-copy"></i> Copy text',1500);
});};

/* ---------------- Download buttons ---------------- */
const textDownload=document.getElementById('modalDownloadText');
if(textDownload){
  textDownload.onclick=()=>{
    const current=document.querySelector('.text-link[data-active="true"]');
    if(current){
      const a=document.createElement('a');
      a.href=current.dataset.file+'&download=1';
      a.click();
    }
  };
}

const imgDownload=document.getElementById('imgDownload');
if(imgDownload){
  imgDownload.onclick=()=>{
    if(!imgCopy.dataset.url)return;
    const a=document.createElement('a');
    a.href=imgCopy.dataset.url;
    a.download='';
    a.click();
  };
}

/* ---------------- Image modal ---------------- */
const imgModal=document.getElementById('imgModal'),
      imgFull=document.getElementById('imgFull'),
      imgClose=document.getElementById('imgClose'),
      imgCopy=document.getElementById('imgCopy'),
      imgPrev=document.getElementById('imgPrev'),
      imgNext=document.getElementById('imgNext');
const imgLinks=Array.from(document.querySelectorAll('.img-link'));
let currentIndex=-1;
function resolveAbsoluteUrl(url){try{return new URL(url,window.location.href).href;}catch(e){return url;}}
function showImageAt(i){
  if(imgLinks.length===0)return;
  currentIndex=(i+imgLinks.length)%imgLinks.length;
  const url=imgLinks[currentIndex].dataset.img;
  imgFull.src=url;
  imgCopy.dataset.url=url;
  new Image().src=imgLinks[(currentIndex+1)%imgLinks.length].dataset.img;
  new Image().src=imgLinks[(currentIndex-1+imgLinks.length)%imgLinks.length].dataset.img;
  if(imgModal.style.display!=='flex'){
    imgModal.style.display='flex';
    requestAnimationFrame(()=>imgModal.classList.add('show'));
  }
}
imgLinks.forEach((l,i)=>{l.dataset.index=i;l.addEventListener('click',e=>{e.preventDefault();showImageAt(i);});});
imgPrev.onclick=e=>{e.stopPropagation();showImageAt(currentIndex-1);};
imgNext.onclick=e=>{e.stopPropagation();showImageAt(currentIndex+1);};
imgClose.onclick=()=>{imgModal.classList.remove('show');setTimeout(()=>imgModal.style.display='none',350);};
imgCopy.onclick=()=>{const abs=resolveAbsoluteUrl(imgCopy.dataset.url||'');navigator.clipboard.writeText(abs).then(()=>{
  imgCopy.innerHTML='<i class="fa-solid fa-check"></i> Copied!';
  setTimeout(()=>imgCopy.innerHTML='<i class="fa-regular fa-copy"></i> Copy image URL',1500);
});};

/* ---------------- Sorting ---------------- */
const tbody=document.getElementById('fileTbody'),
      rows=Array.from(tbody.querySelectorAll('tr.row-item')),
      state={key:'name',dir:'asc'},
      heads=Array.from(document.querySelectorAll('th.sortable'));
function compare(a,b,dir){if(a<b)return dir==='asc'?-1:1;if(a>b)return dir==='asc'?1:-1;return 0;}
function sortRows(k,d){
  const folders=[],files=[];
  rows.forEach(tr=>(tr.dataset.type==='dir'?folders:files).push(tr));
  const getVal=tr=>k==='name'?tr.dataset.name:k==='size'?+tr.dataset.size:k==='date'?+tr.dataset.date:tr.dataset.name;
  folders.sort((a,b)=>compare(getVal(a),getVal(b),d));
  files.sort((a,b)=>compare(getVal(a),getVal(b),d));
  const frag=document.createDocumentFragment();
  [...folders,...files].forEach(tr=>frag.appendChild(tr));
  tbody.appendChild(frag);
}
function updateArrows(){
  heads.forEach(th=>{
    const a=th.querySelector('.arrow');
    a.textContent='';
    th.classList.remove('active');
  });
  const active=heads.find(h=>h.dataset.sort===state.key);
  if(active){
    active.classList.add('active');
    active.querySelector('.arrow').textContent=state.dir==='asc'?'▲':'▼';
  }
}
heads.forEach(th=>{
  th.addEventListener('click',()=>{
    const k=th.dataset.sort;
    state.dir=(state.key===k)?(state.dir==='asc'?'desc':'asc'):'asc';
    state.key=k;
    sortRows(state.key,state.dir);
    updateArrows();
  });
});
sortRows(state.key,state.dir);
updateArrows();
</script>
</body>
</html>
