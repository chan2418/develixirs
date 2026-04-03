<?php
// admin/layout/header.php
// Polished, professional header with notification slide-over and profile dropdown
// Dev note: Tailwind CDN used for quick styling — replace with a build pipeline in production.

if (session_status() === PHP_SESSION_NONE) session_start();
$adminName  = htmlspecialchars($_SESSION['admin_name']  ?? 'Administrator', ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars($_SESSION['admin_email'] ?? '', ENT_QUOTES, 'UTF-8');
$pageTitle  = htmlspecialchars($page_title ?? 'DEVELIXIR Admin', ENT_QUOTES, 'UTF-8');

// Use uploaded screenshot path (tool will map local path to a served URL)
$avatar_src = 'https://ui-avatars.com/api/?name=' . urlencode($adminName) . '&background=0D8ABC&color=fff';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= $pageTitle ?></title>

  <!-- Tailwind CDN (development). For production compile Tailwind and serve static CSS. -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <style>
    /* Base */
    body { background: #f4f6f9; }
    .sidebar-link:hover { background:#f3f4f6; }
    .sidebar-link.active { background:#eef2ff; color:#4f46e5; font-weight:700; }

    /* Header specifics */
    header .icon-btn { width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; }
    header .icon-btn:hover { background:#f8fafc; }

    /* Notification slide-over */
    .notif-backdrop { position:fixed; inset:0; background:rgba(2,6,23,0.38); z-index:60; display:none; }
    .notif-backdrop.show { display:block; }
    .notif-panel {
      position:fixed; right:0; top:0; height:100vh; width:min(680px,100%); max-width:760px;
      background:#fff; z-index:70; transform:translateX(110%); transition:transform .18s ease-in-out;
      box-shadow:-28px 24px 60px rgba(2,6,23,0.10); border-top-left-radius:12px; border-bottom-left-radius:12px; overflow:hidden;
      display:flex; flex-direction:column;
    }
    .notif-panel.open { transform:translateX(0); }

    .notif-header { padding:18px; border-bottom:1px solid #eef2f6; display:flex; justify-content:space-between; align-items:center; gap:12px; }
    .notif-list { padding:12px; overflow:auto; flex:1; }
    .notif-empty { padding:36px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; color:#6b7280; }

    .notif-item {
      display:flex; gap:12px; padding:12px; border-radius:10px; background:#fff; border:1px solid #f1f5f9; align-items:flex-start;
    }
    .notif-item.unread { background:linear-gradient(180deg,#f8fbff 0,#ffffff 100%); border-color:#e6eef7; }

    /* Profile dropdown */
    .profile-dropdown {
      position:absolute; right:0; margin-top:8px; width:220px; background:#fff; border-radius:10px;
      box-shadow:0 14px 40px rgba(2,6,23,0.08); border:1px solid #eef2f7; z-index:80; display:none;
    }
    .profile-dropdown.open { display:block; }
    .profile-dropdown a { display:block; padding:10px 12px; color:#0f172a; font-weight:700; text-decoration:none; }
    .profile-dropdown a:hover { background:#f8fafc; }

    .notif-badge {
      position:absolute; top:-4px; right:-4px; min-width:18px; height:18px; padding:0 6px; display:inline-flex; align-items:center; justify-content:center;
      border-radius:999px; font-size:11px; font-weight:800; background:#ef4444; color:#fff;
    }

    @media (max-width:640px) {
      .notif-panel { width:100%; max-width:100%; border-radius:0; }
    }
  </style>
</head>
<body class="text-slate-800 antialiased">
<div class="flex min-h-screen w-full">

  <!-- Sidebar -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- Main column -->
  <div class="flex-1 flex flex-col min-w-0">

    <!-- Top header -->
    <header class="w-full bg-white border-b border-gray-200">
      <div class="max-w-[1400px] mx-auto px-6 py-3 flex items-center justify-between">

        <!-- left: title -->
        <div class="flex items-center gap-4">
          <h1 class="text-lg md:text-xl font-semibold text-slate-900 tracking-tight"><?= $pageTitle ?></h1>
          <?php if (!empty($page_subtitle)): ?>
            <div class="hidden md:block text-sm text-slate-500"><?= htmlspecialchars($page_subtitle) ?></div>
          <?php endif; ?>
        </div>

        <!-- right: actions -->
        <div class="flex items-center gap-3">

          <!-- search -->
          <button aria-label="Search" class="icon-btn p-2 rounded-md" title="Search">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </button>

          <!-- notification -->
          <div class="relative">
            <button id="notifToggleBtn" aria-haspopup="dialog" aria-expanded="false" title="Notifications" class="icon-btn p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-200">
              <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
              <span id="notifBadge" class="notif-badge" style="display:none">0</span>
            </button>
          </div>

          <!-- profile -->
          <div id="profileToggleWrap" class="relative">
            <button id="profileToggleBtn" aria-haspopup="true" aria-expanded="false" class="flex items-center gap-2 p-1 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-200" title="Account">
              <div class="w-9 h-9 rounded-full bg-gray-200 overflow-hidden">
                <img id="profileAvatar" src="<?= htmlspecialchars($avatar_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= $adminName ?>" class="w-full h-full object-cover">
              </div>
              <span class="hidden md:inline-block text-sm font-semibold"><?= $adminName ?></span>
            </button>

            <!-- dropdown -->
            <div id="profileDropdown" class="profile-dropdown" role="menu" aria-hidden="true">
              <div class="px-3 py-2 border-b border-gray-100">
                <div class="font-semibold"><?= $adminName ?></div>
                <?php if ($adminEmail): ?><div class="text-xs text-slate-500"><?= $adminEmail ?></div><?php endif; ?>
              </div>
              <a href="/admin/profile.php" role="menuitem">Profile</a>
              <a href="/admin/logout.php" role="menuitem">Logout</a>
            </div>
          </div>

        </div>
      </div>
    </header>

    <!-- Notifications slide-over -->
    <div id="notifBackdrop" class="notif-backdrop" aria-hidden="true"></div>
    <aside id="notifPanel" class="notif-panel" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Notifications panel">
      <div class="notif-header">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
          <div>
            <div class="font-semibold text-slate-900">Notifications</div>
            <div id="notifSub" class="text-sm text-slate-500">Loading…</div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button id="markAllRead" class="text-sm text-slate-500 hover:text-slate-900">Mark all read</button>
          <button id="closeNotif" aria-label="Close notifications" class="p-1 rounded-md hover:bg-gray-50">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <div id="notifContentArea" class="flex-1 overflow-auto">
        <div id="notifEmptyState" class="notif-empty" style="display:none">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="text-slate-400" style="width:48px;height:48px"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/></svg>
          <h3 class="mt-4 text-lg font-semibold text-slate-900">No notifications</h3>
          <p class="text-sm text-slate-500 mt-1">You're all caught up.</p>
        </div>

        <div id="notifList" class="notif-list" style="display:none"></div>
      </div>
    </aside>

    <!-- Content wrapper starts in included pages -->
    <main class="flex-1 p-6">
      <div class="max-w-[1400px] mx-auto">

<!-- header script: profile + notifications behavior -->
<script>
(function(){
  // Profile dropdown behavior
  const profileBtn = document.getElementById('profileToggleBtn');
  const profileDropdown = document.getElementById('profileDropdown');
  const profileWrap = document.getElementById('profileToggleWrap');

  function closeProfile() {
    if (!profileDropdown) return;
    profileDropdown.classList.remove('open');
    profileDropdown.setAttribute('aria-hidden','true');
    profileBtn && profileBtn.setAttribute('aria-expanded','false');
  }
  function toggleProfile(e) {
    e.stopPropagation();
    if (!profileDropdown) return;
    const open = profileDropdown.classList.toggle('open');
    profileDropdown.setAttribute('aria-hidden', String(!open));
    profileBtn && profileBtn.setAttribute('aria-expanded', String(open));
  }
  profileBtn && profileBtn.addEventListener('click', toggleProfile);

  document.addEventListener('click', function(e){
    if (!profileWrap) return;
    if (!profileWrap.contains(e.target)) closeProfile();
  });
  window.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeProfile();
  });

  // Notifications
  const notifBtn = document.getElementById('notifToggleBtn');
  const notifPanel = document.getElementById('notifPanel');
  const notifBackdrop = document.getElementById('notifBackdrop');
  const notifBadge = document.getElementById('notifBadge');
  const notifSub = document.getElementById('notifSub');
  const notifList = document.getElementById('notifList');
  const notifEmpty = document.getElementById('notifEmptyState');
  const markAllBtn = document.getElementById('markAllRead');
  const closeNotifBtn = document.getElementById('closeNotif');

  const apiUrl = '/admin/notifications_api.php'; // ensure this file exists

  function openNotif() {
    notifPanel.classList.add('open');
    notifBackdrop.classList.add('show');
    notifBtn && notifBtn.setAttribute('aria-expanded','true');
    loadNotifications();
  }
  function closeNotif() {
    notifPanel.classList.remove('open');
    notifBackdrop.classList.remove('show');
    notifBtn && notifBtn.setAttribute('aria-expanded','false');
  }

  function showEmpty() {
    if (notifList) notifList.style.display = 'none';
    if (notifEmpty) notifEmpty.style.display = 'flex';
  }

  function renderList(rows) {
    if (!notifList) return;
    notifEmpty && (notifEmpty.style.display = 'none');
    notifList.style.display = 'block';
    notifList.innerHTML = '';

    rows.forEach(r => {
      const wrap = document.createElement('div');
      wrap.className = 'notif-item' + ((r.is_read == 0) ? ' unread' : '');
      wrap.style.marginBottom = '10px';
      wrap.style.cursor = 'pointer';

      // left icon
      const left = document.createElement('div');
      left.style.minWidth = '48px';
      left.style.display = 'flex';
      left.style.alignItems = 'center';
      left.style.justifyContent = 'center';
      left.innerHTML = '<div style="width:40px;height:40px;border-radius:8px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:700;">🔔</div>';
      wrap.appendChild(left);

      // content
      const content = document.createElement('div');
      content.style.flex = '1';
      const title = document.createElement('div');
      title.textContent = r.title || 'Notification';
      title.style.fontWeight = '700';
      title.style.color = '#0f172a';
      const msg = document.createElement('div');
      msg.textContent = r.message || '';
      msg.style.color = '#475569';
      msg.style.marginTop = '6px';
      const meta = document.createElement('div');
      meta.innerHTML = (new Date(r.created_at || Date.now())).toLocaleString() + (r.url ? ' • <a href="'+escapeAttr(r.url)+'">Open</a>' : '');
      meta.style.marginTop = '8px';
      meta.style.color = '#94a3b8';
      meta.style.fontSize = '12px';
      content.appendChild(title);
      content.appendChild(msg);
      content.appendChild(meta);
      wrap.appendChild(content);

      // action: mark read
      const actions = document.createElement('div');
      const markBtn = document.createElement('button');
      markBtn.textContent = r.is_read == 1 ? 'Read' : 'Mark read';
      markBtn.style.background = 'transparent';
      markBtn.style.border = 'none';
      markBtn.style.color = '#0b76ff';
      markBtn.style.fontWeight = '700';
      markBtn.style.cursor = 'pointer';
      markBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        markRead(r.id, function(){
          wrap.remove();
          loadNotifications();
        });
      });
      actions.appendChild(markBtn);
      wrap.appendChild(actions);

      wrap.addEventListener('click', function(){
        if (r.url) window.location.href = r.url;
      });

      notifList.appendChild(wrap);
    });
  }

  // load notifications from API
  function loadNotifications() {
    if (notifSub) notifSub.textContent = 'Loading…';
    fetch(apiUrl, { cache: 'no-store' })
      .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(json => {
        if (!json || !json.ok) { if (notifSub) notifSub.textContent = 'Failed to load'; showEmpty(); return; }
        // Filter to show only unread
        const rows = (json.rows || []).filter(r => r.is_read == 0);
        const unread = json.unread || 0;
        if (notifBadge) {
          if (unread > 0) { notifBadge.style.display = 'inline-flex'; notifBadge.textContent = String(unread); }
          else { notifBadge.style.display = 'none'; }
        }
        if (notifSub) notifSub.textContent = unread + ' unread';
        if (!rows.length) { showEmpty(); return; }
        renderList(rows);
      })
      .catch(err => {
        console.error('Notifications load failed', err);
        if (notifSub) notifSub.textContent = 'Failed to load';
        showEmpty();
      });
  }

  function markRead(id, cb) {
    const fd = new FormData();
    fd.append('action', 'mark_read');
    fd.append('id', id);
    fetch(apiUrl, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(json => { if (cb) cb(json); })
      .catch(err => { console.error(err); if (cb) cb(null); });
  }

  // mark all
  markAllBtn && markAllBtn.addEventListener('click', function(){
    const fd = new FormData();
    fd.append('action', 'mark_all');
    fetch(apiUrl, { method: 'POST', body: fd })
      .then(r => r.json())
      .then(json => { loadNotifications(); })
      .catch(err => console.error(err));
  });

  // toggles & closers
  notifBtn && notifBtn.addEventListener('click', function(){ 
    if (!notifPanel) return;
    if (notifPanel.classList.contains('open')) closeNotif(); else openNotif();
  });
  closeNotifBtn && closeNotifBtn.addEventListener('click', closeNotif);
  notifBackdrop && notifBackdrop.addEventListener('click', closeNotif);
  window.addEventListener('keydown', function(e){ if (e.key === 'Escape') { closeNotif(); closeProfile(); } });

  // initial badge load (quiet)
  (function initialBadgeLoad(){
    fetch(apiUrl, { cache: 'no-store' })
      .then(r => r.json())
      .then(j => {
        if (!j || !j.ok) return;
        const unread = j.unread || 0;
        if (notifBadge) {
          if (unread > 0) { notifBadge.style.display = 'inline-flex'; notifBadge.textContent = String(unread); }
          else { notifBadge.style.display = 'none'; }
        }
      })
      .catch(()=>{});
  })();

  // helpers
  function escapeAttr(s){ return String(s||'').replace(/"/g,'&quot;'); }
})();
</script>