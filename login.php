<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login – Devilixirs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    body{
      margin:0;
      font-family:'Poppins',sans-serif;
      background:linear-gradient(120deg,#f8ebeb,#ffffff);
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:100vh;
    }

    .login-wrapper{
      background:#fff;
      width:400px;
      padding:40px;
      border-radius:12px;
      box-shadow:0 10px 30px rgba(0,0,0,0.08);
    }

    .login-wrapper h1{
        color: #d4af37;
      margin:0 0 8px;
      font-size:26px;
      font-weight:600;
    }

    .login-wrapper p{
      margin:0 0 25px;
      font-size:14px;
      color:#777;
    }

    .form-group{
      margin-bottom:18px;
    }

    .form-group label{
      font-size:13px;
      margin-bottom:6px;
      display:block;
    }

    .form-group input{
      width:100%;
      padding:10px 12px;
      border:1px solid #ddd;
      border-radius:6px;
      font-size:14px;
      outline:none;
    }

    .form-group input:focus{
      border-color:#b89026;
    }

    .forgot{
      text-align:right;
      margin-bottom:20px;
      font-size:12px;
    }

    .forgot a{
      color:#3b502c;
      text-decoration:none;
    }

    .btn-login{
      width:100%;
      background:#A41B42;
      color:#fff;
      border:none;
      padding:12px;
      border-radius:8px;
      font-size:14px;
      font-weight:500;
      cursor:pointer;
      transition:0.2s;
    }

    .btn-login:hover{
      background:#3b502c;
    }

    .google-btn{
      margin-top:15px;
      border:1px solid #ddd;
      background:#fff;
      padding:10px;
      width:100%;
      border-radius:8px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
      cursor:pointer;
      font-size:14px;
    }

    .signup-link{
      text-align:center;
      margin-top:20px;
      font-size:13px;
    }

    .signup-link a{
      color:#3b502c;
      font-weight:500;
      text-decoration:none;
    }
    .password-wrapper{
  position: relative;
}

.password-wrapper input{
  padding-right: 40px;
}

.toggle-password{
  position:absolute;
  right:12px;
  top:50%;
  transform:translateY(-50%);
  cursor:pointer;
  color:#777;
  font-size:16px;
}
.form-group input {
  box-sizing: border-box;   /* important */
}

.password-wrapper{
  position: relative;
  width: 100%;              /* ✅ this fixes alignment */
}
.form-group input{
  width:100%;
  padding:10px 12px;
  border:1px solid #ddd;
  border-radius:6px;
  font-size:14px;
  outline:none;
  box-sizing: border-box;   /* ✅ added */
}

.password-wrapper{
  position: relative;
  width: 100%;              /* ✅ added */
}

.password-wrapper input{
  padding-right: 40px;
}


  </style>
</head>
<body>

<div class="login-wrapper">
  <h1>Welcome back</h1>
  <p>Please enter your details.</p>

  <form action="process_login.php" method="post">
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" placeholder="Enter your email" required>
    </div>

   <div class="form-group">
  <label>Password</label>
  <div class="password-wrapper">
    <input type="password" name="password" id="password" placeholder="Password" required>
    <span class="toggle-password" id="togglePassword">
      👁
    </span>
  </div>
</div>

    <div class="forgot">
      <a href="forgot-password.php">Forgot password?</a>
    </div>

    <button class="btn-login" type="submit">Login</button>
  </form>

  <a href="google-login.php" style="text-decoration:none;color:inherit;">
    <div class="google-btn">
      <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png" width="18" alt="Google">
      Sign in with Google
    </div>
  </a>

  <div class="signup-link">
    Don’t have an account? <a href="register.php">Sign up for free</a>
  </div>
</div>

</body>
<script>
  const togglePassword = document.getElementById("togglePassword");
  const passwordField = document.getElementById("password");

  togglePassword.addEventListener("click", function () {
    const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
    passwordField.setAttribute("type", type);

    // Change icon style
    this.textContent = type === "password" ? "👁" : "🙈";
  });
</script>


</html>
