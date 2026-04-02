<?php session_start();
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'viewer'])) {
    header('Location: admin_login.php'); exit;
}
$userName = htmlspecialchars($_SESSION['name']);
$userRole = htmlspecialchars($_SESSION['role']);
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Pole - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--primary:#6c5ce7;--primary-dark:#5a4bd1;--secondary:#00cec9;--danger:#d63031;--warning:#fdcb6e;--success:#00b894;--dark:#2d3436;--darker:#1e272e;--light:#dfe6e9;--white:#fff;--bg:#0a0a1a;--card:#12122a;--card2:#1a1a3e;--sidebar-w:260px;--border:rgba(108,92,231,0.2);--text:#b2bec3;--text-light:#636e72;--green:#00b894;--yellow:#fdcb6e;--red:#d63031;--purple:#6c5ce7;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden;}
::-webkit-scrollbar{width:6px;} ::-webkit-scrollbar-track{background:var(--darker);} ::-webkit-scrollbar-thumb{background:var(--primary);border-radius:3px;}

.app{display:flex;min-height:100vh;}
.sidebar{width:var(--sidebar-w);background:var(--card);border-right:1px solid var(--border);position:fixed;top:0;left:0;height:100vh;overflow-y:auto;z-index:1000;transition:transform .3s;}
.sidebar-header{padding:20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.sidebar-header .logo-icon{width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--white);}
.sidebar-header .logo-text h3{color:var(--white);font-size:15px;}
.sidebar-header .logo-text span{color:var(--text-light);font-size:11px;}
.sidebar-nav{padding:15px 10px;}
.nav-section{margin-bottom:8px;}
.nav-section-title{padding:8px 15px;font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-light);font-weight:700;}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 15px;border-radius:10px;cursor:pointer;transition:all .2s;color:var(--text);font-size:13px;margin-bottom:2px;}
.nav-item:hover{background:rgba(108,92,231,0.1);color:var(--white);}
.nav-item.active{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:var(--white);box-shadow:0 4px 15px rgba(108,92,231,0.3);}
.nav-item i{width:20px;text-align:center;font-size:14px;}
.nav-item .badge{margin-left:auto;background:var(--danger);color:var(--white);padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;}
.main{margin-left:var(--sidebar-w);flex:1;min-height:100vh;}
.topbar{height:65px;background:var(--card);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 25px;position:sticky;top:0;z-index:500;}
.topbar-left{display:flex;align-items:center;gap:15px;}
.topbar-left .menu-toggle{display:none;background:none;border:none;color:var(--text);font-size:20px;cursor:pointer;}
.topbar-left h2{color:var(--white);font-size:18px;font-weight:700;}
.topbar-right{display:flex;align-items:center;gap:15px;}
.topbar-right .notif-btn{position:relative;background:var(--card2);border:1px solid var(--border);width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text);}
.topbar-right .notif-btn .dot{position:absolute;top:8px;right:8px;width:8px;height:8px;background:var(--danger);border-radius:50%;}
.user-menu{display:flex;align-items:center;gap:10px;cursor:pointer;padding:5px 12px;border-radius:10px;transition:background .2s;}
.user-menu:hover{background:var(--card2);}
.user-menu .avatar{width:35px;height:35px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-size:14px;}
.user-menu .user-info{line-height:1.3;}
.user-menu .user-info .name{color:var(--white);font-size:13px;font-weight:600;}
.user-menu .user-info .role{color:var(--text-light);font-size:11px;}
.content{padding:25px;}
.page{display:none;}
.page.active{display:block;animation:fadeIn .3s ease;}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:25px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px;transition:transform .2s,box-shadow .2s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(0,0,0,0.3);}
.stat-card .stat-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
.stat-card .stat-icon{width:45px;height:45px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.stat-card .stat-icon.green{background:rgba(0,184,148,0.15);color:var(--green);}
.stat-card .stat-icon.red{background:rgba(214,48,49,0.15);color:var(--red);}
.stat-card .stat-icon.yellow{background:rgba(253,203,110,0.15);color:var(--yellow);}
.stat-card .stat-icon.purple{background:rgba(108,92,231,0.15);color:var(--purple);}
.stat-card .stat-icon.blue{background:rgba(0,206,201,0.15);color:var(--secondary);}
.stat-card .stat-value{font-size:28px;font-weight:800;color:var(--white);margin-bottom:4px;}
.stat-card .stat-label{font-size:13px;color:var(--text-light);}

.card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px;margin-bottom:20px;}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;}
.card-header h3{color:var(--white);font-size:16px;font-weight:700;}
.table-wrapper{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
table th{padding:12px 15px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--text-light);border-bottom:1px solid var(--border);font-weight:700;}
table td{padding:12px 15px;font-size:13px;border-bottom:1px solid rgba(255,255,255,0.03);color:var(--text);}
table tr:hover td{background:rgba(108,92,231,0.03);}
.status-badge{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;}
.status-badge.normal,.status-badge.resolved,.status-badge.completed,.status-badge.active,.status-badge.approved{background:rgba(0,184,148,0.15);color:var(--green);}
.status-badge.medium,.status-badge.in_progress,.status-badge.in-progress,.status-badge.pending,.status-badge.assigned{background:rgba(253,203,110,0.15);color:var(--yellow);}
.status-badge.high,.status-badge.open,.status-badge.offline,.status-badge.rejected{background:rgba(214,48,49,0.15);color:var(--red);}
.status-badge.critical,.status-badge.escalated{background:rgba(108,92,231,0.15);color:var(--purple);}

.btn{padding:8px 16px;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:var(--white);}
.btn-success{background:var(--success);color:var(--white);}
.btn-danger{background:var(--danger);color:var(--white);}
.btn-warning{background:var(--warning);color:var(--dark);}
.btn-outline{background:transparent;border:1px solid var(--primary);color:var(--primary);}
.btn-outline:hover{background:var(--primary);color:var(--white);}
.btn-secondary{background:var(--card2);color:var(--text);border:1px solid var(--border);}

.filters-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center;}
.filters-bar input,.filters-bar select{padding:9px 14px;background:var(--card);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:13px;}
.filters-bar input:focus,.filters-bar select:focus{outline:none;border-color:var(--primary);}

.map-container{width:100%;height:500px;border-radius:14px;overflow:hidden;border:1px solid var(--border);}
#admin-map{width:100%;height:100%;}

.charts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;margin-bottom:25px;}
.chart-wrapper{height:280px;position:relative;}

.risk-bar{height:8px;border-radius:4px;background:var(--darker);overflow:hidden;margin-top:5px;}
.risk-bar .fill{height:100%;border-radius:4px;transition:width .5s;}
.risk-bar .fill.normal{background:var(--green);width:25%;}
.risk-bar .fill.medium{background:var(--yellow);width:50%;}
.risk-bar .fill.high{background:var(--red);width:75%;}
.risk-bar .fill.critical{background:var(--purple);width:100%;}

.toggle-switch{position:relative;width:50px;height:26px;}
.toggle-switch input{display:none;}
.toggle-switch label{position:absolute;top:0;left:0;width:100%;height:100%;background:var(--danger);border-radius:13px;cursor:pointer;transition:background .3s;}
.toggle-switch input:checked+label{background:var(--success);}
.toggle-switch label::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;background:var(--white);border-radius:50%;transition:transform .3s;}
.toggle-switch input:checked+label::after{transform:translateX(24px);}

.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:20000;opacity:0;pointer-events:none;transition:opacity .3s;}
.modal-overlay.active{opacity:1;pointer-events:all;}
.modal{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:30px;width:600px;max-width:95vw;max-height:85vh;overflow-y:auto;transform:scale(0.9);transition:transform .3s;}
.modal-overlay.active .modal{transform:scale(1);}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.modal-header h3{color:var(--white);font-size:18px;}
.modal-close{background:none;border:none;color:var(--text-light);font-size:20px;cursor:pointer;}
.form-group{margin-bottom:15px;}
.form-group label{display:block;font-size:13px;color:var(--text);margin-bottom:6px;font-weight:500;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;background:var(--darker);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:13px;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--primary);}

.profile-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-top:15px;}
.profile-stat{text-align:center;padding:12px;background:var(--card2);border-radius:10px;}
.profile-stat .val{font-size:22px;font-weight:800;color:var(--white);}
.profile-stat .lbl{font-size:11px;color:var(--text-light);margin-top:3px;}

.pulse{animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.5;}}
.loading{text-align:center;padding:40px;color:var(--text-light);}
.loading i{font-size:30px;margin-bottom:10px;display:block;}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .main{margin-left:0;}
  .topbar-left .menu-toggle{display:block;}
  .stats-grid{grid-template-columns:1fr 1fr;}
  .charts-grid{grid-template-columns:1fr;}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr;}
  .content{padding:15px;}
}

