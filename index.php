<?php session_start(); 
// If already logged in, redirect to appropriate panel
if (!empty($_SESSION['user_id'])) {
    if (in_array($_SESSION['role'], ['admin', 'viewer'])) { header('Location: admin.php'); exit; }
    else { header('Location: worker.php'); exit; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Pole Management - Welcome</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#6c5ce7;--primary-dark:#5a4bd1;--secondary:#00cec9;--accent:#00b894;--danger:#d63031;--success:#00b894;--dark:#2d3436;--darker:#1e272e;--bg:#0a0a1a;--card:#12122a;--card2:#1a1a3e;--border:rgba(108,92,231,0.2);--text:#b2bec3;--text-light:#636e72;--white:#fff;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#0a0a1a 0%,#1a1a3e 50%,#0a0a1a 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;color:var(--text);}
::-webkit-scrollbar{width:6px;} ::-webkit-scrollbar-track{background:var(--darker);} ::-webkit-scrollbar-thumb{background:var(--primary);border-radius:3px;}

.portal-container{width:600px;max-width:95vw;}
.portal-logo{text-align:center;margin-bottom:35px;}
.portal-logo i{font-size:55px;color:var(--primary);margin-bottom:12px;filter:drop-shadow(0 0 25px rgba(108,92,231,0.5));}
.portal-logo h1{font-size:28px;color:var(--white);font-weight:700;}
.portal-logo p{color:var(--text-light);font-size:14px;margin-top:6px;}

.portal-cards{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;}
.portal-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:35px 25px;text-align:center;text-decoration:none;color:var(--text);transition:all .3s;position:relative;overflow:hidden;}
.portal-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;transition:height .3s;}
.portal-card.admin-card::before{background:linear-gradient(135deg,var(--primary),var(--primary-dark));}
.portal-card.worker-card::before{background:linear-gradient(135deg,var(--secondary),var(--accent));}
.portal-card:hover{transform:translateY(-5px);box-shadow:0 15px 40px rgba(0,0,0,0.4);}
.portal-card:hover::before{height:4px;}
.portal-card.admin-card:hover{border-color:rgba(108,92,231,0.4);}
.portal-card.worker-card:hover{border-color:rgba(0,206,201,0.4);}

.portal-card .card-icon{width:70px;height:70px;margin:0 auto 18px;border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:28px;}
.portal-card.admin-card .card-icon{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:var(--white);box-shadow:0 8px 25px rgba(108,92,231,0.3);}
.portal-card.worker-card .card-icon{background:linear-gradient(135deg,var(--secondary),var(--accent));color:var(--white);box-shadow:0 8px 25px rgba(0,206,201,0.3);}

.portal-card h3{color:var(--white);font-size:18px;font-weight:700;margin-bottom:8px;}
.portal-card p{font-size:13px;color:var(--text-light);line-height:1.5;margin-bottom:18px;}
.portal-card .enter-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 22px;border-radius:8px;font-size:13px;font-weight:600;color:var(--white);transition:all .2s;}
.portal-card.admin-card .enter-btn{background:rgba(108,92,231,0.2);border:1px solid rgba(108,92,231,0.3);}
.portal-card.admin-card:hover .enter-btn{background:var(--primary);}
.portal-card.worker-card .enter-btn{background:rgba(0,206,201,0.2);border:1px solid rgba(0,206,201,0.3);}
.portal-card.worker-card:hover .enter-btn{background:var(--secondary);}

.portal-footer{text-align:center;font-size:12px;color:var(--text-light);}
.portal-footer a{color:var(--primary);text-decoration:none;font-weight:600;}
.portal-footer a:hover{color:var(--secondary);}

.particles{position:fixed;width:100%;height:100%;overflow:hidden;z-index:-1;top:0;left:0;}
.particle{position:absolute;width:4px;height:4px;background:var(--primary);border-radius:50%;opacity:0.3;animation:float linear infinite;}
@keyframes float{0%{transform:translateY(100vh) rotate(0deg);opacity:0;}10%{opacity:0.3;}90%{opacity:0.3;}100%{transform:translateY(-100vh) rotate(720deg);opacity:0;}}

@media(max-width:500px){
  .portal-cards{grid-template-columns:1fr;}
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

<div class="particles" id="particles"></div>

<div class="portal-container">
  <div class="portal-logo">
    <i class="fas fa-broadcast-tower"></i>
    <h1>Smart Pole Management</h1>
    <p>Intelligent Infrastructure Monitoring System</p>
  </div>

  <div class="portal-cards">
    <a href="admin_login.php" class="portal-card admin-card">
      <div class="card-icon"><i class="fas fa-user-shield"></i></div>
      <h3>Admin Portal</h3>
      <p>Access the admin dashboard to manage poles, workers, alerts and system settings.</p>
      <span class="enter-btn"><i class="fas fa-arrow-right"></i> Admin Login</span>
    </a>
    <a href="worker_login.php" class="portal-card worker-card">
      <div class="card-icon"><i class="fas fa-hard-hat"></i></div>
      <h3>Worker Portal</h3>
      <p>View assigned tasks, update maintenance progress and submit completion reports.</p>
      <span class="enter-btn"><i class="fas fa-arrow-right"></i> Worker Login</span>
    </a>
  </div>

  <div class="portal-footer">
    <p>Don't have an account? <a href="register.php">Register here</a></p>
  </div>
</div>

<script>
(function(){
  const c=document.getElementById('particles');
  for(let i=0;i<30;i++){
    const p=document.createElement('div');
    p.className='particle';
    p.style.left=Math.random()*100+'%';
    p.style.animationDuration=(Math.random()*15+10)+'s';
    p.style.animationDelay=(Math.random()*10)+'s';
    p.style.width=p.style.height=(Math.random()*4+2)+'px';
    c.appendChild(p);
  }
})();

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
</script>

<!-- Dock Navigation -->
<div class="dock-outer">
  <div class="dock-panel">
    <a href="index.php" class="dock-item active" title="Home">
      <i class="fas fa-home dock-icon"></i>
      <span class="dock-label">Home</span>
    </a>
    <a href="admin_login.php" class="dock-item" title="Admin Portal">
      <i class="fas fa-user-shield dock-icon"></i>
      <span class="dock-label">Admin</span>
    </a>
    <a href="worker_login.php" class="dock-item" title="Worker Portal">
      <i class="fas fa-hard-hat dock-icon"></i>
      <span class="dock-label">Worker</span>
    </a>
    <a href="register.php" class="dock-item" title="Register">
      <i class="fas fa-user-plus dock-icon"></i>
      <span class="dock-label">Register</span>
    </a>
  </div>
</div>

</body>
</html>
