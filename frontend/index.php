<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap');

    body {
      font-family: 'Plus Jakarta Sans', Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: var(--bg);
      padding: 20px;
    }

    .login-container {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 40px 36px;
      width: 100%;
      max-width: 420px;
      animation: fadeUp 0.35s ease both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .login-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 28px;
    }

    .login-logo .logo-icon {
      width: 40px;
      height: 40px;
      background: var(--primary);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-logo .logo-icon svg {
      width: 22px;
      height: 22px;
      fill: #fff;
    }

    .login-logo span {
      font-size: 20px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -0.3px;
    }

    .login-container h2 {
      font-size: 22px;
      font-weight: 700;
      color: var(--text);
      margin-bottom: 4px;
      text-align: center;
    }

    .login-subtitle {
      font-size: 14px;
      color: var(--muted);
      margin-bottom: 28px;
      text-align: center;
    }

    .field-label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 6px;
    }

    .input-wrapper {
      position: relative;
      margin-bottom: 16px;
    }

    .input-wrapper svg {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      stroke: var(--muted);
      pointer-events: none;
    }

    .input-wrapper input {
      padding-left: 40px;
      margin-bottom: 0;
    }

    .login-container button[type="submit"] {
      width: 100%;
      padding: 13px;
      font-size: 15px;
      margin-top: 8px;
      border-radius: 10px;
      letter-spacing: 0.1px;
    }

    .error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: var(--danger);
      font-size: 13px;
      font-weight: 600;
      padding: 10px 14px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .error::before {
      content: '⚠';
      font-size: 14px;
    }

    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 13px;
      color: var(--muted);
    }

    @media (max-width: 480px) {
      .login-container { padding: 28px 20px; }
    }
  </style>
</head>
<body>

  <div class="login-container">
    <h2>Welcome back</h2>
    <p class="login-subtitle">Sign in to your account to continue.</p>

    <!-- Error message from failed login -->
    <?php if (isset($_GET['error'])): ?>
      <p class="error">Invalid username or password. Please try again.</p>
    <?php endif; ?>

    <form action="../backend/login.php" method="POST">

      <label class="field-label" for="username">Username</label>
      <div class="input-wrapper">
        <!-- user icon -->
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
        <input
          type="text"
          id="username"
          name="username"
          placeholder="Enter your username"
          autocomplete="username"
          required
        >
      </div>

      <label class="field-label" for="password">Password</label>
      <div class="input-wrapper">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
        </svg>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="Enter your password"
          autocomplete="current-password"
          required
        >
      </div>

      <button type="submit">Sign In</button>

    </form>

    <p class="login-footer">&copy; <?php echo date('Y'); ?> MyApp. All rights reserved.</p>

  </div>

</body>
</html>