/* Toast notifications */
.toast-container{position:fixed;top:80px;right:25px;z-index:30000;display:flex;flex-direction:column;gap:10px;}
.toast{padding:14px 22px;border-radius:12px;color:var(--white);font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 30px rgba(0,0,0,0.4);animation:toastIn .4s ease,toastOut .4s ease 2.6s forwards;min-width:280px;}
.toast.success{background:linear-gradient(135deg,#00b894,#00a381);}
.toast.danger{background:linear-gradient(135deg,#d63031,#b71c1c);}
.toast.warning{background:linear-gradient(135deg,#fdcb6e,#f39c12);color:var(--dark);}
.toast.info{background:linear-gradient(135deg,#6c5ce7,#5a4bd1);}
.toast i{font-size:16px;}
@keyframes toastIn{from{opacity:0;transform:translateX(100px);}to{opacity:1;transform:translateX(0);}}
@keyframes toastOut{from{opacity:1;transform:translateX(0);}to{opacity:0;transform:translateX(100px);}}

/* Power buttons in tables */
table td .btn{padding:5px 12px;font-size:11px;}
table td .btn i{font-size:11px;}
table td .btn:disabled,.btn-disabled{opacity:0.3;cursor:not-allowed;pointer-events:none;}
.bulk-actions{display:flex;gap:10px;align-items:center;}

/* ESP32 Connection Widget */
.esp-connect-bar{display:flex;align-items:center;gap:12px;background:var(--card2);border:1px solid var(--border);border-radius:12px;padding:6px 16px;margin-right:8px;}
.esp-connect-bar label{font-size:12px;color:var(--text-light);font-weight:600;white-space:nowrap;}
.esp-connect-bar input[type="text"]{width:150px;padding:6px 12px;background:var(--darker);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:13px;font-family:monospace;box-sizing:border-box;}
.esp-connect-bar input[type="text"]:focus{outline:none;border-color:var(--primary);}
.esp-connect-bar .esp-connect-btn{all:unset;box-sizing:border-box;display:inline-flex;align-items:center;gap:6px;padding:7px 18px;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;transition:all .3s;color:var(--white);background:linear-gradient(135deg,#00b4db,#0083b0);white-space:nowrap;line-height:normal;}
.esp-connect-bar .esp-connect-btn:hover{filter:brightness(1.1);}
.esp-connect-bar .esp-connect-btn.connected{background:linear-gradient(135deg,var(--danger),#b71c1c);}
.esp-connect-bar .esp-connect-btn.busy{opacity:0.5;pointer-events:none;cursor:not-allowed;}
.esp-connect-bar .esp-status{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;white-space:nowrap;}
.esp-connect-bar .esp-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
.esp-connect-bar .esp-dot.off{background:var(--danger);}
.esp-connect-bar .esp-dot.on{background:var(--green);animation:pulse 2s infinite;}
@media(max-width:900px){.esp-connect-bar{display:none;}}

/* Dock Navigation Styles */
.dock-outer {
  margin: 0 0.5rem;
  display: flex;
  max-width: 100%;
  align-items: center;
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 999;
}

.dock-panel {
  position: relative;
  display: flex;
  align-items: flex-end;
  width: fit-content;
  gap: 1rem;
  border-radius: 1rem;
  background-color: #060010;
  border: 1px solid #222;
  padding: 0 0.5rem 0.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.dock-item {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background-color: #060010;
  border: 1px solid #222;
  box-shadow:
    0 4px 6px -1px rgba(0, 0, 0, 0.1),
    0 2px 4px -1px rgba(0, 0, 0, 0.06);
  cursor: pointer;
  outline: none;
  width: 50px;
  height: 50px;
  transition: all 0.3s ease;
  color: #b2bec3;
  text-decoration: none;
}

.dock-item:hover {
  background-color: #1a1a3e;
  border-color: #6c5ce7;
  transform: scale(1.1);
  box-shadow: 0 8px 20px rgba(108, 92, 231, 0.3);
}

.dock-item.active {
  background-color: #6c5ce7;
  border-color: #6c5ce7;
  color: #fff;
  box-shadow: 0 8px 25px rgba(108, 92, 231, 0.5);
}

.dock-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.dock-label {
  position: absolute;
  top: -1.8rem;
  left: 50%;
  width: fit-content;
  white-space: nowrap;
  border-radius: 0.375rem;
  border: 1px solid #222;
  background-color: #060010;
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  color: #fff;
  transform: translateX(-50%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.dock-item:hover .dock-label {
  opacity: 1;
}

@media (max-width: 600px) {
  .dock-panel {
    gap: 0.5rem;
    padding: 0 0.25rem 0.25rem;
  }
  .dock-item {
    width: 45px;
    height: 45px;
    font-size: 14px;
  }
  .dock-label {
    font-size: 0.65rem;
  }
}

</style>
</head>
<body>

<div class="modal-overlay" id="detailModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="detailModalTitle">Details</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <div id="detailModalBody"></div>
  </div>
</div>

<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo-icon"><i class="fas fa-broadcast-tower"></i></div>
      <div class="logo-text"><h3>SmartPole</h3><span>Admin Panel</span></div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-section-title">Main</div>
        <div class="nav-item active" onclick="showPage('dashboard',this)"><i class="fas fa-th-large"></i> Dashboard</div>
        <div class="nav-item" onclick="showPage('poles',this)"><i class="fas fa-broadcast-tower"></i> All Poles</div>
        <div class="nav-item" onclick="showPage('polesMap',this)"><i class="fas fa-map-marked-alt"></i> Poles Map <span class="badge" style="background:var(--primary)">Live</span></div>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Monitoring</div>
        <div class="nav-item" onclick="showPage('alerts',this)"><i class="fas fa-exclamation-triangle"></i> Alerts <span class="badge" id="alertBadge">0</span></div>
        <div class="nav-item" onclick="showPage('faultLogs',this)"><i class="fas fa-clipboard-list"></i> Fault Logs</div>
        <div class="nav-item" onclick="showPage('riskLevels',this)"><i class="fas fa-tachometer-alt"></i> Risk Levels</div>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Management</div>
        <div class="nav-item" onclick="showPage('workers',this)"><i class="fas fa-hard-hat"></i> Workers</div>
        <div class="nav-item" onclick="showPage('users',this)"><i class="fas fa-users-cog"></i> User Management</div>
        <div class="nav-item" onclick="showPage('complaints',this)"><i class="fas fa-comment-dots"></i> Complaints <span class="badge" id="complaintBadge">0</span></div>
        <div class="nav-item" onclick="showPage('materials',this)"><i class="fas fa-toolbox"></i> Material Requests</div>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Control</div>
        <div class="nav-item" onclick="showPage('cutoff',this)"><i class="fas fa-power-off"></i> Cutoff Control</div>
        <div class="nav-item" onclick="showPage('proof',this)"><i class="fas fa-camera"></i> Proof Verification</div>
        <div class="nav-item" onclick="showPage('accelerometer',this)"><i class="fas fa-wave-square"></i> Accelerometer <span class="badge" style="background:var(--secondary)">Live</span></div>
      </div>
    </nav>
  </aside>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
        <h2 id="pageTitle">Dashboard</h2>
      </div>
      <div class="topbar-right">
        <div class="esp-connect-bar" id="espBar">
          <label>Server IP</label>
          <input type="text" id="espIpInput" placeholder="e.g. 192.168.1.100" value="">
          <button class="esp-connect-btn" id="espConnectBtn" onclick="espToggleConnection()">
            <i class="fas fa-plug"></i> <span id="espBtnText">Connect</span>
          </button>
          <div class="esp-status">
            <span class="esp-dot off" id="espDot"></span>
            <span id="espStatusText">Disconnected</span>
          </div>
        </div>
        <div class="notif-btn" onclick="showPage('alerts',null)"><i class="fas fa-bell"></i><div class="dot pulse"></div></div>
        <div class="user-menu" onclick="logout()">
          <div class="avatar"><?= $userInitial ?></div>
          <div class="user-info"><div class="name"><?= $userName ?></div><div class="role"><?= ucfirst($userRole) ?></div></div>
          <i class="fas fa-sign-out-alt" style="color:var(--text-light);margin-left:8px;"></i>
        </div>
      </div>
    </div>

    <div class="content">

      <!-- DASHBOARD -->
      <div class="page active" id="page-dashboard">
        <div class="stats-grid" id="dashStats"></div>
        <div class="charts-grid">
          <div class="card"><div class="card-header"><h3>Pole Health Trend (7 Days)</h3></div><div class="chart-wrapper"><canvas id="healthChart"></canvas></div></div>
          <div class="card"><div class="card-header"><h3>Risk Distribution</h3></div><div class="chart-wrapper"><canvas id="riskChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><h3>Recent Alerts</h3><button class="btn btn-outline" onclick="showPage('alerts',null)">View All</button></div>
          <div class="table-wrapper"><table><thead><tr><th>Time</th><th>Pole</th><th>Type</th><th>Severity</th><th>Status</th></tr></thead><tbody id="recentAlertsBody"></tbody></table></div>
        </div>
      </div>

      <!-- ALL POLES -->
      <div class="page" id="page-poles">
        <div class="filters-bar">
          <input type="text" placeholder="🔍 Search poles..." id="poleSearch" oninput="loadPoles()">
          <select id="poleZoneFilter" onchange="loadPoles()"><option value="">All Zones</option><option value="north">North</option><option value="south">South</option><option value="east">East</option><option value="west">West</option></select>
          <select id="poleRiskFilter" onchange="loadPoles()"><option value="">All Risk</option><option value="normal">Normal</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
          <button class="btn btn-primary" onclick="openAddPoleModal()"><i class="fas fa-plus"></i> Add Pole</button>
        </div>
        <div class="card"><div class="table-wrapper"><table><thead><tr><th>Pole ID</th><th>Location</th><th>Zone</th><th>Vibration</th><th>Temp</th><th>Voltage</th><th>Risk</th><th>Worker</th><th>Power</th><th>Actions</th></tr></thead><tbody id="polesBody"></tbody></table></div></div>
      </div>

      <!-- POLES MAP -->
      <div class="page" id="page-polesMap">
        <div class="stats-grid" id="mapLegend" style="grid-template-columns:repeat(4,1fr);margin-bottom:15px;"></div>
        <div class="map-container"><div id="admin-map"></div></div>
      </div>

      <!-- ALERTS -->
      <div class="page" id="page-alerts">
        <div class="filters-bar">
          <input type="text" placeholder="🔍 Search alerts..." id="alertSearch" oninput="loadAlerts()">
          <select id="alertSevFilter" onchange="loadAlerts()"><option value="">All Severity</option><option value="critical">Critical</option><option value="high">High</option><option value="medium">Medium</option><option value="low">Low</option></select>
          <select id="alertStatusFilter" onchange="loadAlerts()"><option value="">All Status</option><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option></select>
        </div>
        <div class="card"><div class="table-wrapper"><table><thead><tr><th>Alert ID</th><th>Time</th><th>Pole</th><th>Type</th><th>Value</th><th>Threshold</th><th>Severity</th><th>Status</th><th>Actions</th></tr></thead><tbody id="alertsBody"></tbody></table></div></div>
      </div>

      <!-- FAULT LOGS -->
      <div class="page" id="page-faultLogs">
        <div class="card"><div class="card-header"><h3>Fault Logs</h3></div><div class="table-wrapper"><table><thead><tr><th>Log ID</th><th>Date</th><th>Pole</th><th>Type</th><th>Description</th><th>Severity</th><th>Resolved By</th><th>Status</th></tr></thead><tbody id="faultLogsBody"></tbody></table></div></div>
      </div>

      <!-- WORKERS -->
      <div class="page" id="page-workers">
        <div class="filters-bar">
          <input type="text" placeholder="🔍 Search workers..." id="workerSearch">
          <button class="btn btn-primary" onclick="openAddWorkerModal()"><i class="fas fa-plus"></i> Add Worker</button>
        </div>
        <div class="card"><div class="table-wrapper"><table><thead><tr><th>Worker ID</th><th>Name</th><th>Phone</th><th>Zone</th><th>Assigned Poles</th><th>Tasks Done</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead><tbody id="workersBody"></tbody></table></div></div>
      </div>

      <!-- USERS -->
      <div class="page" id="page-users">
        <div class="filters-bar">
          <input type="text" placeholder="🔍 Search users..." id="userSearch">
          <button class="btn btn-primary" onclick="openAddUserModal()"><i class="fas fa-plus"></i> Add User</button>
        </div>
        <div class="card"><div class="table-wrapper"><table><thead><tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead><tbody id="usersBody"></tbody></table></div></div>
      </div>

      <!-- RISK LEVELS -->
      <div class="page" id="page-riskLevels">
        <div class="stats-grid" id="riskStats"></div>
        <div class="card"><div class="card-header"><h3>Poles by Risk Level</h3></div><div class="table-wrapper"><table><thead><tr><th>Pole ID</th><th>Location</th><th>Zone</th><th>Vibration</th><th>Temp</th><th>Voltage</th><th>Risk Score</th><th>Risk Level</th><th>Action Required</th><th>Power Status</th><th>Actions</th></tr></thead><tbody id="riskBody"></tbody></table></div></div>
      </div>

      <!-- CUTOFF CONTROL -->
      <div class="page" id="page-cutoff">
        <div class="stats-grid" id="cutoffStats" style="grid-template-columns:1fr 1fr 1fr;"></div>
        <div class="card">
          <div class="card-header">
            <h3>Power Cutoff Control</h3>
            <div class="bulk-actions">
              <button class="btn btn-success" onclick="bulkPower('restore')"><i class="fas fa-plug"></i> All Power ON</button>
              <button class="btn btn-danger" onclick="bulkPower('cutoff')"><i class="fas fa-power-off"></i> All Power OFF</button>
            </div>
          </div>
          <div class="filters-bar" style="margin-bottom:15px;">
            <input type="text" placeholder="🔍 Search poles..." id="cutoffSearch" oninput="filterCutoff()">
            <select id="cutoffPowerFilter" onchange="filterCutoff()"><option value="">All Status</option><option value="1">Power ON</option><option value="0">Power OFF</option></select>
            <select id="cutoffZoneFilter" onchange="filterCutoff()"><option value="">All Zones</option><option value="north">North</option><option value="south">South</option><option value="east">East</option><option value="west">West</option></select>
          </div>
          <div class="table-wrapper"><table><thead><tr><th>Pole ID</th><th>Location</th><th>Zone</th><th>Status</th><th>Voltage</th><th>Risk</th><th>Last Cutoff</th><th>Power ON</th><th>Power OFF</th><th>Actions</th></tr></thead><tbody id="cutoffBody"></tbody></table></div>
        </div>
      </div>

      <!-- PROOF VERIFICATION -->
      <div class="page" id="page-proof">
        <div class="filters-bar">
          <select id="proofStatusFilter" onchange="loadProofs()"><option value="">All Status</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select>
        </div>
        <div class="card"><div class="table-wrapper"><table><thead><tr><th>Proof ID</th><th>Task</th><th>Worker</th><th>Pole</th><th>Type</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead><tbody id="proofBody"></tbody></table></div></div>
      </div>

      <!-- COMPLAINTS -->
      <div class="page" id="page-complaints">
        <div class="filters-bar">
          <input type="text" placeholder="🔍 Search complaints...">
          <select id="complaintStatusFilter" onchange="loadComplaints()"><option value="">All Status</option><option value="open">Open</option><option value="assigned">Assigned</option><option value="resolved">Resolved</option><option value="escalated">Escalated</option></select>
          <button class="btn btn-primary" onclick="openAddComplaintModal()"><i class="fas fa-plus"></i> New Complaint</button>
        </div>
        <div class="card"><div class="table-wrapper"><table><thead><tr><th>Complaint ID</th><th>Citizen</th><th>Pole</th><th>Issue</th><th>Priority</th><th>Worker</th><th>Status</th><th>Actions</th></tr></thead><tbody id="complaintsBody"></tbody></table></div></div>
      </div>

      <!-- ACCELEROMETER READINGS -->
      <div class="page" id="page-accelerometer">
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
          <div class="stat-card" id="accelStatX"><div class="stat-header"><div class="stat-icon blue"><i class="fas fa-arrows-alt-h"></i></div></div><div class="stat-value" id="liveAccelX">--</div><div class="stat-label">X-Axis (g)</div></div>
          <div class="stat-card" id="accelStatY"><div class="stat-header"><div class="stat-icon green"><i class="fas fa-arrows-alt-v"></i></div></div><div class="stat-value" id="liveAccelY">--</div><div class="stat-label">Y-Axis (g)</div></div>
          <div class="stat-card" id="accelStatZ"><div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-compress-arrows-alt"></i></div></div><div class="stat-value" id="liveAccelZ">--</div><div class="stat-label">Z-Axis (g)</div></div>
          <div class="stat-card" id="accelStatMag"><div class="stat-header"><div class="stat-icon purple"><i class="fas fa-tachometer-alt"></i></div></div><div class="stat-value" id="liveAccelMag">--</div><div class="stat-label">Magnitude (g)</div></div>
        </div>
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-wave-square"></i> Live Accelerometer Monitor</h3>
            <div style="display:flex;gap:10px;align-items:center;">
              <select id="accelPoleFilter" onchange="loadAccelReadings()" style="padding:8px 12px;background:var(--card2);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:13px;">
                <option value="0">All Poles</option>
              </select>
              <button class="btn btn-primary" id="accelLiveBtn" onclick="toggleAccelLive()">
                <i class="fas fa-satellite-dish"></i> <span id="accelLiveBtnText">Start Live</span>
              </button>
              <button class="btn btn-secondary" onclick="loadAccelReadings()">
                <i class="fas fa-sync-alt"></i> Refresh
              </button>
            </div>
          </div>
          <div class="charts-grid">
            <div style="height:300px;position:relative;"><canvas id="accelChart"></canvas></div>
            <div style="height:300px;position:relative;"><canvas id="accelMagChart"></canvas></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3>Recent Readings</h3></div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Time</th><th>Pole</th><th>X (g)</th><th>Y (g)</th><th>Z (g)</th><th>Magnitude (g)</th><th>Source IP</th></tr></thead>
              <tbody id="accelReadingsBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MATERIAL REQUESTS -->
      <div class="page" id="page-materials">
        <div class="filters-bar">
          <select id="materialStatusFilter" onchange="loadMaterials()"><option value="">All Status</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="delivered">Delivered</option></select>
        </div>
        <div class="card"><div class="table-wrapper"><table><thead><tr><th>ID</th><th>Worker</th><th>Material</th><th>Qty</th><th>Urgency</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody id="materialsBody"></tbody></table></div></div>
      </div>

    </div>
  </div>
</div>

<script>
const API = 'api.php';
let adminMap = null;
let healthChartInst = null;
let riskChartInst = null;
let allPoles = [];
let allWorkers = [];

const pageTitles = {
  dashboard:'Dashboard',poles:'All Poles',polesMap:'Poles Map',alerts:'Alerts',
  faultLogs:'Fault Logs',workers:'Workers',users:'User Management',
  riskLevels:'Risk Levels',cutoff:'Cutoff Control',proof:'Proof Verification',accelerometer:'Accelerometer',complaints:'Complaints',materials:'Material Requests'
};

// ==================== NAVIGATION ====================
function showPage(page, navItem) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-' + page).classList.add('active');
  if (navItem) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    navItem.classList.add('active');
  }
  document.getElementById('pageTitle').textContent = pageTitles[page] || 'Dashboard';
  document.getElementById('sidebar').classList.remove('open');

  // Load data for page
  switch(page) {
    case 'dashboard': loadDashboard(); break;
    case 'poles': loadPoles(); break;
    case 'polesMap': setTimeout(() => initMap(), 200); break;
    case 'alerts': loadAlerts(); break;
    case 'faultLogs': loadFaultLogs(); break;
    case 'workers': loadWorkers(); break;
    case 'users': loadUsers(); break;
    case 'riskLevels': loadRiskLevels(); break;
    case 'cutoff': loadCutoff(); break;
    case 'proof': loadProofs(); break;
    case 'complaints': loadComplaints(); break;
    case 'materials': loadMaterials(); break;
    case 'accelerometer': loadAccelReadings(); loadAccelPoleOptions(); break;
  }
}

function logout() {
  fetch(API + '?action=logout').then(() => window.location.href = 'admin_login.php');
}

// ==================== API HELPER ====================
async function api(action, opts = {}) {
  const url = API + '?action=' + action + (opts.params ? '&' + new URLSearchParams(opts.params) : '');
  const config = { method: opts.method || 'GET' };
  if (opts.body) {
    config.method = opts.method || 'POST';
    config.headers = { 'Content-Type': 'application/json' };
    config.body = JSON.stringify(opts.body);
  }
  const res = await fetch(url, config);
  return res.json();
}

// ==================== MODAL ====================
function openModal(title, html) {
  document.getElementById('detailModalTitle').innerHTML = title;
  document.getElementById('detailModalBody').innerHTML = html;
  document.getElementById('detailModal').classList.add('active');
}
function closeModal() { document.getElementById('detailModal').classList.remove('active'); }
document.getElementById('detailModal').addEventListener('click', e => { if (e.target.id === 'detailModal') closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ==================== DASHBOARD ====================
async function loadDashboard() {
  const res = await api('dashboard');
  if (!res.success) return;
  const d = res.data;

  document.getElementById('dashStats').innerHTML = `
    <div class="stat-card"><div class="stat-header"><div class="stat-icon purple"><i class="fas fa-broadcast-tower"></i></div></div><div class="stat-value">${d.total_poles}</div><div class="stat-label">Total Poles</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div></div><div class="stat-value">${d.active_poles}</div><div class="stat-label">Active Poles</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon red"><i class="fas fa-exclamation-circle"></i></div></div><div class="stat-value">${d.critical_alerts}</div><div class="stat-label">Critical Alerts</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-hard-hat"></i></div></div><div class="stat-value">${d.active_workers}</div><div class="stat-label">Active Workers</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon blue"><i class="fas fa-tasks"></i></div></div><div class="stat-value">${d.pending_tasks}</div><div class="stat-label">Pending Tasks</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon red"><i class="fas fa-comment-dots"></i></div></div><div class="stat-value">${d.open_complaints}</div><div class="stat-label">Open Complaints</div></div>
  `;

  document.getElementById('alertBadge').textContent = d.critical_alerts;
  document.getElementById('complaintBadge').textContent = d.open_complaints;

  // Recent alerts
  document.getElementById('recentAlertsBody').innerHTML = d.recent_alerts.map(a => `
    <tr><td>${timeAgo(a.created_at)}</td><td>${a.pole_code}</td><td>${a.alert_type}</td>
    <td><span class="status-badge ${a.severity}">${a.severity}</span></td>
    <td><span class="status-badge ${a.status}">${a.status}</span></td></tr>
  `).join('');

  // Charts
  const rd = d.risk_distribution;
  initCharts(rd);
}

function initCharts(rd) {
  if (healthChartInst) healthChartInst.destroy();
  if (riskChartInst) riskChartInst.destroy();

  healthChartInst = new Chart(document.getElementById('healthChart'), {
    type: 'line',
    data: {
      labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
      datasets: [
        {label:'Normal',data:[rd.normal-2,rd.normal-1,rd.normal,rd.normal-3,rd.normal-1,rd.normal+1,rd.normal],borderColor:'#00b894',backgroundColor:'rgba(0,184,148,0.1)',fill:true,tension:0.4},
        {label:'Medium',data:[rd.medium+1,rd.medium,rd.medium-1,rd.medium+2,rd.medium,rd.medium+1,rd.medium],borderColor:'#fdcb6e',backgroundColor:'rgba(253,203,110,0.1)',fill:true,tension:0.4},
        {label:'High',data:[rd.high-1,rd.high,rd.high+1,rd.high,rd.high-1,rd.high+1,rd.high],borderColor:'#d63031',backgroundColor:'rgba(214,48,49,0.1)',fill:true,tension:0.4},
        {label:'Critical',data:[rd.critical-1,rd.critical,rd.critical+1,rd.critical,rd.critical,rd.critical+1,rd.critical],borderColor:'#6c5ce7',backgroundColor:'rgba(108,92,231,0.1)',fill:true,tension:0.4}
      ]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#b2bec3',font:{size:11}}}},scales:{x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#636e72'}},y:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#636e72'}}}}
  });

  riskChartInst = new Chart(document.getElementById('riskChart'), {
    type: 'doughnut',
    data: {
      labels: ['Normal','Medium','High','Critical'],
      datasets: [{data:[rd.normal,rd.medium,rd.high,rd.critical],backgroundColor:['#00b894','#fdcb6e','#d63031','#6c5ce7'],borderWidth:0}]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:'#b2bec3',font:{size:11},padding:15}}}}
  });
}

// ==================== POLES ====================
async function loadPoles() {
  const search = document.getElementById('poleSearch')?.value || '';
  const zone = document.getElementById('poleZoneFilter')?.value || '';
  const risk = document.getElementById('poleRiskFilter')?.value || '';

  const res = await api('poles', { params: { search, zone, risk } });
  if (!res.success) return;
  allPoles = res.data;

  document.getElementById('polesBody').innerHTML = allPoles.map(p => `
    <tr>
      <td><strong style="color:var(--white)">${p.pole_id}</strong></td>
      <td>${p.location}</td>
      <td style="text-transform:capitalize">${p.zone}</td>
      <td>${p.vibration || '-'} mm/s</td>
      <td>${p.temperature || '-'}°C</td>
      <td>${p.voltage || '-'}V</td>
      <td><span class="status-badge ${p.risk_level || 'normal'}">${p.risk_level || 'normal'}</span></td>
      <td>${p.worker_name || '-'}</td>
      <td>
        ${p.power_status == 1 
          ? `<button class="btn btn-danger" onclick="togglePolePower(${p.id},'cutoff')" title="Turn OFF"><i class="fas fa-power-off"></i> OFF</button>` 
          : `<button class="btn btn-success" onclick="togglePolePower(${p.id},'restore')" title="Turn ON"><i class="fas fa-plug"></i> ON</button>`
        }
      </td>
      <td>
        <button class="btn btn-warning" onclick="openAssignWorkerModal(${p.id}, '${p.pole_id}', ${p.assigned_worker_id || 'null'})" title="Assign Worker"><i class="fas fa-user-plus"></i></button>
        <button class="btn btn-outline" onclick="viewPole(${p.id})"><i class="fas fa-eye"></i></button>
      </td>
    </tr>
  `).join('');
}

async function viewPole(id) {
  const res = await api('pole', { params: { id } });
  if (!res.success) return;
  const p = res.data;
  openModal(`<i class="fas fa-broadcast-tower"></i> ${p.pole_id} - ${p.location}`, `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div class="card" style="margin:0;padding:15px;"><div style="font-size:11px;color:var(--text-light);">Vibration</div><div style="font-size:22px;font-weight:800;color:${parseFloat(p.vibration)>5?'var(--red)':'var(--green)'};">${p.vibration} mm/s</div></div>
      <div class="card" style="margin:0;padding:15px;"><div style="font-size:11px;color:var(--text-light);">Temperature</div><div style="font-size:22px;font-weight:800;color:${parseFloat(p.temperature)>50?'var(--red)':'var(--green)'};">${p.temperature}°C</div></div>
      <div class="card" style="margin:0;padding:15px;"><div style="font-size:11px;color:var(--text-light);">Voltage</div><div style="font-size:22px;font-weight:800;color:${parseFloat(p.voltage)<210?'var(--red)':'var(--green)'};">${p.voltage}V</div></div>
      <div class="card" style="margin:0;padding:15px;"><div style="font-size:11px;color:var(--text-light);">Risk Level</div><div style="font-size:22px;font-weight:800;"><span class="status-badge ${p.risk_level}">${p.risk_level} (${p.risk_score})</span></div></div>
    </div>
    <div style="margin-top:15px;display:grid;gap:6px;font-size:13px;">
      <div><strong style="color:var(--white);">Worker:</strong> ${p.worker_name||'Unassigned'}</div>
      <div><strong style="color:var(--white);">Zone:</strong> ${p.zone}</div>
      <div><strong style="color:var(--white);">Power:</strong> <span class="status-badge ${p.power_status==1?'active':'offline'}">${p.power_status==1?'ON':'OFF'}</span></div>
      <div><strong style="color:var(--white);">Coordinates:</strong> ${p.latitude}, ${p.longitude}</div>
    </div>
    <div style="margin-top:15px;display:flex;gap:8px;">
      <button class="btn btn-danger" onclick="toggleCutoff(${p.id},'${p.power_status==1?'cutoff':'restore'}')"><i class="fas fa-power-off"></i> ${p.power_status==1?'Cut Off':'Power On'}</button>
      <button class="btn btn-warning" onclick="openAssignWorkerModal(${p.id}, '${p.pole_id}', ${p.assigned_worker_id || 'null'})"><i class="fas fa-user-plus"></i> Assign Worker</button>
    </div>
  `);
}

function openAddPoleModal() {
  openModal('<i class="fas fa-plus"></i> Add New Pole', `
    <div class="form-group"><label>Pole ID</label><input type="text" id="newPoleId" placeholder="POLE-XXX"></div>
    <div class="form-group"><label>Location</label><input type="text" id="newPoleLoc" placeholder="Enter location"></div>
    <div class="form-group"><label>Zone</label><select id="newPoleZone"><option value="north">North</option><option value="south">South</option><option value="east">East</option><option value="west">West</option></select></div>
    <div class="form-group"><label>Latitude</label><input type="number" id="newPoleLat" step="0.0001" placeholder="11.XXXX"></div>
    <div class="form-group"><label>Longitude</label><input type="number" id="newPoleLng" step="0.0001" placeholder="78.XXXX"></div>
    <button class="btn btn-primary" onclick="addPole()"><i class="fas fa-save"></i> Add Pole</button>
  `);
}

async function addPole() {
  const res = await api('poles', { body: {
    pole_id: document.getElementById('newPoleId').value,
    location: document.getElementById('newPoleLoc').value,
    zone: document.getElementById('newPoleZone').value,
    latitude: document.getElementById('newPoleLat').value,
    longitude: document.getElementById('newPoleLng').value
  }});
  alert(res.message); closeModal(); loadPoles();
}

async function togglePolePower(poleId, action) {
  const label = action === 'cutoff' ? 'Power OFF' : 'Power ON';
  if (!confirm(`Are you sure you want to ${label} this pole?`)) return;
  const reason = action === 'cutoff' ? 'Admin power off from All Poles' : 'Admin power on from All Poles';
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action, reason } });
  showToast(res.message || (action === 'cutoff' ? 'Pole powered OFF' : 'Pole powered ON'), action === 'cutoff' ? 'danger' : 'success');
  await espSendCommand(action);
  loadPoles();
  loadCutoff();
  loadRiskLevels();
}

async function openAssignWorkerModal(poleId, poleCode, currentWorkerId) {
  if (!allWorkers.length) {
    const res = await api('workers');
    if (res.success) allWorkers = res.data;
  }
  
  const workerOptions = allWorkers.map(w => `<option value="${w.id}" ${w.id == currentWorkerId ? 'selected' : ''}>${w.name} (${w.zone} Zone)</option>`).join('');
  
  openModal(`<i class="fas fa-user-plus"></i> Assign Worker to ${poleCode}`, `
    <div class="form-group">
      <label>Select Worker</label>
      <select id="assignWorkerSelect">
        <option value="">-- Unassigned --</option>
        ${workerOptions}
      </select>
    </div>
    <button class="btn btn-primary" onclick="assignWorkerToPole(${poleId})"><i class="fas fa-save"></i> Save Assignment</button>
  `);
}

async function assignWorkerToPole(poleId) {
  const workerId = document.getElementById('assignWorkerSelect').value;
  const res = await api('pole', { 
    method: 'PUT', 
    params: { id: poleId }, 
    body: { assigned_worker_id: workerId || null } 
  });
  
  if (res.success) {
    showToast('Worker assigned successfully', 'success');
    closeModal();
    loadPoles();
  } else {
    showToast(res.message || 'Failed to assign worker', 'danger');
  }
}

// ==================== MAP ====================
function initMap() {
  if (adminMap) { adminMap.invalidateSize(); return; }
  adminMap = L.map('admin-map').setView([11.1271, 78.6569], 7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(adminMap);
  loadMapPoles();
}

async function loadMapPoles() {
  if (!allPoles.length) {
    const res = await api('poles');
    if (res.success) allPoles = res.data;
  }

  const colors = { normal:'#00b894', medium:'#fdcb6e', high:'#d63031', critical:'#6c5ce7' };
  let counts = { normal:0, medium:0, high:0, critical:0 };

  allPoles.forEach(p => {
    const risk = p.risk_level || 'normal';
    counts[risk]++;
    const color = colors[risk] || colors.normal;
    const icon = L.divIcon({
      html:`<div style="background:${color};width:24px;height:24px;border-radius:50%;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;"><div style="width:8px;height:8px;background:white;border-radius:50%;"></div></div>`,
      className:'custom-marker', iconSize:[24,24], iconAnchor:[12,12], popupAnchor:[0,-15]
    });
    L.marker([p.latitude, p.longitude], { icon }).addTo(adminMap)
      .bindPopup(`<div style="font-family:Segoe UI,sans-serif;min-width:200px;"><h3 style="margin:0 0 8px;color:#333;font-size:14px;">${p.pole_id} - ${p.location}</h3><div style="font-size:12px;"><div><b>Vibration:</b> ${p.vibration} mm/s</div><div><b>Temp:</b> ${p.temperature}°C</div><div><b>Voltage:</b> ${p.voltage}V</div><div><b>Risk:</b> <span style="background:${color};color:white;padding:2px 8px;border-radius:10px;font-size:10px;">${risk.toUpperCase()}</span></div><div><b>Power:</b> ${p.power_status==1?'🟢 ON':'🔴 OFF'}</div></div></div>`);
  });

  document.getElementById('mapLegend').innerHTML = Object.entries(counts).map(([k,v]) => `
    <div class="stat-card" style="padding:12px;text-align:center;"><div style="display:flex;align-items:center;gap:8px;justify-content:center;">
      <div style="width:14px;height:14px;border-radius:50%;background:${colors[k]};"></div>
      <span style="color:var(--white);font-weight:700;">${k.charAt(0).toUpperCase()+k.slice(1)}: ${v}</span></div></div>
  `).join('');
}

// ==================== ALERTS ====================
async function loadAlerts() {
  const severity = document.getElementById('alertSevFilter')?.value || '';
  const status = document.getElementById('alertStatusFilter')?.value || '';
  const res = await api('alerts', { params: { severity, status } });
  if (!res.success) return;

  document.getElementById('alertsBody').innerHTML = res.data.map(a => `
    <tr>
      <td><strong style="color:var(--white)">${a.alert_id}</strong></td>
      <td>${timeAgo(a.created_at)}</td>
      <td>${a.pole_code}</td>
      <td>${a.alert_type}</td>
      <td style="color:var(--white);font-weight:600;">${a.value}</td>
      <td>${a.threshold}</td>
      <td><span class="status-badge ${a.severity}">${a.severity}</span></td>
      <td><span class="status-badge ${a.status}">${a.status}</span></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap;">
        ${a.status !== 'resolved' ? `<button class="btn btn-success" onclick="resolveAlert(${a.id})" title="Resolve"><i class="fas fa-check"></i> Resolve</button>` : `<span style="color:var(--green);font-size:12px;"><i class="fas fa-check-circle"></i> Resolved</span>`}
        <button class="btn btn-danger" onclick="alertCutoff(${a.pole_id})" title="Cutoff Pole"><i class="fas fa-power-off"></i></button>
        <button class="btn btn-outline" onclick="viewPole(${a.pole_id})" title="View Pole"><i class="fas fa-eye"></i></button>
      </td>
    </tr>
  `).join('');
}

async function resolveAlert(id) {
  if (!confirm('Mark this alert as resolved?')) return;
  const res = await api('alert_resolve', { body: { id } });
  showToast(res.message || 'Alert resolved', 'success');
  loadAlerts();
  loadDashboard();
}

async function alertCutoff(poleId) {
  if (!confirm('Cut off power to this pole due to alert?')) return;
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action: 'cutoff', reason: 'Emergency cutoff from Alerts panel' } });
  showToast(res.message || 'Pole power cut off', 'danger');
  await espSendCommand('cutoff');
  loadAlerts();
  loadCutoff();
  loadPoles();
}

// ==================== FAULT LOGS ====================
async function loadFaultLogs() {
  const res = await api('fault_logs');
  if (!res.success) return;
  document.getElementById('faultLogsBody').innerHTML = res.data.map(f => `
    <tr>
      <td><strong style="color:var(--white)">${f.log_id}</strong></td>
      <td>${f.created_at?.substring(0,10)}</td>
      <td>${f.pole_code}</td>
      <td style="text-transform:capitalize">${f.fault_type}</td>
      <td style="max-width:250px;">${f.description}</td>
      <td><span class="status-badge ${f.severity}">${f.severity}</span></td>
      <td>${f.resolved_by_name || '-'}</td>
      <td><span class="status-badge ${f.status}">${f.status}</span></td>
    </tr>
  `).join('');
}

// ==================== WORKERS ====================
async function loadWorkers() {
  const res = await api('workers');
  if (!res.success) return;
  allWorkers = res.data;
  document.getElementById('workersBody').innerHTML = allWorkers.map(w => `
    <tr>
      <td><strong style="color:var(--white)">${w.worker_id}</strong></td>
      <td>${w.name}</td>
      <td>${w.phone || '-'}</td>
      <td style="text-transform:capitalize">${w.zone}</td>
      <td>${w.assigned_poles}</td>
      <td>${w.tasks_completed}</td>
      <td><span style="color:var(--yellow);">★ ${w.rating}</span></td>
      <td><span class="status-badge ${w.status}">${w.status}</span></td>
      <td>
        <button class="btn btn-outline" onclick="viewWorker(${JSON.stringify(w).replace(/"/g,'&quot;')})"><i class="fas fa-eye"></i></button>
        <button class="btn btn-danger" onclick="deleteWorker(${w.id}, '${w.name}')"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `).join('');
}

function viewWorker(w) {
  openModal(`<i class="fas fa-hard-hat"></i> ${w.name}`, `
    <div style="display:flex;gap:20px;align-items:center;margin-bottom:20px;">
      <div style="width:70px;height:70px;border-radius:15px;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--white);font-weight:800;">${w.name.charAt(0)}</div>
      <div><h3 style="color:var(--white);margin-bottom:4px;">${w.name}</h3><p>${w.worker_id} • ${w.zone} Zone</p><p>${w.phone || ''}</p></div>
    </div>
    <div class="profile-stats">
      <div class="profile-stat"><div class="val">${w.assigned_poles}</div><div class="lbl">Poles</div></div>
      <div class="profile-stat"><div class="val">${w.tasks_completed}</div><div class="lbl">Tasks</div></div>
      <div class="profile-stat"><div class="val">${w.rating}★</div><div class="lbl">Rating</div></div>
    </div>
  `);
}

function openAddWorkerModal() {
  openModal('<i class="fas fa-plus"></i> Add Worker', `
    <div class="form-group"><label>Name</label><input type="text" id="nwName" placeholder="Full Name"></div>
    <div class="form-group"><label>Email</label><input type="email" id="nwEmail" placeholder="email@example.com"></div>
    <div class="form-group"><label>Phone</label><input type="text" id="nwPhone" placeholder="+91..."></div>
    <div class="form-group"><label>Zone</label><select id="nwZone"><option value="north">North</option><option value="south">South</option><option value="east">East</option><option value="west">West</option></select></div>
    <div class="form-group"><label>Specialization</label><input type="text" id="nwSpec" placeholder="e.g. Electrical Systems"></div>
    <div class="form-group"><label>Password</label><input type="password" id="nwPass" value="worker123"></div>
    <button class="btn btn-primary" onclick="addWorker()"><i class="fas fa-save"></i> Add Worker</button>
  `);
}

async function addWorker() {
  const res = await api('workers', { body: {
    name: document.getElementById('nwName').value,
    email: document.getElementById('nwEmail').value,
    phone: document.getElementById('nwPhone').value,
    zone: document.getElementById('nwZone').value,
    specialization: document.getElementById('nwSpec').value,
    password: document.getElementById('nwPass').value,
  }});
  alert(res.message); closeModal(); loadWorkers();
}

async function deleteWorker(id, name) {
  if (!confirm(`Are you sure you want to remove worker "${name}"? This action cannot be undone.`)) return;
  const res = await api('worker_delete', { body: { id } });
  if (res.success) {
    showToast('Worker removed successfully', 'success');
    loadWorkers();
  } else {
    showToast(res.message || 'Failed to remove worker', 'danger');
  }
}

// ==================== USERS ====================
async function loadUsers() {
  const res = await api('users');
  if (!res.success) return;
  document.getElementById('usersBody').innerHTML = res.data.map(u => `
    <tr>
      <td><strong style="color:var(--white)">${u.user_id}</strong></td>
      <td>${u.name}</td>
      <td>${u.email}</td>
      <td><span class="status-badge ${u.role_name==='admin'?'critical':u.role_name==='worker'?'medium':'normal'}">${u.role_name}</span></td>
      <td>${u.last_login ? timeAgo(u.last_login) : 'Never'}</td>
      <td><span class="status-badge ${u.status}">${u.status}</span></td>
      <td>
        <button class="btn btn-outline" onclick="editUser(${u.id})"><i class="fas fa-edit"></i></button>
        <button class="btn btn-danger" onclick="deleteUser(${u.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `).join('');
}

function openAddUserModal() {
  openModal('<i class="fas fa-plus"></i> Add User', `
    <div class="form-group"><label>Name</label><input type="text" id="nuName" placeholder="Full Name"></div>
    <div class="form-group"><label>Email</label><input type="email" id="nuEmail" placeholder="email@example.com"></div>
    <div class="form-group"><label>Phone</label><input type="text" id="nuPhone" placeholder="+91..."></div>
    <div class="form-group"><label>Role</label><select id="nuRole"><option value="1">Admin</option><option value="2">Worker</option><option value="3">Viewer</option></select></div>
    <div class="form-group"><label>Password</label><input type="password" id="nuPass" value="password123"></div>
    <button class="btn btn-primary" onclick="addUser()"><i class="fas fa-save"></i> Add User</button>
  `);
}

async function addUser() {
  const res = await api('users', { body: {
    name: document.getElementById('nuName').value,
    email: document.getElementById('nuEmail').value,
    phone: document.getElementById('nuPhone').value,
    role_id: document.getElementById('nuRole').value,
    password: document.getElementById('nuPass').value,
  }});
  alert(res.message); closeModal(); loadUsers();
}

async function deleteUser(id) {
  if (!confirm('Delete this user?')) return;
  await api('user_delete', { body: { id } });
  loadUsers();
}

// ==================== RISK LEVELS ====================
async function loadRiskLevels() {
  const res = await api('risk_levels');
  if (!res.success) return;
  const data = res.data;
  let counts = { normal:0, medium:0, high:0, critical:0 };
  data.forEach(r => counts[r.risk_level]++);

  document.getElementById('riskStats').innerHTML = `
    <div class="stat-card"><div class="stat-header"><div class="stat-icon green"><i class="fas fa-shield-alt"></i></div></div><div class="stat-value" style="color:var(--green);">${counts.normal}</div><div class="stat-label">Low Risk</div><div class="risk-bar"><div class="fill normal"></div></div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-exclamation"></i></div></div><div class="stat-value" style="color:var(--yellow);">${counts.medium}</div><div class="stat-label">Medium Risk</div><div class="risk-bar"><div class="fill medium"></div></div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon red"><i class="fas fa-radiation"></i></div></div><div class="stat-value" style="color:var(--red);">${counts.high}</div><div class="stat-label">High Risk</div><div class="risk-bar"><div class="fill high"></div></div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon purple"><i class="fas fa-skull-crossbones"></i></div></div><div class="stat-value" style="color:var(--purple);">${counts.critical}</div><div class="stat-label">Critical Risk</div><div class="risk-bar"><div class="fill critical"></div></div></div>
  `;

  document.getElementById('riskBody').innerHTML = data.map(r => `
    <tr>
      <td><strong style="color:var(--white)">${r.pole_code}</strong></td>
      <td>${r.location}</td>
      <td style="text-transform:capitalize">${r.zone}</td>
      <td>${r.vibration || '-'} mm/s</td>
      <td>${r.temperature || '-'}°C</td>
      <td>${r.voltage || '-'}V</td>
      <td><strong style="color:var(--white)">${r.risk_score}</strong>/100</td>
      <td><span class="status-badge ${r.risk_level}">${r.risk_level}</span></td>
      <td>${r.action_required || '-'}</td>
      <td><span class="status-badge ${r.power_status==1?'active':'offline'}">${r.power_status==1?'🟢 ON':'🔴 OFF'}</span></td>
      <td>
        <button class="btn btn-success ${r.power_status==1?'btn-disabled':''}" onclick="powerOnRisk(${r.p_id})" ${r.power_status==1?'disabled':''} style="padding:4px 8px;font-size:10px;"><i class="fas fa-plug"></i> ON</button>
        <button class="btn btn-danger ${r.power_status==0?'btn-disabled':''}" onclick="powerOffRisk(${r.p_id})" ${r.power_status==0?'disabled':''} style="padding:4px 8px;font-size:10px;"><i class="fas fa-power-off"></i> OFF</button>
      </td>
    </tr>
  `).join('');
}

async function powerOnRisk(poleId) {
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action: 'restore', reason: 'Admin Power ON from Risk Levels' } });
  showToast(res.message || 'Power restored', 'success');
  await espSendCommand('restore');
  loadRiskLevels();
  loadCutoff();
  loadPoles();
}

async function powerOffRisk(poleId) {
  if (!confirm('Are you sure you want to Power OFF this pole?')) return;
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action: 'cutoff', reason: 'Admin Power OFF from Risk Levels' } });
  showToast(res.message || 'Power cut off', 'danger');
  await espSendCommand('cutoff');
  loadRiskLevels();
  loadCutoff();
  loadPoles();
}

// ==================== CUTOFF ====================
let cutoffData = [];

async function loadCutoff() {
  const res = await api('cutoff');
  if (!res.success) return;
  cutoffData = res.data;
  renderCutoff(cutoffData);
}

function filterCutoff() {
  const search = (document.getElementById('cutoffSearch')?.value || '').toLowerCase();
  const power = document.getElementById('cutoffPowerFilter')?.value || '';
  const zone = document.getElementById('cutoffZoneFilter')?.value || '';
  let filtered = cutoffData;
  if (search) filtered = filtered.filter(p => p.pole_id.toLowerCase().includes(search) || p.location.toLowerCase().includes(search));
  if (power !== '') filtered = filtered.filter(p => String(p.power_status) === power);
  if (zone) filtered = filtered.filter(p => p.zone === zone);
  renderCutoff(filtered);
}

function renderCutoff(data) {
  let on = 0, off = 0;
  data.forEach(p => p.power_status == 1 ? on++ : off++);

  document.getElementById('cutoffStats').innerHTML = `
    <div class="stat-card"><div class="stat-header"><div class="stat-icon green"><i class="fas fa-plug"></i></div></div><div class="stat-value" style="color:var(--green)">${on}</div><div class="stat-label">Powered ON</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon red"><i class="fas fa-power-off"></i></div></div><div class="stat-value" style="color:var(--red)">${off}</div><div class="stat-label">Powered OFF</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon purple"><i class="fas fa-broadcast-tower"></i></div></div><div class="stat-value">${data.length}</div><div class="stat-label">Total Poles</div></div>
  `;

  document.getElementById('cutoffBody').innerHTML = data.map(p => `
    <tr>
      <td><strong style="color:var(--white)">${p.pole_id}</strong></td>
      <td>${p.location}</td>
      <td style="text-transform:capitalize">${p.zone}</td>
      <td><span class="status-badge ${p.power_status==1?'active':'offline'}">${p.power_status==1?'🟢 Online':'🔴 Offline'}</span></td>
      <td>${p.voltage || '-'}V</td>
      <td><span class="status-badge ${p.risk_level||'normal'}">${p.risk_level||'normal'}</span></td>
      <td>${p.last_cutoff ? timeAgo(p.last_cutoff) : 'Never'}</td>
      <td>
        <button class="btn btn-success ${p.power_status==1?'btn-disabled':''}" onclick="powerOn(${p.id})" ${p.power_status==1?'disabled':''}><i class="fas fa-plug"></i> ON</button>
      </td>
      <td>
        <button class="btn btn-danger ${p.power_status==0?'btn-disabled':''}" onclick="powerOff(${p.id})" ${p.power_status==0?'disabled':''}><i class="fas fa-power-off"></i> OFF</button>
      </td>
      <td><button class="btn btn-outline" onclick="viewPole(${p.id})"><i class="fas fa-eye"></i></button></td>
    </tr>
  `).join('');
}

async function powerOn(poleId) {
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action: 'restore', reason: 'Admin Power ON from Cutoff Control' } });
  showToast(res.message || 'Power restored', 'success');
  await espSendCommand('restore');
  loadCutoff();
  loadPoles();
  loadRiskLevels();
}

