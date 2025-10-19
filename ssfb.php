<?php
// ==================================
//  Super Simple File Browser v1.0
//  "Nord Dark Style"
//  Smooth modals & previews + Image Modal + Gallery Nav
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
    header('Content-Type:text/plain;charset=utf-8');
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
<style>
:root{
  --bg-main:#2e3440;--bg-dark:#232831;--bg-hover:#3b4252;--text:#eceff4;--text-dim:#d8dee9;
  --accent:#81a1c1;--accent2:#5e81ac;--yellow:#ebcb8b;--green:#a3be8c;--border:#434c5e;
}
*{box-sizing:border-box}
body{font-family:'JetBrains Mono',monospace;background:var(--bg-main);color:var(--text);margin:0;overflow-x:hidden;}
header{background:linear-gradient(90deg,var(--accent2),var(--accent));padding:14px 22px;color:#fff;font-size:1.2em;
display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 6px rgba(0,0,0,.3);}
.container{padding:10px 20px 40px;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{padding:10px 16px;border-bottom:1px solid var(--border);}
th{background:var(--bg-dark);color:var(--text-dim);font-weight:500;text-align:left;user-select:none;cursor:pointer;position:relative;}
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
.icon{width:20px;text-align:center;margin-right:10px;}

/* Hover image preview */
.preview{position:absolute;display:none;opacity:0;transform:scale(.95);
border:1px solid var(--border);background:var(--bg-dark);padding:5px;border-radius:6px;z-index:100;
box-shadow:0 4px 12px rgba(0,0,0,.4);transition:opacity .25s ease,transform .25s ease;}
.preview.show{display:block;opacity:1;transform:scale(1);}
.preview img{max-width:300px;max-height:300px;border-radius:4px;}

/* Modal base */
.modal{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);
display:none;align-items:center;justify-content:center;opacity:0;transition:opacity .35s ease;z-index:200;}
.modal.show{display:flex;opacity:1;}
.modal-content{background:var(--bg-dark);color:var(--text);padding:20px;border-radius:8px;max-width:80%;max-height:80%;
overflow:auto;white-space:pre-wrap;font-family:'JetBrains Mono',monospace;box-shadow:0 0 10px rgba(0,0,0,.6);
transform:translateY(-20px);transition:transform .35s ease;}
.modal.show .modal-content{transform:translateY(0);}
.modal-close,.modal-copy{position:absolute;top:20px;font-size:16px;cursor:pointer;color:#fff;background:var(--accent2);
border:none;padding:8px 14px;border-radius:5px;transition:background .2s;}
.modal-close:hover,.modal-copy:hover{background:var(--accent);}
.modal-close{right:30px;} .modal-copy{right:120px;}

/* Image modal specifics */
#imgContent{text-align:center;white-space:normal;}
#imgFull{max-width:100%;max-height:75vh;border-radius:6px;}

