<?php session_start();
// If already logged in as admin/viewer, go to admin panel
if (!empty($_SESSION['user_id']) && in_array($_SESSION['role'], ['admin', 'viewer'])) {
    header('Location: admin.php'); exit;
}
// If logged in but not admin, go to their panel
if (!empty($_SESSION['user_id']) && $_SESSION['role'] === 'worker') {
    header('Location: worker.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Pole - Admin Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#6c5ce7;--primary-dark:#5a4bd1;--secondary:#00cec9;--danger:#d63031;--success:#00b894;--dark:#2d3436;--darker:#1e272e;--bg:#0a0a1a;--card:rgba(18,18,42,0.85);--border:rgba(108,92,231,0.3);--text:#b2bec3;--text-light:#636e72;--white:#fff;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:url('assets/images/bg.jpg') center/cover no-repeat fixed;min-height:100vh;display:flex;align-items:center;justify-content:center;color:var(--text);position:relative;}
body::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(135deg,rgba(10,10,26,0.9) 0%,rgba(26,26,62,0.8) 50%,rgba(10,10,26,0.9) 100%);z-index:-1;}
::-webkit-scrollbar{width:6px;} ::-webkit-scrollbar-track{background:var(--darker);} ::-webkit-scrollbar-thumb{background:var(--primary);border-radius:3px;}

.login-container{width:440px;max-width:95vw;position:relative;z-index:1;}
.login-logo{text-align:center;margin-bottom:30px;}
.login-logo .icon-wrapper{width:80px;height:80px;margin:0 auto 15px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 40px rgba(108,92,231,0.4);}
.login-logo .icon-wrapper i{font-size:36px;color:var(--white);}
.login-logo h1{font-size:24px;color:var(--white);font-weight:700;text-shadow:0 2px 10px rgba(0,0,0,0.5);}
.login-logo p{color:var(--text-light);font-size:13px;margin-top:5px;text-shadow:0 1px 5px rgba(0,0,0,0.5);}
.login-logo .role-badge{display:inline-block;margin-top:8px;padding:4px 14px;border-radius:20px;background:rgba(108,92,231,0.3);color:var(--primary);font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;backdrop-filter:blur(5px);}

.login-card{background:var(--card);backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px);border:1px solid var(--border);border-radius:16px;padding:35px;box-shadow:0 20px 60px rgba(0,0,0,0.6);}
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-size:13px;color:var(--text);margin-bottom:6px;font-weight:500;}
.form-group input{width:100%;padding:12px 15px;background:var(--darker);border:1px solid var(--border);border-radius:10px;color:var(--white);font-size:14px;transition:border .3s;}
.form-group input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(108,92,231,0.15);}
.input-icon{position:relative;}
.input-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:14px;}
.input-icon input{padding-left:42px;}
.input-icon .toggle-pass{position:absolute;right:14px;left:auto;top:50%;transform:translateY(-50%);cursor:pointer;background:none;border:none;color:var(--text-light);font-size:14px;}

.btn-login{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:var(--white);width:100%;padding:13px 24px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 15px rgba(108,92,231,0.4);transition:all .3s;margin-top:5px;}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 6px 25px rgba(108,92,231,0.5);}
.btn-login:disabled{opacity:0.6;cursor:not-allowed;transform:none;}

.error-msg{background:rgba(214,48,49,0.15);color:var(--danger);padding:10px 15px;border-radius:8px;font-size:13px;margin-bottom:15px;display:none;align-items:center;gap:8px;}
.error-msg.show{display:flex;}
.error-msg i{font-size:14px;}

.login-footer{text-align:center;margin-top:20px;font-size:12px;color:var(--text-light);}
.login-footer a{color:var(--primary);text-decoration:none;font-weight:600;transition:color .2s;}
.login-footer a:hover{color:var(--secondary);}