async function powerOff(poleId) {
  if (!confirm('Are you sure you want to Power OFF this pole?')) return;
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action: 'cutoff', reason: 'Admin Power OFF from Cutoff Control' } });
  showToast(res.message || 'Power cut off', 'danger');
  await espSendCommand('cutoff');
  loadCutoff();
  loadPoles();
  loadRiskLevels();
}

async function bulkPower(action) {
  const label = action === 'cutoff' ? 'Power OFF ALL poles' : 'Power ON ALL poles';
  if (!confirm(`Are you sure you want to ${label}? This affects every pole in the system.`)) return;
  const reason = action === 'cutoff' ? 'Bulk power off by admin' : 'Bulk power on by admin';
  // Send individual requests for each pole
  const promises = cutoffData
    .filter(p => action === 'cutoff' ? p.power_status == 1 : p.power_status == 0)
    .map(p => api('cutoff_toggle', { body: { pole_id: p.id, action, reason } }));
  await Promise.all(promises);
  await espSendCommand(action);
  showToast(`${label} completed (${promises.length} poles)`, action === 'cutoff' ? 'danger' : 'success');
  loadCutoff();
  loadPoles();
  loadRiskLevels();
}

async function toggleCutoff(poleId, action) {
  const label = action === 'cutoff' ? 'CUT OFF power' : 'RESTORE power';
  if (!confirm(`Are you sure you want to ${label} for this pole?`)) {
    loadCutoff();
    return;
  }
  const reason = action === 'cutoff' ? 'Manual cutoff by admin' : 'Manual restore by admin';
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action, reason } });
  showToast(res.message || `Power ${action === 'cutoff' ? 'cut off' : 'restored'}`, action === 'cutoff' ? 'danger' : 'success');
  await espSendCommand(action);
  loadCutoff();
  loadPoles();
  loadRiskLevels();
}