/* Gallery navigation buttons */
.img-nav{
  position:absolute;top:50%;transform:translateY(-50%);
  background:rgba(0,0,0,.4);border:1px solid var(--border);border-radius:50%;
  width:44px;height:44px;display:flex;align-items:center;justify-content:center;
  cursor:pointer;user-select:none;font-size:18px;color:#fff;
  transition:background .2s,border-color .2s;
  z-index:999;
}
.img-nav:hover{background:rgba(0,0,0,.6);border-color:#666;}
#imgPrev{left:24px;} #imgNext{right:24px;}
@media (max-width:700px){
  .modal-copy{right:110px;} .modal-close{right:20px;}
  #imgPrev{left:12px;} #imgNext{right:12px;}
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
      <th class="sortable" data-sort="name"><span class="label">Name</span><span class="arrow" aria-hidden="true"></span></th>
      <th class="sortable size" data-sort="size"><span class="label">Size</span><span class="arrow" aria-hidden="true"></span></th>
      <th class="sortable date" data-sort="date"><span class="label">Modified</span><span class="arrow" aria-hidden="true"></span></th>
    </tr>
  </thead>
  <tbody id="fileTbody">
<?php
// Parent directory link (not part of sort dataset)
if ($path !== $root) {
  $p = dirname($path);
  echo '<tr class="static-parent"><td colspan="3"><a class="folder" href="?dir=' . urlencode($p) . '"><i class="fa-solid fa-arrow-up"></i> [Parent Directory]</a></td></tr>';
}

$textT     = ['txt','log','cfg','ini','json','yml','yaml','md','xml','html','css','js'];
$imgT      = ['jpg','jpeg','png','gif','webp','bmp'];
$skipFiles = ['index.php','ftp.php','ssfb.php']; // exclude self files

foreach ($items as $it) {
  if (in_array($it, $skipFiles)) continue;

  $f = $path . DIRECTORY_SEPARATOR . $it;
  if (pathinfo($it, PATHINFO_EXTENSION) === 'php') continue;

  $rel = substr($f, strlen($root));
  $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
  $url = $baseUrl . $rel;

  $name      = $it;
  $nameHtml  = htmlspecialchars($it);
  $mtime     = @filemtime($f) ?: 0;
  $mtimeText = $mtime ? date('Y-m-d H:i:s', $mtime) : '';
  $isDir     = is_dir($f);
  $sizeBytes = ($isDir ? 0 : (@filesize($f) ?: 0));
  $sizeText  = $isDir ? '—' : humanSize($sizeBytes);
  $ext       = strtolower(pathinfo($it, PATHINFO_EXTENSION));

  $icon = '<i class="fa-solid fa-file-lines"></i>';
  if ($isDir)                                      $icon = '<i class="fa-solid fa-folder"></i>';
  elseif (in_array($ext, ['zip','tar','gz','rar','7z','iso'])) $icon = '<i class="fa-solid fa-file-zipper"></i>';
  elseif (in_array($ext, ['mp4','mkv','avi','mov']))           $icon = '<i class="fa-solid fa-file-video"></i>';
  elseif (in_array($ext, ['mp3','wav','ogg']))                 $icon = '<i class="fa-solid fa-file-audio"></i>';
  elseif (in_array($ext, $imgT))                               $icon = '<i class="fa-solid fa-file-image"></i>';
  elseif (in_array($ext, $textT))                              $icon = '<i class="fa-solid fa-file-code"></i>';

  $typeClass = $isDir ? 'folder' : 'file';

  // Each row carries data-* attributes used for client-side sorting
  echo '<tr class="row-item" data-type="'.($isDir?'dir':'file').'" data-name="'.htmlspecialchars(mb_strtolower($name)).'" data-size="'.$sizeBytes.'" data-date="'.$mtime.'">';
  echo   '<td>';
  if ($isDir) {
    echo     '<a class="folder" href="?dir='.urlencode($f).'">'.$icon.' '.$nameHtml.'</a>';
  } else {
    if (in_array($ext, $imgT)) {
      echo   '<a class="file img-link" href="#" data-img="'.htmlspecialchars($url).'">'.$icon.' '.$nameHtml.'</a>';
    } elseif (in_array($ext, $textT)) {
      echo   '<a class="file text-link" href="#" data-file="?view='.urlencode($f).'">'.$icon.' '.$nameHtml.'</a>';
    } else {
      echo   '<a class="file" href="'.htmlspecialchars($url).'" download>'.$icon.' '.$nameHtml.'</a>';
    }
  }
  echo   '</td>';
  echo   '<td class="size">'.$sizeText.'</td>';
  echo   '<td class="date">'.$mtimeText.'</td>';
  echo '</tr>';
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
  <button class="modal-close" id="modalClose">✖</button>
  <div class="modal-content" id="modalContent">Loading...</div>
</div>

<!-- Image modal + gallery controls -->
<div class="modal" id="imgModal">
  <button class="modal-copy" id="imgCopy"><i class="fa-regular fa-copy"></i> Copy image URL</button>
  <button class="modal-close" id="imgClose">✖</button>
  <div class="img-nav" id="imgPrev" title="Previous (←)"><i class="fa-solid fa-chevron-left"></i></div>
  <div class="img-nav" id="imgNext" title="Next (→)"><i class="fa-solid fa-chevron-right"></i></div>
  <div class="modal-content" id="imgContent">
    <img id="imgFull" src="" alt="">
  </div>
</div>

<script>
/* ---------------- Hover image preview ---------------- */
const preview = document.getElementById('preview'),
      imgEl   = preview.querySelector('img');
document.querySelectorAll('.img-link').forEach(link=>{
  link.addEventListener('mouseenter',()=>{
    imgEl.src = link.dataset.img;
    preview.style.display='block';
    requestAnimationFrame(()=>preview.classList.add('show'));
  });
  link.addEventListener('mousemove',e=>{
    preview.style.left=(e.pageX+20)+'px';
    preview.style.top =(e.pageY+20)+'px';
  });
  link.addEventListener('mouseleave',()=>{
    preview.classList.remove('show');
    setTimeout(()=>{ if(!preview.classList.contains('show')) preview.style.display='none'; },250);
  });
});

/* ---------------- Text modal ---------------- */
const textModal   = document.getElementById('textModal'),
      textContent = document.getElementById('modalContent'),
      textCopy    = document.getElementById('modalCopy'),
      textClose   = document.getElementById('modalClose');
document.querySelectorAll('.text-link').forEach(l=>{
  l.addEventListener('click',e=>{
    e.preventDefault();
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
textCopy.onclick =()=>{navigator.clipboard.writeText(textContent.textContent).then(()=>{
  textCopy.innerHTML='<i class="fa-solid fa-check"></i> Copied!';
  setTimeout(()=>textCopy.innerHTML='<i class="fa-regular fa-copy"></i> Copy text',1500);
});};

/* ---------------- Image modal + gallery ---------------- */
const imgModal=document.getElementById('imgModal'),
      imgFull =document.getElementById('imgFull'),
      imgClose=document.getElementById('imgClose'),
      imgCopy =document.getElementById('imgCopy'),
      imgPrev =document.getElementById('imgPrev'),
      imgNext =document.getElementById('imgNext');

const imgLinks=Array.from(document.querySelectorAll('.img-link'));
let currentIndex=-1;

function resolveAbsoluteUrl(url){
  try { return new URL(url, window.location.href).href; } catch(e){ return url; }
}
function showImageAt(index){
  if(imgLinks.length===0) return;
  currentIndex=(index+imgLinks.length)%imgLinks.length;
  const url=imgLinks[currentIndex].dataset.img;
  imgFull.src=url;
  imgCopy.dataset.url=url;

  // Preload neighbors for a smoother gallery
  new Image().src=imgLinks[(currentIndex+1)%imgLinks.length].dataset.img;
  new Image().src=imgLinks[(currentIndex-1+imgLinks.length)%imgLinks.length].dataset.img;

  if(imgModal.style.display!=='flex'){
    imgModal.style.display='flex';
    requestAnimationFrame(()=>imgModal.classList.add('show'));
  }
}
imgLinks.forEach((link,idx)=>{
  link.dataset.index=idx;
  link.addEventListener('click',e=>{e.preventDefault();showImageAt(idx);});
});
imgPrev.addEventListener('click',e=>{e.stopPropagation();showImageAt(currentIndex-1);});
imgNext.addEventListener('click',e=>{e.stopPropagation();showImageAt(currentIndex+1);});
imgClose.onclick=()=>{imgModal.classList.remove('show');setTimeout(()=>imgModal.style.display='none',350);};
imgCopy.onclick =()=>{const abs=resolveAbsoluteUrl(imgCopy.dataset.url||'');navigator.clipboard.writeText(abs).then(()=>{
  imgCopy.innerHTML='<i class="fa-solid fa-check"></i> Copied!';setTimeout(()=>imgCopy.innerHTML='<i class="fa-regular fa-copy"></i> Copy image URL',1500);
});};
window.addEventListener('keydown',e=>{
  const anyOpen=document.querySelector('.modal.show');
  if(!anyOpen) return;
  if(e.key==='Escape'){
    document.querySelectorAll('.modal.show').forEach(m=>{m.classList.remove('show');setTimeout(()=>m.style.display='none',350);});
  } else if(anyOpen===imgModal){
    if(e.key==='ArrowLeft')  showImageAt(currentIndex-1);
    if(e.key==='ArrowRight') showImageAt(currentIndex+1);
  }
});
[imgModal,textModal].forEach(m=>{
  m.addEventListener('click',e=>{
    const content=m.querySelector('.modal-content');
    if(
      !content.contains(e.target) &&
      !e.target.closest('.img-nav') &&
      !e.target.classList.contains('modal-copy') &&
      !e.target.classList.contains('modal-close')
    ){
      m.classList.remove('show'); setTimeout(()=>m.style.display='none',350);
    }
  });
});

/* ---------------- Client-side sorting (folders first) ---------------- */
const tbody  = document.getElementById('fileTbody');
const rows   = Array.from(tbody.querySelectorAll('tr.row-item')); // skip parent row
const state  = { key:'name', dir:'asc' }; // default: Name asc (A→Z)
const heads  = Array.from(document.querySelectorAll('th.sortable'));

function compare(aVal, bVal, dir){
  if (aVal < bVal) return dir==='asc' ? -1 : 1;
  if (aVal > bVal) return dir==='asc' ?  1 : -1;
  return 0;
}
function sortRows(key, dir){
  // Split into folders & files so folders are always on top
  const folders = [], files = [];
  rows.forEach(tr => (tr.dataset.type === 'dir' ? folders : files).push(tr));

  const getVal = (tr) => {
    if (key === 'name') return tr.dataset.name;
    if (key === 'size') return Number(tr.dataset.size) || 0;
    if (key === 'date') return Number(tr.dataset.date) || 0;
    return tr.dataset.name;
  };

  folders.sort((a,b)=>compare(getVal(a), getVal(b), dir));
  files.sort((a,b)=>compare(getVal(a), getVal(b), dir));

  // Re-append in new order (keeps Parent Directory row untouched)
  const frag = document.createDocumentFragment();
  [...folders, ...files].forEach(tr => frag.appendChild(tr));
  tbody.appendChild(frag);
}
function updateHeaderArrows(){
  heads.forEach(th=>{
    const arrow = th.querySelector('.arrow');
    arrow.textContent = '';
    th.classList.remove('active');
  });
  const active = heads.find(h => h.dataset.sort === state.key);
  if (active) {
    active.classList.add('active');
    const arrow = active.querySelector('.arrow');
    arrow.textContent = state.dir === 'asc' ? '▲' : '▼';
  }
}
heads.forEach(th=>{
  th.addEventListener('click', ()=>{
    const key = th.dataset.sort;
    // toggle if same key, otherwise default to asc
    state.dir = (state.key === key) ? (state.dir === 'asc' ? 'desc' : 'asc') : 'asc';
    state.key = key;
    sortRows(state.key, state.dir);
    updateHeaderArrows();
  });
});

// Initial default sort: folders A→Z, files A→Z
sortRows(state.key, state.dir);
updateHeaderArrows();
</script>
</body>
</html>