.switch-panel{text-align:center;margin-top:18px;}
.switch-panel a{display:inline-flex;align-items:center;gap:6px;color:var(--secondary);text-decoration:none;font-size:13px;font-weight:500;padding:8px 16px;border-radius:8px;transition:all .2s;border:1px solid rgba(0,206,201,0.2);}
.switch-panel a:hover{background:rgba(0,206,201,0.1);border-color:var(--secondary);}

.particles{position:fixed;width:100%;height:100%;overflow:hidden;z-index:-1;top:0;left:0;}
.particle{position:absolute;width:4px;height:4px;background:var(--primary);border-radius:50%;opacity:0.3;animation:float linear infinite;}
@keyframes float{0%{transform:translateY(100vh) rotate(0deg);opacity:0;}10%{opacity:0.3;}90%{opacity:0.3;}100%{transform:translateY(-100vh) rotate(720deg);opacity:0;}}

.remember-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;font-size:13px;}
.remember-row label{display:flex;align-items:center;gap:6px;cursor:pointer;color:var(--text);}
.remember-row input[type=checkbox]{accent-color:var(--primary);width:15px;height:15px;}
</style>
</head>
<body>

<div class="particles" id="particles"></div>

<div class="login-container">
  <div class="login-logo">
    <div class="icon-wrapper">
      <i class="fas fa-user-shield"></i>
    </div>
    <h1>Smart Pole Management</h1>
    <p>Intelligent Infrastructure Monitoring System</p>
    <span class="role-badge"><i class="fas fa-shield-halved"></i> Admin Portal</span>
  </div>
  <div class="login-card">
    <div class="error-msg" id="errorMsg"><i class="fas fa-exclamation-circle"></i><span id="errorText"></span></div>
    <form id="loginForm" onsubmit="handleLogin(event)">
      <div class="form-group">
        <label><i class="fas fa-envelope" style="margin-right:5px;font-size:12px;"></i>Email Address</label>
        <div class="input-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" id="loginEmail" placeholder="admin@smart.com" required autocomplete="email">
        </div>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock" style="margin-right:5px;font-size:12px;"></i>Password</label>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input type="password" id="loginPassword" placeholder="Enter your password" required autocomplete="current-password">
          <button type="button" class="toggle-pass" onclick="togglePassword()"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="remember-row">
        <label><input type="checkbox" id="rememberMe"> Remember me</label>
      </div>
      <button type="submit" class="btn-login" id="loginBtn">
        <i class="fas fa-sign-in-alt"></i> Sign In as Admin
      </button>
    </form>
    <div class="login-footer">
      <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
  </div>
  <div class="switch-panel">
    <a href="worker_login.php"><i class="fas fa-hard-hat"></i> Switch to Worker Login</a>
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

function togglePassword() {
  const inp = document.getElementById('loginPassword');
  const icon = document.querySelector('.toggle-pass i');
  if (inp.type === 'password') { inp.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
  else { inp.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}

async function handleLogin(e) {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  const errEl = document.getElementById('errorMsg');
  const errText = document.getElementById('errorText');
  errEl.classList.remove('show');

  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;

  if (!email || !password) {
    errText.textContent = 'Please enter both email and password.';
    errEl.classList.add('show');
    return;
  }

  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
  btn.disabled = true;

  try {
    const res = await fetch('api.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    const data = await res.json();

    if (data.success) {
      const role = data.data.role;
      if (role === 'admin' || role === 'viewer') {
        window.location.href = 'admin.php';
      } else {
        errText.textContent = 'Access denied. This login is for administrators only. Please use the Worker Login.';
        errEl.classList.add('show');
        fetch('api.php?action=logout');
      }
    } else {
      errText.textContent = data.message || 'Login failed. Please check your credentials.';
      errEl.classList.add('show');
    }
  } catch (err) {
    errText.textContent = 'Connection error. Make sure XAMPP and MySQL are running.';
    errEl.classList.add('show');
  }

  btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In as Admin';
  btn.disabled = false;
}
</script>
</body>
</html>