// ==================== PROOF VERIFICATION ====================
async function loadProofs() {
  const status = document.getElementById('proofStatusFilter')?.value || '';
  const res = await api('proofs', { params: { status } });
  if (!res.success) return;

  document.getElementById('proofBody').innerHTML = res.data.map(p => `
    <tr>
      <td><strong style="color:var(--white)">${p.proof_id}</strong></td>
      <td>${p.task_code || '-'}</td>
      <td>${p.worker_name}</td>
      <td>${p.pole_code}</td>
      <td style="text-transform:capitalize">${p.proof_type.replace(/_/g,' ')}</td>
      <td>${timeAgo(p.created_at)}</td>
      <td><span class="status-badge ${p.status}">${p.status}</span></td>
      <td>
        ${p.status === 'pending' ? `
          <button class="btn btn-success" onclick="verifyProof(${p.id},'approved')"><i class="fas fa-check"></i></button>
          <button class="btn btn-danger" onclick="verifyProof(${p.id},'rejected')"><i class="fas fa-times"></i></button>
        ` : ''}
      </td>
    </tr>
  `).join('');
}

async function verifyProof(id, status) {
  const reason = status === 'rejected' ? prompt('Rejection reason:') || '' : '';
  await api('proof_verify', { body: { id, status, rejection_reason: reason } });
  loadProofs();
}

