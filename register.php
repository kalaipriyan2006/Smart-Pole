<?php session_start();
// If already logged in, redirect
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') { header('Location: admin.php'); exit; }
    else { header('Location: worker.php'); exit; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Pole Management - Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#6c5ce7;--primary-dark:#5a4bd1;--secondary:#00cec9;--danger:#d63031;--success:#00b894;--dark:#2d3436;--darker:#1e272e;--bg:#0a0a1a;--card:rgba(18,18,42,0.85);--border:rgba(108,92,231,0.3);--text:#b2bec3;--text-light:#636e72;--white:#fff;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',system-ui,sans-serif;background:url('assets/images/bg.jpg') center/cover no-repeat fixed;min-height:100vh;display:flex;align-items:center;justify-content:center;color:var(--text);padding:20px 0;position:relative;}
body::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(135deg,rgba(10,10,26,0.9) 0%,rgba(26,26,62,0.8) 50%,rgba(10,10,26,0.9) 100%);z-index:-1;}
::-webkit-scrollbar{width:6px;} ::-webkit-scrollbar-track{background:var(--darker);} ::-webkit-scrollbar-thumb{background:var(--primary);border-radius:3px;}

.register-container{width:480px;max-width:95vw;position:relative;z-index:1;}
.register-logo{text-align:center;margin-bottom:30px;}
.register-logo i{font-size:50px;color:var(--primary);margin-bottom:10px;filter:drop-shadow(0 0 20px rgba(108,92,231,0.5));}
.register-logo h1{font-size:24px;color:var(--white);font-weight:700;text-shadow:0 2px 10px rgba(0,0,0,0.5);}
.register-logo p{color:var(--text-light);font-size:13px;margin-top:5px;text-shadow:0 1px 5px rgba(0,0,0,0.5);}
.register-card{background:var(--card);backdrop-filter:blur(15px);-webkit-backdrop-filter:blur(15px);border:1px solid var(--border);border-radius:16px;padding:35px;box-shadow:0 20px 60px rgba(0,0,0,0.6);}
.register-tabs{display:flex;gap:0;margin-bottom:25px;background:var(--darker);border-radius:10px;padding:4px;}
.register-tab{flex:1;padding:10px;text-align:center;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;color:var(--text-light);transition:all .3s;border:none;background:none;}
.register-tab.active{background:var(--primary);color:var(--white);box-shadow:0 4px 15px rgba(108,92,231,0.4);}
.register-tab:hover:not(.active){color:var(--white);}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;color:var(--text);margin-bottom:6px;font-weight:500;}
.form-group input,.form-group select{width:100%;padding:12px 15px;background:var(--darker);border:1px solid var(--border);border-radius:10px;color:var(--white);font-size:14px;transition:border .3s;}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(108,92,231,0.15);}
.form-group select option{background:var(--darker);color:var(--white);}
.form-row{display:flex;gap:12px;}
.form-row .form-group{flex:1;}
.input-icon{position:relative;}
.input-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:14px;}
.input-icon input,.input-icon select{padding-left:42px;}
.input-icon .toggle-pass{position:absolute;right:14px;left:auto;cursor:pointer;background:none;border:none;color:var(--text-light);font-size:14px;top:50%;transform:translateY(-50%);}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:var(--white);width:100%;padding:12px 24px;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 15px rgba(108,92,231,0.4);transition:all .3s;margin-top:8px;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 25px rgba(108,92,231,0.5);}
.btn-primary:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.register-footer{text-align:center;margin-top:20px;font-size:13px;color:var(--text-light);}
.register-footer a{color:var(--primary);text-decoration:none;font-weight:600;}
.register-footer a:hover{text-decoration:underline;}
.error-msg{background:rgba(214,48,49,0.15);color:var(--danger);padding:10px 15px;border-radius:8px;font-size:13px;margin-bottom:15px;display:none;}
.error-msg.show{display:block;}
.success-msg{background:rgba(0,184,148,0.15);color:var(--success);padding:10px 15px;border-radius:8px;font-size:13px;margin-bottom:15px;display:none;}
.success-msg.show{display:block;}
.worker-fields{display:block;} .worker-fields.hidden{display:none;}
.password-strength{height:4px;border-radius:2px;margin-top:6px;background:var(--darker);overflow:hidden;}
.password-strength .bar{height:100%;width:0;border-radius:2px;transition:all .3s;}
.strength-text{font-size:11px;margin-top:3px;color:var(--text-light);}

.particles{position:fixed;width:100%;height:100%;overflow:hidden;z-index:-1;top:0;left:0;}
.particle{position:absolute;width:4px;height:4px;background:var(--primary);border-radius:50%;opacity:0.3;animation:float linear infinite;}
@keyframes float{0%{transform:translateY(100vh) rotate(0deg);opacity:0;}10%{opacity:0.3;}90%{opacity:0.3;}100%{transform:translateY(-100vh) rotate(720deg);opacity:0;}}
</style>
</head>
<body>

<div class="particles" id="particles"></div>

