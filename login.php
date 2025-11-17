<?php
session_start();
require 'config.php';

if (!empty($_SESSION['user_id'])) {
  header('Location: analytics.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill both fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, name, role FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['role'] = $row['role'];
                    header('Location: analytics.php');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
            $stmt->close();
        } else {
            $error = 'Database error.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    body { font-family: Arial, sans-serif; background:#f7f7f7; }
    .login { width:320px; margin:80px auto; background:#fff; padding:20px; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
    .login h2{margin:0 0 12px}
    .login input{width:100%;padding:10px;margin:6px 0;border:1px solid #ccc;border-radius:4px}
    .login button{width:100%;padding:10px;background:#2d89ef;color:#fff;border:0;border-radius:4px;cursor:pointer}
    .error{color:#b00020;margin:8px 0}
  </style>
</head>
<body>
  <div class="login">
    <h2>Sign in</h2>
    <?php if ($error): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" required autofocus>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>