// ==================== COMPLAINTS ====================
async function loadComplaints() {
  const status = document.getElementById('complaintStatusFilter')?.value || '';
  const res = await api('complaints', { params: { status } });
  if (!res.success) return;

  document.getElementById('complaintsBody').innerHTML = res.data.map(c => `
    <tr>
      <td><strong style="color:var(--white)">${c.complaint_id}</strong></td>
      <td>${c.citizen_name}</td>
      <td>${c.pole_code || '-'}</td>
      <td style="max-width:200px;">${c.issue}</td>
      <td><span class="status-badge ${c.priority === 'urgent' ? 'critical' : c.priority}">${c.priority}</span></td>
      <td>${c.worker_name || '<span style="color:var(--text-light)">Unassigned</span>'}</td>
      <td><span class="status-badge ${c.status}">${c.status}</span></td>
      <td>
        <button class="btn btn-outline" onclick="viewComplaint(${JSON.stringify(c).replace(/"/g,'&quot;')})"><i class="fas fa-eye"></i></button>
        <button class="btn btn-warning" onclick="openAssignComplaintModal(${c.id}, ${c.assigned_worker_id || 'null'})" title="Assign Worker"><i class="fas fa-user-plus"></i></button>
        ${c.worker_name ? `<button class="btn btn-danger" onclick="removeWorkerFromComplaint(${c.id})" title="Remove Worker"><i class="fas fa-user-minus"></i></button>` : ''}
        ${c.status !== 'resolved' ? `<button class="btn btn-success" onclick="updateComplaint(${c.id},'resolved')"><i class="fas fa-check"></i></button>` : ''}
      </td>
    </tr>
  `).join('');
}