<div class="register-container">
  <div class="register-logo">
    <i class="fas fa-broadcast-tower"></i>
    <h1>Smart Pole Management</h1>
    <p>Create your account</p>
  </div>
  <div class="register-card">
    <div class="register-tabs">
      <button class="register-tab active" data-role="worker" onclick="switchTab('worker',this)">
        <i class="fas fa-hard-hat"></i> Worker
      </button>
      <button class="register-tab" data-role="admin" onclick="switchTab('admin',this)">
        <i class="fas fa-user-shield"></i> Admin
      </button>
    </div>

    <div class="error-msg" id="errorMsg"></div>
    <div class="success-msg" id="successMsg"></div>

    <form id="registerForm" onsubmit="handleRegister(event)">
      <input type="hidden" id="regRole" value="worker">

      <div class="form-group">
        <label>Full Name</label>
        <div class="input-icon">
          <i class="fas fa-user"></i>
          <input type="text" id="regName" placeholder="Enter your full name" required>
        </div>
      </div>

      <div class="form-group">
        <label>Email</label>
        <div class="input-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" id="regEmail" placeholder="Enter your email" required>
        </div>
      </div>

      <div class="form-group">
        <label>Phone</label>
        <div class="input-icon">
          <i class="fas fa-phone"></i>
          <input type="tel" id="regPhone" placeholder="Enter your phone number">
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input type="password" id="regPassword" placeholder="Min 6 characters" required minlength="6" oninput="checkStrength(this.value)">
          <button type="button" class="toggle-pass" onclick="togglePassword('regPassword')"><i class="fas fa-eye"></i></button>
        </div>
        <div class="password-strength"><div class="bar" id="strengthBar"></div></div>
        <div class="strength-text" id="strengthText"></div>
      </div>

      <div class="form-group">
        <label>Confirm Password</label>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input type="password" id="regConfirmPassword" placeholder="Confirm your password" required minlength="6">
          <button type="button" class="toggle-pass" onclick="togglePassword('regConfirmPassword')"><i class="fas fa-eye"></i></button>
        </div>
      </div>

      <div class="worker-fields" id="workerFields">
        <div class="form-group">
          <label>Zone</label>
          <div class="input-icon">
            <i class="fas fa-map-marker-alt"></i>
            <select id="regZone">
              <option value="north">North Zone</option>
              <option value="south">South Zone</option>
              <option value="east">East Zone</option>
              <option value="west">West Zone</option>
            </select>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-primary" id="registerBtn">
        <i class="fas fa-user-plus"></i> Create Account
      </button>
    </form>

    <div class="register-footer">
      <p>Already have an account? <a href="index.php">Sign In</a></p>
    </div>
  </div>
</div>

<script>
// Particles
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

let currentRole = 'worker';

function switchTab(role, el) {
  currentRole = role;
  document.getElementById('regRole').value = role;
  document.querySelectorAll('.register-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');

  const workerFields = document.getElementById('workerFields');
  if (role === 'worker') {
    workerFields.classList.remove('hidden');
  } else {
    workerFields.classList.add('hidden');
  }
}

function togglePassword(fieldId) {
  const inp = document.getElementById(fieldId);
  const icon = inp.parentElement.querySelector('.toggle-pass i');
  if (inp.type === 'password') { inp.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
  else { inp.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}

function checkStrength(pass) {
  const bar = document.getElementById('strengthBar');
  const text = document.getElementById('strengthText');
  let score = 0;

  if (pass.length >= 6) score++;
  if (pass.length >= 10) score++;
  if (/[A-Z]/.test(pass)) score++;
  if (/[0-9]/.test(pass)) score++;
  if (/[^A-Za-z0-9]/.test(pass)) score++;

  const levels = [
    { width: '0%', color: 'transparent', label: '' },
    { width: '20%', color: '#d63031', label: 'Very Weak' },
    { width: '40%', color: '#e17055', label: 'Weak' },
    { width: '60%', color: '#fdcb6e', label: 'Fair' },
    { width: '80%', color: '#00cec9', label: 'Strong' },
    { width: '100%', color: '#00b894', label: 'Very Strong' }
  ];

  const level = levels[score];
  bar.style.width = level.width;
  bar.style.background = level.color;
  text.textContent = level.label;
  text.style.color = level.color;
}

async function handleRegister(e) {
  e.preventDefault();
  const btn = document.getElementById('registerBtn');
  const errEl = document.getElementById('errorMsg');
  const sucEl = document.getElementById('successMsg');
  errEl.classList.remove('show');
  sucEl.classList.remove('show');

  const name = document.getElementById('regName').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const phone = document.getElementById('regPhone').value.trim();
  const password = document.getElementById('regPassword').value;
  const confirmPassword = document.getElementById('regConfirmPassword').value;
  const role = document.getElementById('regRole').value;
  const zone = document.getElementById('regZone').value;

  // Client-side validation
  if (password !== confirmPassword) {
    errEl.textContent = 'Passwords do not match';
    errEl.classList.add('show');
    return;
  }

  if (password.length < 6) {
    errEl.textContent = 'Password must be at least 6 characters';
    errEl.classList.add('show');
    return;
  }

  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
  btn.disabled = true;

  try {
    const res = await fetch('api.php?action=register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, phone, password, role, zone })
    });
    const data = await res.json();

    if (data.success) {
      sucEl.textContent = data.message || 'Registration successful! Redirecting to login...';
      sucEl.classList.add('show');
      document.getElementById('registerForm').reset();
      checkStrength('');

      // Redirect to login after 2 seconds
      setTimeout(() => {
        window.location.href = 'index.php';
      }, 2000);
    } else {
      errEl.textContent = data.message || 'Registration failed';
      errEl.classList.add('show');
    }
  } catch (err) {
    errEl.textContent = 'Connection error. Make sure XAMPP MySQL is running.';
    errEl.classList.add('show');
  }

  btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
  btn.disabled = false;
}
</script>
</body>
</html>
