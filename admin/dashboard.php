<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) {
    header('Location: login.php'); exit;
}
$pdo = get_db();

$news = $pdo->query('SELECT * FROM news ORDER BY date_posted DESC')->fetchAll();
$docs = $pdo->query('SELECT * FROM documents ORDER BY uploaded_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{background:#f8fafc}</style>
</head>
<body class="p-6">
  <div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Admin Dashboard</h1>
      <div class="space-x-2">
        <a href="logout.php" class="text-sm text-red-600">Logout</a>
        <a href="/" class="text-sm text-gray-600">View site</a>
      </div>
    </div>

    <section class="mb-6">
      <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">ข่าวประชาสัมพันธ์</h2>
        <a href="news_form.php" class="bg-blue-600 text-white px-3 py-1 rounded">เพิ่มข่าว</a>
      </div>
      <div class="mt-3 bg-white rounded shadow p-3">
        <table class="w-full text-sm">
          <thead class="text-left text-gray-500"><tr><th>id</th><th>title</th><th>date</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($news as $n): ?>
            <tr class="border-t"><td><?php echo e($n['id']); ?></td><td><?php echo e($n['title']); ?></td><td><?php echo e($n['date_posted']); ?></td>
              <td class="text-right"><a href="news_form.php?id=<?php echo $n['id']; ?>" class="text-blue-600 mr-2">edit</a><a href="news_delete.php?id=<?php echo $n['id']; ?>" class="text-red-600">delete</a></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section>
      <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">เอกสาร</h2>
        <a href="docs_form.php" class="bg-blue-600 text-white px-3 py-1 rounded">เพิ่มเอกสาร</a>
      </div>
      <div class="mt-3">
        <button id="testUploadBtn" class="bg-green-600 text-white px-3 py-1 rounded">ทดสอบอัปโหลดไปยัง Google Drive (API)</button>
        <div id="testUploadArea" class="mt-3 hidden">
          <div id="testUploadProgress" class="w-full bg-gray-100 rounded h-3 overflow-hidden"><div id="testUploadBar" style="width:0%" class="h-3 bg-green-500"></div></div>
          <div id="testUploadLog" class="mt-2 p-2 bg-black text-white text-xs font-mono h-40 overflow-auto"></div>
        </div>
      </div>
      <div class="mt-3 bg-white rounded shadow p-3">
        <table class="w-full text-sm">
          <thead class="text-left text-gray-500"><tr><th>id</th><th>title</th><th>uploaded</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($docs as $d): ?>
            <tr class="border-t"><td><?php echo e($d['id']); ?></td><td><?php echo e($d['title']); ?></td><td><?php echo e($d['uploaded_at']); ?></td>
              <td class="text-right"><a href="docs_form.php?id=<?php echo $d['id']; ?>" class="text-blue-600 mr-2">edit</a><a href="docs_delete.php?id=<?php echo $d['id']; ?>" class="text-red-600">delete</a></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
  <script>
    (function(){
      const btn = document.getElementById('testUploadBtn');
      const area = document.getElementById('testUploadArea');
      const bar = document.getElementById('testUploadBar');
      const log = document.getElementById('testUploadLog');
      function appendLog(txt){ log.innerText += txt + "\n"; log.scrollTop = log.scrollHeight; }

      btn.addEventListener('click', async function(){
        btn.disabled = true; btn.innerText = 'กำลังทดสอบ...'; area.classList.remove('hidden');
        // simulated progress
        let pct = 0; const sim = setInterval(()=>{ pct = Math.min(95, pct + Math.floor(Math.random()*10)+5); bar.style.width = pct + '%'; }, 600);
        appendLog('[..] เริ่มต้นทดสอบอัปโหลด...');

        try{
          const res = await fetch('test_upload_api.php', { method: 'POST', credentials: 'same-origin' });
          const data = await res.json();
          clearInterval(sim); bar.style.width = '100%';
          appendLog('[..] การทดสอบเสร็จสิ้น. ลำดับเหตุการณ์:');
          if (Array.isArray(data.steps)){
            data.steps.forEach(s=>appendLog(s));
          }
          appendLog('----- RESULT -----');
          appendLog(JSON.stringify(data.result, null, 2));
        } catch (e){
          clearInterval(sim); appendLog('ERROR: ' + e.message);
        }
        btn.disabled = false; btn.innerText = 'ทดสอบอัปโหลดไปยัง Google Drive (API)';
      });
    })();
  </script>
  <!-- Drive sync notification -->
  <div id="driveSyncToast" class="hidden fixed right-4 bottom-4 bg-yellow-100 border-l-4 border-yellow-400 p-3 rounded shadow max-w-sm">
    <div class="flex items-start">
      <div class="flex-1">
        <div id="driveSyncMsg" class="text-sm text-yellow-800">มีความไม่ตรงกันระหว่าง Google Drive และฐานข้อมูล</div>
        <div class="text-xs text-gray-600 mt-1" id="driveSyncCounts"></div>
      </div>
      <div class="ml-3">
        <button id="driveSyncViewBtn" class="bg-yellow-600 text-white px-2 py-1 text-xs rounded">ดู</button>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="driveSyncModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded shadow max-w-3xl w-full max-h-[80vh] overflow-auto p-4">
      <div class="flex justify-between items-center mb-3">
        <h3 class="text-lg font-semibold">ตรวจสอบความตรงกันของไฟล์ Google Drive กับฐานข้อมูล</h3>
        <button id="driveSyncClose" class="text-gray-600">ปิด</button>
      </div>
      <div id="driveSyncContent" class="text-sm">
        <div id="onDriveNotInDBSection" class="mb-4">
          <h4 class="font-medium">ไฟล์บน Drive แต่ไม่มีในฐานข้อมูล</h4>
          <ul id="onDriveNotInDBList" class="list-disc ml-5 mt-2 text-xs text-gray-700"></ul>
        </div>
        <div id="inDBNotOnDriveSection">
          <h4 class="font-medium">รายการในฐานข้อมูล แต่ไฟล์หายจาก Drive</h4>
          <ul id="inDBNotOnDriveList" class="list-disc ml-5 mt-2 text-xs text-gray-700"></ul>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      async function checkDriveSync(){
        try{
          const res = await fetch('check_drive_sync.php', { credentials: 'same-origin' });
          if (!res.ok) return;
          const j = await res.json();
          if (!j.ok) return;
          const c = j.counts || {};
          if ((c.onDriveNotInDB||0) > 0 || (c.inDBNotOnDrive||0) > 0){
            document.getElementById('driveSyncCounts').innerText = `${c.onDriveNotInDB} ไฟล์บน Drive ที่ไม่มีใน DB · ${c.inDBNotOnDrive} รายการใน DB ที่ไม่มีบน Drive`;
            document.getElementById('driveSyncToast').classList.remove('hidden');

            const viewBtn = document.getElementById('driveSyncViewBtn');
            viewBtn.addEventListener('click', ()=>{ openModalWithData(j); });
            document.getElementById('driveSyncClose').addEventListener('click', ()=>{ document.getElementById('driveSyncModal').classList.add('hidden'); });
          }
        }catch(e){ console.log('checkDriveSync error', e); }
      }

      function openModalWithData(j){
        const onDriveList = document.getElementById('onDriveNotInDBList');
        const inDbList = document.getElementById('inDBNotOnDriveList');
        onDriveList.innerHTML = '';
        inDbList.innerHTML = '';

        // Bulk action buttons
        const bulkContainer = document.createElement('div');
        bulkContainer.className = 'mb-3 flex gap-2';
        const importAllBtn = document.createElement('button'); importAllBtn.className = 'bg-green-600 text-white px-2 py-1 text-xs rounded'; importAllBtn.innerText = 'นำเข้าทั้งหมด (Import all)';
        const removeAllBtn = document.createElement('button'); removeAllBtn.className = 'bg-red-600 text-white px-2 py-1 text-xs rounded'; removeAllBtn.innerText = 'ลบทั้งหมดจาก DB (Remove all)';
        bulkContainer.appendChild(importAllBtn); bulkContainer.appendChild(removeAllBtn);
        // Insert bulk container at top of modal content
        const content = document.getElementById('driveSyncContent');
        if (content.firstChild !== bulkContainer) content.insertBefore(bulkContainer, content.firstChild);

        function setBtnState(btn, state){ btn.disabled = state; }

        async function callAction(payload){
          try{
            const res = await fetch('drive_sync_action.php', { method: 'POST', credentials: 'same-origin', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            return await res.json();
          } catch(e){ return { error: e.message }; }
        }

        // per-file import buttons
        (j.onDriveNotInDB||[]).forEach(f=>{
          const li = document.createElement('li');
          li.className = 'flex justify-between items-center';
          const left = document.createElement('div');
          const a = document.createElement('a'); a.href = f.webViewLink || '#'; a.target = '_blank'; a.innerText = f.name || f.id;
          left.appendChild(a);
          const right = document.createElement('div');
          const impBtn = document.createElement('button'); impBtn.className = 'ml-2 bg-green-500 text-white px-2 py-1 text-xs rounded'; impBtn.innerText = 'Import';
          const status = document.createElement('span'); status.className = 'ml-2 text-xs text-gray-600';
          impBtn.addEventListener('click', async ()=>{
            setBtnState(impBtn, true); status.innerText = '...';
            const r = await callAction({ action: 'import', drive_id: f.id, name: f.name, webViewLink: f.webViewLink });
            if (r && r.ok && r.results && r.results[f.id] && r.results[f.id].ok){ status.innerText = 'นำเข้าแล้ว (Imported)'; impBtn.remove(); }
            else {
              let msg = 'ผิดพลาด';
              if (r && r.error) msg += ': ' + r.error;
              else if (r && r.results && r.results[f.id] && r.results[f.id].msg) msg += ': ' + r.results[f.id].msg;
              else if (r && r.message) msg += ': ' + r.message;
              else if (typeof r === 'object') msg += ': ' + JSON.stringify(r);
              status.innerText = msg;
              setBtnState(impBtn, false);
            }
          });
          right.appendChild(impBtn); right.appendChild(status);
          li.appendChild(left); li.appendChild(right);
          onDriveList.appendChild(li);
        });

        // per-db remove buttons
        (j.inDBNotOnDrive||[]).forEach(r=>{
          const li = document.createElement('li');
          li.className = 'flex justify-between items-center';
          const left = document.createElement('div'); left.innerText = `${r.title} (doc_id=${r.doc_id})`;
          const right = document.createElement('div');
          const remBtn = document.createElement('button'); remBtn.className = 'ml-2 bg-red-500 text-white px-2 py-1 text-xs rounded'; remBtn.innerText = 'Remove';
          const status = document.createElement('span'); status.className = 'ml-2 text-xs text-gray-600';
          remBtn.addEventListener('click', async ()=>{
            setBtnState(remBtn, true); status.innerText = '...';
            const res = await callAction({ action: 'remove', doc_id: r.doc_id });
            if (res && res.ok && res.results && res.results[r.doc_id] && res.results[r.doc_id].ok){ status.innerText = 'ลบแล้ว (Removed)'; remBtn.remove(); }
            else {
              let msg = 'ผิดพลาด';
              if (res && res.error) msg += ': ' + res.error;
              else if (res && res.results && res.results[r.doc_id] && res.results[r.doc_id].msg) msg += ': ' + res.results[r.doc_id].msg;
              else if (res && res.message) msg += ': ' + res.message;
              else if (typeof res === 'object') msg += ': ' + JSON.stringify(res);
              status.innerText = msg; setBtnState(remBtn, false);
            }
          });
          right.appendChild(remBtn); right.appendChild(status);
          li.appendChild(left); li.appendChild(right);
          inDbList.appendChild(li);
        });

        // bulk actions
        importAllBtn.addEventListener('click', async ()=>{
          importAllBtn.disabled = true; importAllBtn.innerText = 'กำลังนำเข้า...';
          const ids = (j.onDriveNotInDB||[]).map(f=>f.id);
          if (ids.length === 0){ importAllBtn.innerText = 'ไม่มีรายการนำเข้า'; return; }
          const res = await callAction({ action: 'import_all', drive_ids: ids });
          if (res && res.ok){ importAllBtn.innerText = 'นำเข้าเสร็จแล้ว'; setTimeout(()=>importAllBtn.remove(), 1200); }
          else { importAllBtn.innerText = 'ผิดพลาด: ' + (res && (res.error || res.message) ? (res.error || res.message) : JSON.stringify(res)); importAllBtn.disabled = false; }
        });

        removeAllBtn.addEventListener('click', async ()=>{
          removeAllBtn.disabled = true; removeAllBtn.innerText = 'กำลังลบ...';
          const ids = (j.inDBNotOnDrive||[]).map(r=>r.doc_id);
          if (ids.length === 0){ removeAllBtn.innerText = 'ไม่มีรายการลบ'; return; }
          const res = await callAction({ action: 'remove_all', doc_ids: ids });
          if (res && res.ok){ removeAllBtn.innerText = 'ลบเสร็จแล้ว'; setTimeout(()=>removeAllBtn.remove(), 1200); }
          else { removeAllBtn.innerText = 'ผิดพลาด: ' + (res && (res.error || res.message) ? (res.error || res.message) : JSON.stringify(res)); removeAllBtn.disabled = false; }
        });

        document.getElementById('driveSyncModal').classList.remove('hidden');
      }

      // run on load (delay slightly so page finishes rendering)
      setTimeout(checkDriveSync, 800);
    })();
  </script>
</body>
</html>