function viewComplaint(c) {
  openModal(`<i class="fas fa-comment-dots"></i> ${c.complaint_id}`, `
    <div style="display:grid;gap:8px;font-size:13px;">
      <div><strong style="color:var(--white);">Citizen:</strong> ${c.citizen_name} (${c.citizen_phone || ''})</div>
      <div><strong style="color:var(--white);">Pole:</strong> ${c.pole_code || '-'}</div>
      <div><strong style="color:var(--white);">Issue:</strong> ${c.issue}</div>
      <div><strong style="color:var(--white);">Priority:</strong> <span class="status-badge ${c.priority === 'urgent' ? 'critical' : c.priority}">${c.priority}</span></div>
      <div><strong style="color:var(--white);">Worker:</strong> ${c.worker_name || 'Unassigned'}</div>
      <div><strong style="color:var(--white);">Status:</strong> <span class="status-badge ${c.status}">${c.status}</span></div>
      <div><strong style="color:var(--white);">Remarks:</strong> ${c.remarks || 'None'}</div>
    </div>
    <div style="margin-top:15px;display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn btn-warning" onclick="openAssignComplaintModal(${c.id}, ${c.assigned_worker_id || 'null'});closeModal();"><i class="fas fa-user-plus"></i> Assign Worker</button>
      ${c.worker_name ? `<button class="btn btn-danger" onclick="removeWorkerFromComplaint(${c.id});closeModal();"><i class="fas fa-user-minus"></i> Remove Worker</button>` : ''}
      <button class="btn btn-success" onclick="updateComplaint(${c.id},'resolved');closeModal();"><i class="fas fa-check"></i> Resolve</button>
      <button class="btn btn-danger" onclick="updateComplaint(${c.id},'escalated');closeModal();"><i class="fas fa-arrow-up"></i> Escalate</button>
    </div>
  `);
}

