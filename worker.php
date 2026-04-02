<?php session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header('Location: worker_login.php'); exit;
}
$userName = htmlspecialchars($_SESSION['name']);
$userInitial = strtoupper(substr($userName, 0, 1));
$workerId = $_SESSION['worker_id'] ?? 0;
$workerUid = $_SESSION['worker_uid'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Pole - Worker Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--primary:#6c5ce7;--primary-dark:#5a4bd1;--secondary:#00cec9;--danger:#d63031;--warning:#fdcb6e;--success:#00b894;--dark:#2d3436;--darker:#1e272e;--white:#fff;--bg:#0a0a1a;--card:#12122a;--card2:#1a1a3e;--sidebar-w:260px;--border:rgba(108,92,231,0.2);--text:#b2bec3;--text-light:#636e72;--green:#00b894;--yellow:#fdcb6e;--red:#d63031;--purple:#6c5ce7;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden;}
::-webkit-scrollbar{width:6px;} ::-webkit-scrollbar-track{background:var(--darker);} ::-webkit-scrollbar-thumb{background:var(--secondary);border-radius:3px;}

.app{display:flex;min-height:100vh;}
.sidebar{width:var(--sidebar-w);background:var(--card);border-right:1px solid var(--border);position:fixed;top:0;left:0;height:100vh;overflow-y:auto;z-index:1000;transition:transform .3s;}
.sidebar-header{padding:20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.sidebar-header .logo-icon{width:40px;height:40px;background:linear-gradient(135deg,var(--secondary),var(--green));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--white);}
.sidebar-header .logo-text h3{color:var(--white);font-size:15px;}
.sidebar-header .logo-text span{color:var(--text-light);font-size:11px;}
.sidebar-nav{padding:15px 10px;}
.nav-section{margin-bottom:8px;}
.nav-section-title{padding:8px 15px;font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:var(--text-light);font-weight:700;}
.nav-item{display:flex;align-items:center;gap:12px;padding:11px 15px;border-radius:10px;cursor:pointer;transition:all .2s;color:var(--text);font-size:13px;margin-bottom:2px;}
.nav-item:hover{background:rgba(0,206,201,0.1);color:var(--white);}
.nav-item.active{background:linear-gradient(135deg,var(--secondary),var(--green));color:var(--white);box-shadow:0 4px 15px rgba(0,206,201,0.3);}
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
.user-menu .avatar{width:35px;height:35px;border-radius:10px;background:linear-gradient(135deg,var(--secondary),var(--green));display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-size:14px;}
.user-menu .user-info{line-height:1.3;}
.user-menu .user-info .name{color:var(--white);font-size:13px;font-weight:600;}
.user-menu .user-info .role{color:var(--text-light);font-size:11px;}
.content{padding:25px;}
.page{display:none;}
.page.active{display:block;animation:fadeIn .3s ease;}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:25px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px;transition:transform .2s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(0,0,0,0.3);}
.stat-card .stat-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
.stat-card .stat-icon{width:45px;height:45px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.stat-card .stat-icon.green{background:rgba(0,184,148,0.15);color:var(--green);}
.stat-card .stat-icon.red{background:rgba(214,48,49,0.15);color:var(--red);}
.stat-card .stat-icon.yellow{background:rgba(253,203,110,0.15);color:var(--yellow);}
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
table tr:hover td{background:rgba(0,206,201,0.03);}
.status-badge{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;}
.status-badge.normal,.status-badge.resolved,.status-badge.completed,.status-badge.active,.status-badge.approved{background:rgba(0,184,148,0.15);color:var(--green);}
.status-badge.medium,.status-badge.in_progress,.status-badge.in-progress,.status-badge.pending,.status-badge.assigned{background:rgba(253,203,110,0.15);color:var(--yellow);}
.status-badge.high,.status-badge.open,.status-badge.offline,.status-badge.rejected{background:rgba(214,48,49,0.15);color:var(--red);}
.status-badge.critical,.status-badge.escalated{background:rgba(108,92,231,0.15);color:var(--purple);}

.btn{padding:8px 16px;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:linear-gradient(135deg,var(--secondary),var(--green));color:var(--white);}
.btn-success{background:var(--success);color:var(--white);}
.btn-danger{background:var(--danger);color:var(--white);}
.btn-warning{background:var(--warning);color:var(--dark);}
.btn-outline{background:transparent;border:1px solid var(--secondary);color:var(--secondary);}
.btn-outline:hover{background:var(--secondary);color:var(--white);}
.btn-secondary{background:var(--card2);color:var(--text);border:1px solid var(--border);}

.task-card{background:var(--card2);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:12px;transition:border .2s;}
.task-card:hover{border-color:var(--secondary);}
.task-card .task-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
.task-card .task-id{color:var(--secondary);font-weight:700;font-size:14px;}
.task-card .task-details{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;}
.task-card .task-detail{font-size:12px;}
.task-card .task-detail span{color:var(--text-light);}
.task-card .task-detail strong{color:var(--white);margin-left:4px;}
.task-card .task-actions{display:flex;gap:8px;flex-wrap:wrap;}

.charts-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;margin-bottom:25px;}
.chart-wrapper{height:280px;position:relative;}

.risk-bar{height:8px;border-radius:4px;background:var(--darker);overflow:hidden;margin-top:5px;}
.risk-bar .fill{height:100%;border-radius:4px;transition:width .5s;}
.risk-bar .fill.normal{background:var(--green);width:25%;}
.risk-bar .fill.medium{background:var(--yellow);width:50%;}
.risk-bar .fill.high{background:var(--red);width:75%;}
.risk-bar .fill.critical{background:var(--purple);width:100%;}

.map-container{width:100%;height:500px;border-radius:14px;overflow:hidden;border:1px solid var(--border);}
#worker-map{width:100%;height:100%;}

