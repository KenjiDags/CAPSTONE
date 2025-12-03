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
              //unhashed password for now
                if ($password === $row['password']) {
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
        body {
            font-family: Arial, sans-serif;
            background:#f7f7f7;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;

            background: url('images/TESDA-Logo.png') no-repeat center center fixed;
            background-size: contain;
        }

        .login {
            width: 520px;              /* MUCH larger */
            background:#ffffff;
            padding: 45px;             /* more padding */
            border-radius: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.18);
        }

        .login h2 {
            margin:0 0 20px;
            font-size: 26px;
            text-align: center;
        }

        .login input {
            width:100%;
            padding:12px;
            margin:10px 0;
            border:1px solid #ccc;
            border-radius:6px;
            font-size: 15px;
        }

        .login button {
            width:100%;
            padding:12px;
            background:#2d89ef;
            color:#fff;
            border:0;
            border-radius:6px;
            cursor:pointer;
            font-size: 16px;
        }

        .login button:hover {
            background:#1d6bc4;
        }

        .error {
            color:#b00020;
            margin:12px 0;
            text-align:center;
        }
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