async function openAssignComplaintModal(id, currentWorkerId) {
  if (!allWorkers.length) {
    const res = await api('workers');
    if (res.success) allWorkers = res.data;
  }
  const workerOptions = allWorkers.map(w => `<option value="${w.id}" ${w.id == currentWorkerId ? 'selected' : ''}>${w.name} (${w.zone} Zone)</option>`).join('');
  openModal('<i class="fas fa-user-plus"></i> Assign Worker to Complaint', `
    <div class="form-group">
      <label>Select Worker</label>
      <select id="complaintWorkerSelect">
        <option value="">-- Select Worker --</option>
        ${workerOptions}
      </select>
    </div>
    <button class="btn btn-primary" onclick="assignComplaintWorker(${id})"><i class="fas fa-save"></i> Assign Worker</button>
  `);
}

async function assignComplaintWorker(id) {
  const wid = document.getElementById('complaintWorkerSelect').value;
  if (!wid) { showToast('Please select a worker', 'warning'); return; }
  await api('complaint_update', { body: { id, assigned_worker_id: parseInt(wid) } });
  showToast('Worker assigned to complaint', 'success');
  closeModal();
  loadComplaints();
}

async function removeWorkerFromComplaint(id) {
  if (!confirm('Are you sure you want to remove the assigned worker from this complaint?')) return;
  await api('complaint_update', { body: { id, assigned_worker_id: null, status: 'open' } });
  showToast('Worker removed from complaint', 'success');
  loadComplaints();
}

async function updateComplaint(id, status) {
  await api('complaint_update', { body: { id, status } });
  loadComplaints();
}

function openAddComplaintModal() {
  openModal('<i class="fas fa-plus"></i> New Complaint', `
    <div class="form-group"><label>Citizen Name</label><input type="text" id="ncName" placeholder="Full Name"></div>
    <div class="form-group"><label>Phone</label><input type="text" id="ncPhone" placeholder="+91..."></div>
    <div class="form-group"><label>Email</label><input type="email" id="ncEmail" placeholder="email@example.com"></div>
    <div class="form-group"><label>Issue</label><textarea id="ncIssue" rows="3" placeholder="Describe the issue..."></textarea></div>
    <div class="form-group"><label>Category</label><select id="ncCat"><option value="safety">Safety</option><option value="electrical">Electrical</option><option value="structural">Structural</option><option value="lighting">Lighting</option><option value="noise">Noise</option><option value="other">Other</option></select></div>
    <div class="form-group"><label>Priority</label><select id="ncPri"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
    <button class="btn btn-primary" onclick="addComplaint()"><i class="fas fa-save"></i> Submit Complaint</button>
  `);
}

async function addComplaint() {
  const res = await api('complaints', { body: {
    citizen_name: document.getElementById('ncName').value,
    citizen_phone: document.getElementById('ncPhone').value,
    citizen_email: document.getElementById('ncEmail').value,
    issue: document.getElementById('ncIssue').value,
    category: document.getElementById('ncCat').value,
    priority: document.getElementById('ncPri').value,
  }});
  alert(res.message); closeModal(); loadComplaints();
}

// ==================== MATERIALS ====================
async function loadMaterials() {
  const res = await api('admin_material_requests');
  if (!res.success) return;
  
  const filter = document.getElementById('materialStatusFilter').value;
  let data = res.data;
  if (filter) data = data.filter(m => m.status === filter);
  
  document.getElementById('materialsBody').innerHTML = data.map(m => `
    <tr>
      <td>#${m.id}</td>
      <td>${m.worker_name} <br><small style="color:var(--text-light)">${m.worker_code}</small></td>
      <td><strong>${m.material_name}</strong><br><small style="color:var(--text-light)">${m.notes || ''}</small></td>
      <td>${m.quantity}</td>
      <td><span class="status-badge ${m.urgency === 'high' ? 'high' : (m.urgency === 'medium' ? 'medium' : 'normal')}">${m.urgency}</span></td>
      <td>${new Date(m.created_at).toLocaleDateString()}</td>
      <td><span class="status-badge ${m.status === 'pending' ? 'medium' : (m.status === 'approved' ? 'normal' : (m.status === 'rejected' ? 'high' : 'normal'))}">${m.status}</span></td>
      <td>
        ${m.status === 'pending' ? `
          <button class="btn btn-success" onclick="updateMaterial(${m.id}, 'approved')" style="padding:4px 8px;font-size:10px;"><i class="fas fa-check"></i> Approve</button>
          <button class="btn btn-danger" onclick="updateMaterial(${m.id}, 'rejected')" style="padding:4px 8px;font-size:10px;"><i class="fas fa-times"></i> Reject</button>
        ` : (m.status === 'approved' ? `
          <button class="btn btn-primary" onclick="updateMaterial(${m.id}, 'delivered')" style="padding:4px 8px;font-size:10px;"><i class="fas fa-truck"></i> Mark Delivered</button>
        ` : '-')}
      </td>
    </tr>
  `).join('') || '<tr><td colspan="8" style="text-align:center;">No material requests found.</td></tr>';
}

async function updateMaterial(id, status) {
  if (!confirm(`Are you sure you want to mark this request as ${status}?`)) return;
  const res = await api('admin_material_request_update', { body: { id, status } });
  showToast(res.message, status === 'rejected' ? 'danger' : 'success');
  loadMaterials();
}

// ==================== HELPERS ====================
function timeAgo(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  const now = new Date();
  const diff = Math.floor((now - d) / 1000);
  if (diff < 60) return diff + ' sec ago';
  if (diff < 3600) return Math.floor(diff/60) + ' min ago';
  if (diff < 86400) return Math.floor(diff/3600) + ' hr ago';
  return Math.floor(diff/86400) + ' days ago';
}