.filters-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center;}
.filters-bar input,.filters-bar select{padding:9px 14px;background:var(--card);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:13px;}
.filters-bar input:focus,.filters-bar select:focus{outline:none;border-color:var(--secondary);}

.profile-header{display:flex;gap:25px;align-items:center;margin-bottom:30px;}
.profile-avatar{width:100px;height:100px;border-radius:20px;background:linear-gradient(135deg,var(--secondary),var(--green));display:flex;align-items:center;justify-content:center;font-size:40px;color:var(--white);font-weight:800;}
.profile-info h2{color:var(--white);font-size:22px;margin-bottom:4px;}
.profile-info p{color:var(--text-light);font-size:14px;}
.profile-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:15px;margin-top:15px;}
.profile-stat{text-align:center;padding:15px;background:var(--card2);border-radius:10px;}
.profile-stat .val{font-size:24px;font-weight:800;color:var(--white);}
.profile-stat .lbl{font-size:11px;color:var(--text-light);margin-top:4px;}

.timeline{position:relative;padding-left:30px;}
.timeline::before{content:'';position:absolute;left:10px;top:0;bottom:0;width:2px;background:var(--border);}
.timeline-item{position:relative;margin-bottom:20px;}
.timeline-item::before{content:'';position:absolute;left:-24px;top:4px;width:12px;height:12px;border-radius:50%;background:var(--secondary);border:2px solid var(--card);}
.timeline-item .time{font-size:11px;color:var(--text-light);}
.timeline-item .desc{font-size:13px;color:var(--white);margin-top:3px;}

.proof-upload{border:2px dashed var(--border);border-radius:12px;padding:30px;text-align:center;cursor:pointer;transition:border .3s;}
.proof-upload:hover{border-color:var(--secondary);}
.proof-upload i{font-size:40px;color:var(--text-light);margin-bottom:10px;}
.proof-upload p{color:var(--text-light);font-size:13px;}

.form-group{margin-bottom:15px;}
.form-group label{display:block;font-size:13px;color:var(--text);margin-bottom:6px;font-weight:500;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;background:var(--darker);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:13px;}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--secondary);}

.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;z-index:20000;opacity:0;pointer-events:none;transition:opacity .3s;}
.modal-overlay.active{opacity:1;pointer-events:all;}
.modal{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:30px;width:500px;max-width:95vw;max-height:85vh;overflow-y:auto;transform:scale(0.9);transition:transform .3s;}
.modal-overlay.active .modal{transform:scale(1);}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.modal-header h3{color:var(--white);font-size:18px;}
.modal-close{background:none;border:none;color:var(--text-light);font-size:20px;cursor:pointer;}

.pulse{animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.5;}}