// Toast notification system
function showToast(message, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success:'fa-check-circle', danger:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${message}`;
  container.appendChild(toast);
  setTimeout(() => { toast.remove(); }, 3000);
}

// ==================== ACCELEROMETER ====================
let accelChartInst = null;
let accelMagChartInst = null;
let accelLiveInterval = null;
let accelLiveRunning = false;

async function loadAccelPoleOptions() {
  const res = await api('poles');
  if (!res.success) return;
  const sel = document.getElementById('accelPoleFilter');
  if (!sel) return;
  const current = sel.value;
  sel.innerHTML = '<option value="0">All Poles</option>' + res.data.map(p => `<option value="${p.id}">${p.pole_id} - ${p.location}</option>`).join('');
  sel.value = current;
}

async function loadAccelReadings() {
  const poleId = document.getElementById('accelPoleFilter')?.value || 0;
  const params = { limit: 100 };
  if (parseInt(poleId)) params.pole_id = poleId;
  const res = await api('accelerometer_readings', { params });
  if (!res.success) return;
  const data = res.data;

  // Update table
  document.getElementById('accelReadingsBody').innerHTML = data.map(r => `
    <tr>
      <td>${r.recorded_at ? new Date(r.recorded_at).toLocaleString() : '-'}</td>
      <td><strong style="color:var(--white)">${r.pole_code || 'N/A'}</strong></td>
      <td>${parseFloat(r.accel_x).toFixed(4)}</td>
      <td>${parseFloat(r.accel_y).toFixed(4)}</td>
      <td>${parseFloat(r.accel_z).toFixed(4)}</td>
      <td><strong style="color:var(--secondary)">${parseFloat(r.magnitude).toFixed(4)}</strong></td>
      <td style="font-family:monospace;font-size:11px;">${r.source_ip || '-'}</td>
    </tr>
  `).join('') || '<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No accelerometer readings yet. Connect ESP32 circuit to start.</td></tr>';

  // Update live stats with latest reading
  if (data.length > 0) {
    const latest = data[0];
    document.getElementById('liveAccelX').textContent = parseFloat(latest.accel_x).toFixed(4);
    document.getElementById('liveAccelY').textContent = parseFloat(latest.accel_y).toFixed(4);
    document.getElementById('liveAccelZ').textContent = parseFloat(latest.accel_z).toFixed(4);
    document.getElementById('liveAccelMag').textContent = parseFloat(latest.magnitude).toFixed(4);
  }

  // Update charts
  const reversed = [...data].reverse().slice(-30);
  const labels = reversed.map(r => r.recorded_at ? new Date(r.recorded_at).toLocaleTimeString() : '');
  const xData = reversed.map(r => parseFloat(r.accel_x));
  const yData = reversed.map(r => parseFloat(r.accel_y));
  const zData = reversed.map(r => parseFloat(r.accel_z));
  const magData = reversed.map(r => parseFloat(r.magnitude));

  if (accelChartInst) accelChartInst.destroy();
  accelChartInst = new Chart(document.getElementById('accelChart'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'X-Axis', data: xData, borderColor: '#00cec9', backgroundColor: 'rgba(0,206,201,0.1)', tension: 0.3, pointRadius: 2, borderWidth: 2 },
        { label: 'Y-Axis', data: yData, borderColor: '#00b894', backgroundColor: 'rgba(0,184,148,0.1)', tension: 0.3, pointRadius: 2, borderWidth: 2 },
        { label: 'Z-Axis', data: zData, borderColor: '#fdcb6e', backgroundColor: 'rgba(253,203,110,0.1)', tension: 0.3, pointRadius: 2, borderWidth: 2 }
      ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#b2bec3' } }, title: { display: true, text: 'X / Y / Z Axes', color: '#dfe6e9' } }, scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#636e72', maxTicksLimit: 10 } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#636e72' } } } }
  });

  if (accelMagChartInst) accelMagChartInst.destroy();
  accelMagChartInst = new Chart(document.getElementById('accelMagChart'), {
    type: 'line',
    data: {
      labels,
      datasets: [{ label: 'Magnitude', data: magData, borderColor: '#6c5ce7', backgroundColor: 'rgba(108,92,231,0.15)', fill: true, tension: 0.3, pointRadius: 2, borderWidth: 2 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#b2bec3' } }, title: { display: true, text: 'Magnitude (g)', color: '#dfe6e9' } }, scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#636e72', maxTicksLimit: 10 } }, y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#636e72' } } } }
  });
}

function toggleAccelLive() {
  if (accelLiveRunning) {
    clearInterval(accelLiveInterval);
    accelLiveInterval = null;
    accelLiveRunning = false;
    document.getElementById('accelLiveBtnText').textContent = 'Start Live';
    document.getElementById('accelLiveBtn').classList.remove('connected');
    showToast('Live accelerometer monitoring stopped', 'info');
  } else {
    if (!espConnected) {
      showToast('Connect ESP-32 first to get live readings!', 'warning');
      return;
    }
    accelLiveRunning = true;
    document.getElementById('accelLiveBtnText').textContent = 'Stop Live';
    document.getElementById('accelLiveBtn').style.background = 'linear-gradient(135deg,var(--danger),#b71c1c)';
    showToast('Live accelerometer monitoring started', 'success');
    fetchAccelLive();
    accelLiveInterval = setInterval(fetchAccelLive, 2000);
  }
}

async function fetchAccelLive() {
  if (!espConnected || !espIp) { toggleAccelLive(); return; }
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 3000);
    const res = await fetch(`http://${espIp}/accelerometer`, { signal: controller.signal });
    clearTimeout(timeout);
    if (res.ok) {
      const data = await res.json();
      const x = parseFloat(data.accel_x || data.x || 0);
      const y = parseFloat(data.accel_y || data.y || 0);
      const z = parseFloat(data.accel_z || data.z || 0);
      const mag = Math.sqrt(x*x + y*y + z*z);

      document.getElementById('liveAccelX').textContent = x.toFixed(4);
      document.getElementById('liveAccelY').textContent = y.toFixed(4);
      document.getElementById('liveAccelZ').textContent = z.toFixed(4);
      document.getElementById('liveAccelMag').textContent = mag.toFixed(4);

      // Save to backend
      const poleId = document.getElementById('accelPoleFilter')?.value || 0;
      await api('accelerometer_submit', { body: { pole_id: parseInt(poleId) || 0, accel_x: x, accel_y: y, accel_z: z } });
      loadAccelReadings();
    }
  } catch (e) {
    console.warn('Accelerometer fetch failed:', e);
  }
}

// ==================== ESP-32 CONNECTION ====================
let espConnected = false;
let espIp = '';
let espCheckInterval = null;

function espToggleConnection() {
  if (espConnected) {
    espDisconnect();
  } else {
    espConnect();
  }
}

async function espConnect() {
  const ip = document.getElementById('espIpInput').value.trim();
  if (!ip) { showToast('Please enter ESP-32 Server IP', 'warning'); return; }
  const btn = document.getElementById('espConnectBtn');
  document.getElementById('espBtnText').textContent = 'Connecting...';
  btn.classList.add('busy');
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    const res = await fetch(`http://${ip}/status`, { signal: controller.signal });
    clearTimeout(timeout);
    if (res.ok) {
      espIp = ip;
      espConnected = true;
      document.getElementById('espDot').className = 'esp-dot on';
      document.getElementById('espStatusText').textContent = 'Connected';
      document.getElementById('espBtnText').textContent = 'Disconnect';
      btn.classList.add('connected');
      document.getElementById('espIpInput').disabled = true;
      showToast('ESP-32 Connected successfully!', 'success');
      espCheckInterval = setInterval(espHeartbeat, 10000);
    } else {
      showToast('ESP-32 responded with error', 'danger');
    }
  } catch (e) {
    showToast('Cannot reach ESP-32. Check IP and network.', 'danger');
  }
  btn.classList.remove('busy');
}

function espDisconnect() {
  espConnected = false;
  espIp = '';
  if (espCheckInterval) { clearInterval(espCheckInterval); espCheckInterval = null; }
  // Stop accelerometer live if running
  if (accelLiveRunning) toggleAccelLive();
  const btn = document.getElementById('espConnectBtn');
  document.getElementById('espDot').className = 'esp-dot off';
  document.getElementById('espStatusText').textContent = 'Disconnected';
  document.getElementById('espBtnText').textContent = 'Connect';
  btn.classList.remove('connected');
  btn.classList.remove('busy');
  document.getElementById('espIpInput').disabled = false;
  showToast('ESP-32 Disconnected', 'info');
}

async function espHeartbeat() {
  if (!espConnected) return;
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 4000);
    await fetch(`http://${espIp}/status`, { signal: controller.signal });
    clearTimeout(timeout);
  } catch (e) {
    espDisconnect();
    showToast('ESP-32 connection lost!', 'danger');
  }
}

async function espSendCommand(action) {
  if (!espConnected) return;
  const endpoint = action === 'cutoff' ? '/off' : '/on';
  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    await fetch(`http://${espIp}${endpoint}`, { signal: controller.signal });
    clearTimeout(timeout);
  } catch (e) {
    console.warn('ESP-32 command failed:', e);
  }
}

// ESP-32 commands are now integrated directly into all power functions above

// ==================== INIT ====================
loadDashboard();

// Auto-refresh every 30s
setInterval(() => {
  const activePage = document.querySelector('.page.active');
  if (activePage) {
    const id = activePage.id.replace('page-', '');
    if (id === 'dashboard') loadDashboard();
  }
}, 30000);

// Dock Navigation Handler
document.querySelectorAll('.dock-item').forEach(item => {
  item.addEventListener('click', function(e) {
    e.preventDefault();
    const href = this.getAttribute('href');
    if (href) {
      window.location.href = href;
    }
  });
});

// Update active dock item based on current page
function updateActiveDockItem() {
  const currentPage = document.querySelector('.page.active')?.id.replace('page-', '');
  document.querySelectorAll('.dock-item').forEach(item => {
    item.classList.remove('active');
    const page = item.getAttribute('data-page');
    if (page === currentPage) {
      item.classList.add('active');
    }
  });
}
updateActiveDockItem();
</script>

<!-- Dock Navigation -->
<div class="dock-outer">
  <div class="dock-panel">
    <a href="javascript:void(0)" onclick="showPage('dashboard',null)" class="dock-item active" data-page="dashboard" title="Dashboard">
      <i class="fas fa-home dock-icon"></i>
      <span class="dock-label">Dashboard</span>
    </a>
    <a href="javascript:void(0)" onclick="showPage('poles',null)" class="dock-item" data-page="poles" title="All Poles">
      <i class="fas fa-broadcast-tower dock-icon"></i>
      <span class="dock-label">Poles</span>
    </a>
    <a href="javascript:void(0)" onclick="showPage('alerts',null)" class="dock-item" data-page="alerts" title="Alerts">
      <i class="fas fa-exclamation-triangle dock-icon"></i>
      <span class="dock-label">Alerts</span>
    </a>
    <a href="javascript:void(0)" onclick="showPage('workers',null)" class="dock-item" data-page="workers" title="Workers">
      <i class="fas fa-hard-hat dock-icon"></i>
      <span class="dock-label">Workers</span>
    </a>
    <a href="javascript:void(0)" onclick="showPage('polesMap',null)" class="dock-item" data-page="polesMap" title="Map">
      <i class="fas fa-map dock-icon"></i>
      <span class="dock-label">Map</span>
    </a>
    <a href="javascript:void(0)" onclick="logout()" class="dock-item" title="Logout">
      <i class="fas fa-sign-out-alt dock-icon"></i>
      <span class="dock-label">Logout</span>
    </a>
  </div>
</div>

</body>
</html>