/* ESP32 Connection Widget */
.esp-connect-bar{display:flex;align-items:center;gap:12px;background:var(--card2);border:1px solid var(--border);border-radius:12px;padding:6px 16px;margin-right:8px;}
.esp-connect-bar label{font-size:12px;color:var(--text-light);font-weight:600;white-space:nowrap;}
.esp-connect-bar input[type="text"]{width:150px;padding:6px 12px;background:var(--darker);border:1px solid var(--border);border-radius:8px;color:var(--white);font-size:13px;font-family:monospace;box-sizing:border-box;}
.esp-connect-bar input[type="text"]:focus{outline:none;border-color:var(--secondary);}
.esp-connect-bar .esp-connect-btn{all:unset;box-sizing:border-box;display:inline-flex;align-items:center;gap:6px;padding:7px 18px;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;transition:all .3s;color:var(--white);background:linear-gradient(135deg,#00b4db,#0083b0);white-space:nowrap;line-height:normal;}
.esp-connect-bar .esp-connect-btn:hover{filter:brightness(1.1);}
.esp-connect-bar .esp-connect-btn.connected{background:linear-gradient(135deg,var(--danger),#b71c1c);}
.esp-connect-bar .esp-connect-btn.busy{opacity:0.5;pointer-events:none;cursor:not-allowed;}
.esp-connect-bar .esp-status{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;white-space:nowrap;}
.esp-connect-bar .esp-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
.esp-connect-bar .esp-dot.off{background:var(--danger);}
.esp-connect-bar .esp-dot.on{background:var(--green);animation:pulse 2s infinite;}
@media(max-width:900px){.esp-connect-bar{display:none;}}

/* Toast notifications */
.toast-container{position:fixed;top:80px;right:25px;z-index:30000;display:flex;flex-direction:column;gap:10px;}
.toast{padding:14px 22px;border-radius:12px;color:var(--white);font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 30px rgba(0,0,0,0.4);animation:toastIn .4s ease,toastOut .4s ease 2.6s forwards;min-width:280px;}
.toast.success{background:linear-gradient(135deg,#00b894,#00a381);}
.toast.danger{background:linear-gradient(135deg,#d63031,#b71c1c);}
.toast.warning{background:linear-gradient(135deg,#fdcb6e,#f39c12);color:#2d3436;}
.toast.info{background:linear-gradient(135deg,#00cec9,#00b5b0);}
.toast i{font-size:16px;}
@keyframes toastIn{from{opacity:0;transform:translateX(100px);}to{opacity:1;transform:translateX(0);}}
@keyframes toastOut{from{opacity:1;transform:translateX(0);}to{opacity:0;transform:translateX(100px);}}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .main{margin-left:0;}
  .topbar-left .menu-toggle{display:block;}
  .stats-grid{grid-template-columns:1fr 1fr;}
  .charts-grid{grid-template-columns:1fr;}
  .profile-header{flex-direction:column;text-align:center;}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr;}
  .content{padding:15px;}
}

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
  border-color: #00cec9;
  transform: scale(1.1);
  box-shadow: 0 8px 20px rgba(0, 206, 201, 0.3);
}

.dock-item.active {
  background-color: #00cec9;
  border-color: #00cec9;
  color: #fff;
  box-shadow: 0 8px 25px rgba(0, 206, 201, 0.5);
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
      <div class="logo-icon"><i class="fas fa-hard-hat"></i></div>
      <div class="logo-text"><h3>SmartPole</h3><span>Worker Panel</span></div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-section-title">Main</div>
        <div class="nav-item active" onclick="showPage('dashboard',this)"><i class="fas fa-th-large"></i> Dashboard</div>
        <div class="nav-item" onclick="showPage('tasks',this)"><i class="fas fa-tasks"></i> My Tasks <span class="badge" id="taskBadge">0</span></div>
        <div class="nav-item" onclick="showPage('riskLevels',this)"><i class="fas fa-tachometer-alt"></i> Risk Levels</div>
        <div class="nav-item" onclick="showPage('map',this)"><i class="fas fa-map-marked-alt"></i> Poles Map</div>
        <div class="nav-item" onclick="showPage('complaints',this)"><i class="fas fa-comment-dots"></i> Complaints <span class="badge" id="complaintBadge">0</span></div>
        <div class="nav-item" onclick="showPage('materials',this)"><i class="fas fa-toolbox"></i> Material Requests</div>
        <div class="nav-item" onclick="showPage('accelerometer',this)"><i class="fas fa-wave-square"></i> Accelerometer <span class="badge" style="background:var(--secondary)">Live</span></div>
        <div class="nav-item" onclick="showPage('profile',this)"><i class="fas fa-user"></i> My Profile</div>
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
          <label>ESP-32 IP</label>
          <input type="text" id="espIpInput" placeholder="e.g. 192.168.1.100" value="" autocomplete="off">
          <button class="esp-connect-btn" id="espConnectBtn" onclick="espToggleConnection()">
            <i class="fas fa-plug"></i> <span id="espBtnText">Connect</span>
          </button>
          <div class="esp-status">
            <span class="esp-dot off" id="espDot"></span>
            <span id="espStatusText">Disconnected</span>
          </div>
        </div>
        <div class="notif-btn"><i class="fas fa-bell"></i><div class="dot pulse"></div></div>
        <div class="user-menu" onclick="logout()">
          <div class="avatar"><?= $userInitial ?></div>
          <div class="user-info"><div class="name"><?= $userName ?></div><div class="role">Field Worker</div></div>
          <i class="fas fa-sign-out-alt" style="color:var(--text-light);margin-left:8px;"></i>
        </div>
      </div>
    </div>

    <div class="content">

      <!-- WORKER DASHBOARD -->
      <div class="page active" id="page-dashboard">
        <div class="stats-grid" id="dashStats"></div>
        <div class="charts-grid">
          <div class="card"><div class="card-header"><h3>My Weekly Performance</h3></div><div class="chart-wrapper"><canvas id="perfChart"></canvas></div></div>
          <div class="card"><div class="card-header"><h3>Task Status Overview</h3></div><div class="chart-wrapper"><canvas id="taskChart"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-header"><h3>Assigned Poles Overview</h3></div>
          <div class="table-wrapper">
            <table><thead><tr><th>Pole ID</th><th>Location</th><th>Zone</th><th>Vibration</th><th>Temp</th><th>Risk</th><th>Power</th></tr></thead><tbody id="assignedPolesBody"></tbody></table>
          </div>
        </div>
      </div>

      <!-- MY TASKS -->
      <div class="page" id="page-tasks">
        <div class="filters-bar">
          <input type="text" placeholder="🔍 Search tasks..." id="taskSearch">
          <select id="taskStatusFilter" onchange="loadTasks()"><option value="">All Status</option><option value="pending">Not Done</option><option value="in_progress">In Progress</option><option value="completed">Done</option></select>
        </div>
        <div id="tasksList"></div>
      </div>

      <!-- RISK LEVELS -->
      <div class="page" id="page-riskLevels">
        <div class="stats-grid" id="workerRiskStats"></div>
        <div class="card">
          <div class="card-header"><h3>My Assigned Poles - Risk Levels</h3></div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Pole ID</th><th>Location</th><th>Zone</th><th>Vibration</th><th>Temp</th><th>Voltage</th><th>Risk Score</th><th>Risk Level</th><th>Action Required</th><th>Power Status</th><th>Actions</th></tr></thead>
              <tbody id="workerRiskBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- WORKER MAP -->
      <div class="page" id="page-map">
        <div class="card" style="padding:12px;margin-bottom:15px;">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <span style="color:var(--white);font-weight:600;"><i class="fas fa-info-circle" style="color:var(--secondary);"></i> Showing only your assigned poles</span>
            <button class="btn btn-outline" onclick="openGoogleMaps()"><i class="fas fa-directions"></i> Open in Google Maps</button>
          </div>
        </div>
        <div class="map-container"><div id="worker-map"></div></div>
      </div>

      <!-- COMPLAINTS -->
      <div class="page" id="page-complaints">
        <div class="filters-bar">
          <input type="text" placeholder="🔍 Search complaints..." id="compSearch">
          <select id="compStatusFilter" onchange="loadComplaints()"><option value="">All Status</option><option value="open">Open</option><option value="assigned">Not Resolved</option><option value="resolved">Resolved</option></select>
        </div>
        <div id="complaintsList"></div>
      </div>

      <!-- MATERIAL REQUESTS -->
      <div class="page" id="page-materials">
        <div class="card">
          <div class="card-header"><h3>Request New Material</h3></div>
          <div style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="flex:1; min-width:200px; margin-bottom:0;">
              <label>Material Name</label>
              <select id="matName">
                <option value="">Select Material...</option>
                <option value="LED Bulb 50W">LED Bulb 50W</option>
                <option value="LED Bulb 100W">LED Bulb 100W</option>
                <option value="Copper Wire (10m)">Copper Wire (10m)</option>
                <option value="Copper Wire (50m)">Copper Wire (50m)</option>
                <option value="Vibration Sensor">Vibration Sensor</option>
                <option value="Temperature Sensor">Temperature Sensor</option>
                <option value="Voltage Sensor">Voltage Sensor</option>
                <option value="Fuse Box">Fuse Box</option>
                <option value="Circuit Breaker">Circuit Breaker</option>
                <option value="Pole Paint (1L)">Pole Paint (1L)</option>
                <option value="Screws & Nuts Pack">Screws & Nuts Pack</option>
                <option value="Insulation Tape">Insulation Tape</option>
                <option value="Other">Other (Specify in notes)</option>
              </select>
            </div>
            <div class="form-group" style="width:100px; margin-bottom:0;">
              <label>Quantity</label>
              <input type="number" id="matQty" min="1" value="1">
            </div>
            <div class="form-group" style="width:150px; margin-bottom:0;">
              <label>Urgency</label>
              <select id="matUrgency">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>
            <button class="btn btn-primary" onclick="submitMaterialRequest()" style="height:40px;"><i class="fas fa-paper-plane"></i> Submit Request</button>
          </div>
          <div class="form-group" style="margin-top:15px; margin-bottom:0;">
            <label>Additional Notes (Optional)</label>
            <input type="text" id="matNotes" placeholder="Specify details if 'Other' is selected or add any notes...">
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3>My Requests</h3></div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>ID</th><th>Material</th><th>Qty</th><th>Urgency</th><th>Status</th><th>Date</th></tr></thead>
              <tbody id="materialsBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ACCELEROMETER READINGS -->
      <div class="page" id="page-accelerometer">
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
          <div class="stat-card"><div class="stat-header"><div class="stat-icon blue"><i class="fas fa-arrows-alt-h"></i></div></div><div class="stat-value" id="liveAccelX">--</div><div class="stat-label">X-Axis (g)</div></div>
          <div class="stat-card"><div class="stat-header"><div class="stat-icon green"><i class="fas fa-arrows-alt-v"></i></div></div><div class="stat-value" id="liveAccelY">--</div><div class="stat-label">Y-Axis (g)</div></div>
          <div class="stat-card"><div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-compress-arrows-alt"></i></div></div><div class="stat-value" id="liveAccelZ">--</div><div class="stat-label">Z-Axis (g)</div></div>
          <div class="stat-card"><div class="stat-header"><div class="stat-icon purple"><i class="fas fa-tachometer-alt"></i></div><span class="status-badge" style="background:rgba(108,92,231,0.15);color:var(--purple);" id="accelStatusBadge">Inactive</span></div><div class="stat-value" id="liveAccelMag">--</div><div class="stat-label">Magnitude (g)</div></div>
        </div>
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-wave-square"></i> Accelerometer Monitor</h3>
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
              <thead><tr><th>Time</th><th>Pole</th><th>X (g)</th><th>Y (g)</th><th>Z (g)</th><th>Magnitude (g)</th></tr></thead>
              <tbody id="accelReadingsBody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MY PROFILE -->
      <div class="page" id="page-profile">
        <div class="card">
          <div class="profile-header" id="profileHeader"></div>
          <div class="profile-stats" id="profileStats"></div>
        </div>
        <div class="card">
          <div class="card-header"><h3>Change Password</h3></div>
          <div class="form-group" style="max-width:400px;">
            <label>Current Password</label>
            <input type="password" id="currentPass" placeholder="Enter current password">
          </div>
          <div class="form-group" style="max-width:400px;">
            <label>New Password</label>
            <input type="password" id="newPass" placeholder="Enter new password">
          </div>
          <div class="form-group" style="max-width:400px;">
            <label>Confirm New Password</label>
            <input type="password" id="confirmPass" placeholder="Confirm new password">
          </div>
          <button class="btn btn-primary" onclick="changePassword()"><i class="fas fa-save"></i> Update Password</button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
const API = 'api.php';
const WORKER_ID = <?= $workerId ?>;
let workerMap = null;
let myPoles = [];
let perfChartInst = null;
let taskChartInst = null;

const pageTitles = { dashboard:'Dashboard', tasks:'My Tasks', riskLevels:'Risk Levels', map:'Poles Map', complaints:'Complaints', materials:'Material Requests', accelerometer:'Accelerometer', profile:'My Profile' };

function showPage(page, navItem) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('page-' + page).classList.add('active');
  if (navItem) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    navItem.classList.add('active');
  }
  document.getElementById('pageTitle').textContent = pageTitles[page] || 'Dashboard';
  document.getElementById('sidebar').classList.remove('open');

  switch(page) {
    case 'dashboard': loadDashboard(); break;
    case 'tasks': loadTasks(); break;
    case 'riskLevels': loadWorkerRiskLevels(); break;
    case 'map': setTimeout(() => initMap(), 200); break;
    case 'complaints': loadComplaints(); break;
    case 'materials': loadMaterials(); break;
    case 'accelerometer': loadAccelReadings(); loadAccelPoleOptions(); break;
    case 'profile': loadProfile(); break;
  }
}

function logout() {
  fetch(API + '?action=logout').then(() => window.location.href = 'worker_login.php');
}

async function api(action, opts = {}) {
  const url = API + '?action=' + action + (opts.params ? '&' + new URLSearchParams(opts.params) : '');
  const config = { method: opts.method || 'GET' };
  if (opts.body) {
    config.method = 'POST';
    config.headers = { 'Content-Type': 'application/json' };
    config.body = JSON.stringify(opts.body);
  }
  const res = await fetch(url, config);
  return res.json();
}

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
  const res = await api('worker_dashboard');
  if (!res.success) return;
  const d = res.data;
  myPoles = d.poles || [];

  document.getElementById('dashStats').innerHTML = `
    <div class="stat-card"><div class="stat-header"><div class="stat-icon blue"><i class="fas fa-broadcast-tower"></i></div></div><div class="stat-value">${d.assigned_poles}</div><div class="stat-label">Assigned Poles</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-clock"></i></div></div><div class="stat-value">${d.todays_tasks}</div><div class="stat-label">Pending Tasks</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div></div><div class="stat-value">${d.critical_alerts}</div><div class="stat-label">Critical Alerts</div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon green"><i class="fas fa-check-double"></i></div></div><div class="stat-value">${d.performance}%</div><div class="stat-label">Performance</div></div>
  `;

  document.getElementById('taskBadge').textContent = d.todays_tasks;

  // Assigned poles table
  document.getElementById('assignedPolesBody').innerHTML = myPoles.map(p => `
    <tr>
      <td><strong style="color:var(--white)">${p.pole_id}</strong></td>
      <td>${p.location}</td>
      <td style="text-transform:capitalize">${p.zone}</td>
      <td>${p.vibration || '-'} mm/s</td>
      <td>${p.temperature || '-'}°C</td>
      <td><span class="status-badge ${p.risk_level || 'normal'}">${p.risk_level || 'normal'}</span></td>
      <td><span class="status-badge ${p.power_status == 1 ? 'active' : 'offline'}">${p.power_status == 1 ? 'ON' : 'OFF'}</span></td>
    </tr>
  `).join('');

  initCharts(d);
}

function initCharts(d) {
  if (perfChartInst) perfChartInst.destroy();
  if (taskChartInst) taskChartInst.destroy();

  const wk = d.worker || {};
  const completed = wk.tasks_completed || 0;

  perfChartInst = new Chart(document.getElementById('perfChart'), {
    type: 'bar',
    data: {
      labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
      datasets: [{label:'Tasks Completed',data:[3,5,4,6,4,2,3],backgroundColor:'rgba(0,206,201,0.6)',borderColor:'#00cec9',borderWidth:1,borderRadius:6}]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#b2bec3'}}},scales:{x:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#636e72'}},y:{grid:{color:'rgba(255,255,255,0.05)'},ticks:{color:'#636e72'}}}}
  });

  taskChartInst = new Chart(document.getElementById('taskChart'), {
    type: 'pie',
    data: {
      labels: ['Completed','Pending','In Progress'],
      datasets: [{data:[completed, d.todays_tasks, Math.max(1, Math.floor(d.todays_tasks/2))],backgroundColor:['#00b894','#d63031','#fdcb6e'],borderWidth:0}]
    },
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:'#b2bec3',font:{size:11},padding:15}}}}
  });
}

// ==================== TASKS ====================
async function loadTasks() {
  const status = document.getElementById('taskStatusFilter')?.value || '';
  const res = await api('tasks', { params: { worker_id: WORKER_ID, status } });
  if (!res.success) return;

  document.getElementById('tasksList').innerHTML = res.data.map(t => `
    <div class="task-card">
      <div class="task-top">
        <div class="task-id">${t.task_id}</div>
        <span class="status-badge ${t.status}">${t.status.replace(/_/g,' ')}</span>
      </div>
      <div class="task-details">
        <div class="task-detail"><span>Pole:</span><strong>${t.pole_code}</strong></div>
        <div class="task-detail"><span>Location:</span><strong>${t.location}</strong></div>
        <div class="task-detail"><span>Issue:</span><strong>${t.title}</strong></div>
        <div class="task-detail"><span>Priority:</span><strong><span class="status-badge ${t.priority}">${t.priority}</span></strong></div>
        <div class="task-detail"><span>Type:</span><strong>${t.task_type}</strong></div>
        <div class="task-detail"><span>Due:</span><strong>${t.due_date || '-'}</strong></div>
      </div>
      <div class="task-actions">
        ${t.status === 'pending' ? `
          <button class="btn btn-primary" onclick="updateTask(${t.id},'in_progress')"><i class="fas fa-play"></i> Start Task</button>
          <button class="btn btn-success" onclick="updateTask(${t.id},'completed')"><i class="fas fa-check-circle"></i> Mark Done</button>
        ` : ''}
        ${t.status === 'in_progress' ? `
          <button class="btn btn-success" onclick="updateTask(${t.id},'completed')"><i class="fas fa-check-circle"></i> Mark Done</button>
          <button class="btn btn-danger" onclick="updateTask(${t.id},'pending')"><i class="fas fa-times-circle"></i> Not Done</button>
          <button class="btn btn-secondary" onclick="openUploadProof(${t.id}, ${t.pole_id})"><i class="fas fa-camera"></i> Upload Proof</button>
        ` : ''}
        ${t.status === 'completed' ? `
          <button class="btn btn-success" style="opacity:0.8" disabled><i class="fas fa-check-double"></i> Done</button>
          <button class="btn btn-danger" onclick="updateTask(${t.id},'pending')"><i class="fas fa-undo"></i> Not Done</button>
        ` : ''}
        <button class="btn btn-outline" onclick="navigateTo(${t.pole_id})"><i class="fas fa-map-marker-alt"></i> Navigate</button>
      </div>
    </div>
  `).join('') || '<div style="text-align:center;padding:40px;color:var(--text-light);">No tasks found.</div>';
}

async function updateTask(id, status) {
  await api('task_update', { body: { id, status } });
  loadTasks();
  loadDashboard();
  if (status === 'completed') showToast('Task marked as Done!', 'success');
  if (status === 'pending') showToast('Task marked as Not Done!', 'warning');
}

function openUploadProof(taskId, poleId) {
  openModal('<i class="fas fa-camera"></i> Upload Proof', `
    <form id="proofForm" enctype="multipart/form-data">
      <input type="hidden" name="task_id" value="${taskId}">
      <input type="hidden" name="pole_id" value="${poleId}">
      <input type="hidden" name="worker_id" value="${WORKER_ID}">
      <div class="form-group">
        <label>Proof Type</label>
        <select name="proof_type" id="proofType">
          <option value="before_repair">Before Repair</option>
          <option value="during_repair">During Repair</option>
          <option value="after_repair">After Repair</option>
          <option value="inspection">Inspection</option>
        </select>
      </div>
      <div class="proof-upload" onclick="document.getElementById('proofFile').click()">
        <i class="fas fa-cloud-upload-alt"></i>
        <p>Click to upload photo</p>
        <input type="file" id="proofFile" name="proof_file" accept="image/*" style="display:none" onchange="previewFile(this)">
      </div>
      <div class="form-group" style="margin-top:15px;">
        <label>Remarks</label>
        <input type="text" name="remarks" placeholder="Add remarks...">
      </div>
      <button type="button" class="btn btn-primary" onclick="submitProof()"><i class="fas fa-upload"></i> Submit Proof</button>
    </form>
  `);
}

function previewFile(input) {
  if (input.files && input.files[0]) {
    input.closest('.proof-upload').innerHTML = `<i class="fas fa-check-circle" style="font-size:40px;color:var(--green);"></i><p style="color:var(--green);">Selected: ${input.files[0].name}</p>`;
    // Re-add hidden input
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'file'; hiddenInput.id = 'proofFile'; hiddenInput.name = 'proof_file';
    hiddenInput.style.display = 'none'; hiddenInput.files = input.files;
    input.closest('.proof-upload').appendChild(hiddenInput);
  }
}

async function submitProof() {
  const form = document.getElementById('proofForm');
  const formData = new FormData(form);
  const fileInput = document.getElementById('proofFile');
  if (fileInput && fileInput.files[0]) formData.set('proof_file', fileInput.files[0]);

  const res = await fetch(API + '?action=proofs', { method: 'POST', body: formData });
  const data = await res.json();
  showToast(data.message || 'Proof uploaded!', data.success ? 'success' : 'danger');
  closeModal();
}

// ==================== MAP ====================
function initMap() {
  if (workerMap) { workerMap.invalidateSize(); return; }
  workerMap = L.map('worker-map').setView([11.1271, 78.6569], 7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(workerMap);
  loadMapPoles();
}

async function loadMapPoles() {
  if (!myPoles.length) {
    const res = await api('worker_dashboard');
    if (res.success) myPoles = res.data.poles || [];
  }

  const colors = { normal:'#00b894', medium:'#fdcb6e', high:'#d63031', critical:'#6c5ce7' };

  myPoles.forEach(p => {
    const risk = p.risk_level || 'normal';
    const color = colors[risk] || colors.normal;
    const icon = L.divIcon({
      html:`<div style="background:${color};width:24px;height:24px;border-radius:50%;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;"><div style="width:8px;height:8px;background:white;border-radius:50%;"></div></div>`,
      className:'custom-marker', iconSize:[24,24], iconAnchor:[12,12], popupAnchor:[0,-15]
    });
    L.marker([p.latitude, p.longitude], { icon }).addTo(workerMap)
      .bindPopup(`<div style="font-family:Segoe UI,sans-serif;min-width:200px;"><h3 style="margin:0 0 8px;color:#333;font-size:14px;">${p.pole_id} - ${p.location}</h3><div style="font-size:12px;"><div><b>Vibration:</b> ${p.vibration||0} mm/s</div><div><b>Temp:</b> ${p.temperature||0}°C</div><div><b>Voltage:</b> ${p.voltage||0}V</div><div><b>Risk:</b> <span style="background:${color};color:white;padding:2px 8px;border-radius:10px;font-size:10px;">${risk.toUpperCase()}</span></div></div><a href="https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}" target="_blank" style="display:inline-block;margin-top:8px;background:#4285f4;color:white;padding:5px 10px;border-radius:5px;text-decoration:none;font-size:11px;">📍 Navigate</a></div>`);
  });

  if (myPoles.length > 0) {
    workerMap.fitBounds(myPoles.map(p => [p.latitude, p.longitude]), { padding: [30, 30] });
  }
}

function openGoogleMaps() {
  if (myPoles.length > 0) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${myPoles[0].latitude},${myPoles[0].longitude}`, '_blank');
  }
}

function navigateTo(poleId) {
  const p = myPoles.find(x => x.id == poleId);
  if (p) window.open(`https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}`, '_blank');
}

// ==================== COMPLAINTS ====================
async function loadComplaints() {
  const status = document.getElementById('compStatusFilter')?.value || '';
  const res = await api('complaints', { params: { worker_id: WORKER_ID, status } });
  if (!res.success) return;

  const data = res.data;
  document.getElementById('complaintBadge').textContent = data.filter(c => c.status !== 'resolved' && c.status !== 'closed').length;

  document.getElementById('complaintsList').innerHTML = data.map(c => `
    <div class="task-card">
      <div class="task-top">
        <div class="task-id">${c.complaint_id}</div>
        <span class="status-badge ${c.status}">${c.status}</span>
      </div>
      <div class="task-details">
        <div class="task-detail"><span>Citizen:</span><strong>${c.citizen_name}</strong></div>
        <div class="task-detail"><span>Pole:</span><strong>${c.pole_code || '-'}</strong></div>
        <div class="task-detail"><span>Issue:</span><strong>${c.issue}</strong></div>
        <div class="task-detail"><span>Priority:</span><strong><span class="status-badge ${c.priority === 'urgent' ? 'critical' : c.priority}">${c.priority}</span></strong></div>
        <div class="task-detail"><span>Category:</span><strong style="text-transform:capitalize">${c.category || '-'}</strong></div>
        <div class="task-detail"><span>Reported:</span><strong>${c.created_at ? new Date(c.created_at).toLocaleDateString() : '-'}</strong></div>
      </div>
      <div class="task-actions">
        ${c.status === 'resolved' || c.status === 'closed' ? `
          <button class="btn btn-success" style="opacity:0.8" disabled><i class="fas fa-check-double"></i> Resolved</button>
          <button class="btn btn-danger" onclick="unresolveComplaint(${c.id})"><i class="fas fa-undo"></i> Not Resolved</button>
        ` : `
          <button class="btn btn-success" onclick="resolveComplaint(${c.id})"><i class="fas fa-check"></i> Mark Resolved</button>
          <button class="btn btn-warning" onclick="unresolveComplaint(${c.id})"><i class="fas fa-times-circle"></i> Not Resolved</button>
          <button class="btn btn-secondary" onclick="openUploadProof(0, ${c.pole_id || 0})"><i class="fas fa-camera"></i> Upload Photo</button>
        `}
      </div>
    </div>
  `).join('') || '<div style="text-align:center;padding:40px;color:var(--text-light);">No complaints assigned.</div>';
}

async function resolveComplaint(id) {
  await api('complaint_update', { body: { id, status: 'resolved', remarks: 'Fixed by worker' } });
  loadComplaints();
  loadDashboard();
  showToast('Complaint marked as Resolved!', 'success');
}

async function unresolveComplaint(id) {
  await api('complaint_update', { body: { id, status: 'assigned', remarks: 'Reopened - not yet resolved' } });
  loadComplaints();
  loadDashboard();
  showToast('Complaint marked as Not Resolved!', 'warning');
}

// ==================== MATERIALS ====================
async function loadMaterials() {
  const res = await api('material_requests');
  if (!res.success) return;
  const data = res.data;
  document.getElementById('materialsBody').innerHTML = data.map(m => `
    <tr>
      <td>#${m.id}</td>
      <td><strong>${m.material_name}</strong></td>
      <td>${m.quantity}</td>
      <td><span class="status-badge ${m.urgency === 'high' ? 'high' : (m.urgency === 'medium' ? 'medium' : 'normal')}">${m.urgency}</span></td>
      <td><span class="status-badge ${m.status === 'pending' ? 'medium' : (m.status === 'approved' ? 'normal' : (m.status === 'rejected' ? 'high' : 'normal'))}">${m.status}</span></td>
      <td>${new Date(m.created_at).toLocaleDateString()}</td>
    </tr>
  `).join('') || '<tr><td colspan="6" style="text-align:center;">No material requests found.</td></tr>';
}

async function submitMaterialRequest() {
  const name = document.getElementById('matName').value;
  const qty = document.getElementById('matQty').value;
  const urgency = document.getElementById('matUrgency').value;
  const notes = document.getElementById('matNotes').value.trim();
  
  if (!name) return showToast('Please select a material', 'warning');
  
  const finalName = name === 'Other' ? `Other: ${notes}` : name;
  
  const res = await api('material_request_add', { body: { material_name: finalName, quantity: qty, urgency: urgency, notes: notes } });
  if (res.success) {
    showToast('Material request submitted successfully!', 'success');
    document.getElementById('matName').value = '';
    document.getElementById('matQty').value = '1';
    document.getElementById('matNotes').value = '';
    loadMaterials();
  } else {
    showToast(res.message || 'Failed to submit request', 'danger');
  }
}

// ==================== PROFILE ====================
async function loadProfile() {
  const res = await api('worker_dashboard');
  if (!res.success) return;
  const w = res.data.worker;
  if (!w) return;

  document.getElementById('profileHeader').innerHTML = `
    <div class="profile-avatar"><?= $userInitial ?></div>
    <div class="profile-info">
      <h2><?= $userName ?></h2>
      <p><i class="fas fa-id-badge"></i> Worker ID: ${w.worker_id}</p>
      <p><i class="fas fa-map-marker-alt"></i> ${w.zone.charAt(0).toUpperCase()+w.zone.slice(1)} Zone</p>
      <p><i class="fas fa-phone"></i> ${w.phone || '-'}</p>
      <p><i class="fas fa-cogs"></i> ${w.specialization || '-'}</p>
    </div>
  `;

  document.getElementById('profileStats').innerHTML = `
    <div class="profile-stat"><div class="val">${res.data.assigned_poles}</div><div class="lbl">Assigned Poles</div></div>
    <div class="profile-stat"><div class="val">${w.tasks_completed}</div><div class="lbl">Tasks Completed</div></div>
    <div class="profile-stat"><div class="val">${res.data.performance}%</div><div class="lbl">Performance</div></div>
    <div class="profile-stat"><div class="val">${w.rating}★</div><div class="lbl">Rating</div></div>
  `;
}

async function changePassword() {
  const current = document.getElementById('currentPass').value;
  const newP = document.getElementById('newPass').value;
  const confirm = document.getElementById('confirmPass').value;

  if (!current || !newP) return showToast('Please fill all fields', 'warning');
  if (newP !== confirm) return showToast('Passwords do not match', 'danger');

  const res = await api('change_password', { body: { current_password: current, new_password: newP } });
  showToast(res.message, res.success ? 'success' : 'danger');
  if (res.success) {
    document.getElementById('currentPass').value = '';
    document.getElementById('newPass').value = '';
    document.getElementById('confirmPass').value = '';
  }
}

// ==================== RISK LEVELS ====================
async function loadWorkerRiskLevels() {
  const res = await api('risk_levels');
  if (!res.success) return;
  const allData = res.data;
  const data = allData.filter(r => myPoles.some(p => p.pole_id === r.pole_code));
  let counts = { normal:0, medium:0, high:0, critical:0 };
  data.forEach(r => { if (counts[r.risk_level] !== undefined) counts[r.risk_level]++; });

  document.getElementById('workerRiskStats').innerHTML = `
    <div class="stat-card"><div class="stat-header"><div class="stat-icon green"><i class="fas fa-shield-alt"></i></div></div><div class="stat-value" style="color:var(--green);">${counts.normal}</div><div class="stat-label">Low Risk</div><div class="risk-bar"><div class="fill normal"></div></div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-exclamation"></i></div></div><div class="stat-value" style="color:var(--yellow);">${counts.medium}</div><div class="stat-label">Medium Risk</div><div class="risk-bar"><div class="fill medium"></div></div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon red"><i class="fas fa-radiation"></i></div></div><div class="stat-value" style="color:var(--red);">${counts.high}</div><div class="stat-label">High Risk</div><div class="risk-bar"><div class="fill high"></div></div></div>
    <div class="stat-card"><div class="stat-header"><div class="stat-icon purple"><i class="fas fa-skull-crossbones"></i></div></div><div class="stat-value" style="color:var(--purple);">${counts.critical}</div><div class="stat-label">Critical Risk</div><div class="risk-bar"><div class="fill critical"></div></div></div>
  `;

  document.getElementById('workerRiskBody').innerHTML = data.map(r => `
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
        <button class="btn btn-success ${r.power_status==1?'btn-disabled':''}" onclick="workerPowerOn(${r.p_id})" ${r.power_status==1?'disabled':''} style="padding:4px 8px;font-size:10px;"><i class="fas fa-plug"></i> ON</button>
        <button class="btn btn-danger ${r.power_status==0?'btn-disabled':''}" onclick="workerPowerOff(${r.p_id})" ${r.power_status==0?'disabled':''} style="padding:4px 8px;font-size:10px;"><i class="fas fa-power-off"></i> OFF</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="11" style="text-align:center;padding:30px;color:var(--text-light);">No risk data for your assigned poles.</td></tr>';
}

async function workerPowerOn(poleId) {
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action: 'restore', reason: 'Worker Power ON from Risk Levels' } });
  showToast(res.message || 'Power restored', 'success');
  await espSendCommand('restore');
  loadWorkerRiskLevels();
  loadDashboard();
}

async function workerPowerOff(poleId) {
  if (!confirm('Are you sure you want to Power OFF this pole?')) return;
  const res = await api('cutoff_toggle', { body: { pole_id: poleId, action: 'cutoff', reason: 'Worker Power OFF from Risk Levels' } });
  showToast(res.message || 'Power cut off', 'danger');
  await espSendCommand('cutoff');
  loadWorkerRiskLevels();
  loadDashboard();
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
  const res = await api('worker_dashboard');
  if (!res.success) return;
  const poles = res.data.poles || [];
  const sel = document.getElementById('accelPoleFilter');
  if (!sel) return;
  const current = sel.value;
  sel.innerHTML = '<option value="0">All Poles</option>' + poles.map(p => `<option value="${p.id}">${p.pole_id} - ${p.location}</option>`).join('');
  sel.value = current;
}

async function loadAccelReadings() {
  const poleId = document.getElementById('accelPoleFilter')?.value || 0;
  const params = { limit: 100 };
  if (parseInt(poleId)) params.pole_id = poleId;
  const res = await api('accelerometer_readings', { params });
  if (!res.success) return;
  const data = res.data;

  document.getElementById('accelReadingsBody').innerHTML = data.map(r => `
    <tr>
      <td>${r.recorded_at ? new Date(r.recorded_at).toLocaleString() : '-'}</td>
      <td><strong style="color:var(--white)">${r.pole_code || 'N/A'}</strong></td>
      <td>${parseFloat(r.accel_x).toFixed(4)}</td>
      <td>${parseFloat(r.accel_y).toFixed(4)}</td>
      <td>${parseFloat(r.accel_z).toFixed(4)}</td>
      <td><strong style="color:var(--secondary)">${parseFloat(r.magnitude).toFixed(4)}</strong></td>
    </tr>
  `).join('') || '<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No readings yet. Connect ESP32 circuit to start.</td></tr>';

  if (data.length > 0) {
    const latest = data[0];
    document.getElementById('liveAccelX').textContent = parseFloat(latest.accel_x).toFixed(4);
    document.getElementById('liveAccelY').textContent = parseFloat(latest.accel_y).toFixed(4);
    document.getElementById('liveAccelZ').textContent = parseFloat(latest.accel_z).toFixed(4);
    document.getElementById('liveAccelMag').textContent = parseFloat(latest.magnitude).toFixed(4);
  }

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
    document.getElementById('accelLiveBtn').style.background = '';
    document.getElementById('accelStatusBadge').textContent = 'Inactive';
    document.getElementById('accelStatusBadge').style.color = 'var(--text-light)';
    showToast('Live monitoring stopped', 'info');
  } else {
    if (!espConnected) {
      showToast('Connect ESP-32 first to get live readings!', 'warning');
      return;
    }
    accelLiveRunning = true;
    document.getElementById('accelLiveBtnText').textContent = 'Stop Live';
    document.getElementById('accelLiveBtn').style.background = 'linear-gradient(135deg,var(--danger),#b71c1c)';
    document.getElementById('accelStatusBadge').textContent = 'LIVE';
    document.getElementById('accelStatusBadge').style.color = 'var(--green)';
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

// ==================== INIT ====================
loadDashboard();

// Dock Navigation Handler
document.querySelectorAll('.dock-item').forEach(item => {
  item.addEventListener('click', function(e) {
    e.preventDefault();
    const href = this.getAttribute('href');
    if (href && !href.includes('javascript:void(0)')) {
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
    <a href="javascript:void(0)" onclick="showPage('tasks',null)" class="dock-item" data-page="tasks" title="My Tasks">
      <i class="fas fa-tasks dock-icon"></i>
      <span class="dock-label">Tasks</span>
    </a>
    <a href="javascript:void(0)" onclick="showPage('progress',null)" class="dock-item" data-page="progress" title="Progress">
      <i class="fas fa-chart-line dock-icon"></i>
      <span class="dock-label">Progress</span>
    </a>
    <a href="javascript:void(0)" onclick="showPage('profile',null)" class="dock-item" data-page="profile" title="Profile">
      <i class="fas fa-user dock-icon"></i>
      <span class="dock-label">Profile</span>
    </a>
    <a href="javascript:void(0)" onclick="logout()" class="dock-item" title="Logout">
      <i class="fas fa-sign-out-alt dock-icon"></i>
      <span class="dock-label">Logout</span>
    </a>
  </div>
</div>

</body>
</html>
