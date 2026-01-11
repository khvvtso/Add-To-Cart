<?php
// php/login.php
session_start();
require_once __DIR__ . '/db_project.php';

$errors = [];

function sanitize($value) {
    return htmlspecialchars(trim($value));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($password === '') $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = $pdo_project->prepare('SELECT user_id, `Name`, `Email`, `password` FROM users WHERE Email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Authentication success - set session with user_id for database cart tracking
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_name'] = $user['Name'];
            // Redirect to customer dashboard
            header('Location: /management/php/customer/index.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}




?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <style>
        /* Reset & base */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Login card */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 16px;
            padding: 30px;
            color: #fff;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .login-card h1 {
            text-align: center;
            margin-bottom: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Errors */
        .errors {
            background: rgba(255, 99, 99, 0.15);
            border: 1px solid rgba(255, 99, 99, 0.4);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .errors ul {
            margin: 0;
            padding-left: 18px;
        }

        /* Form */
        label {
            font-size: 14px;
            margin-top: 14px;
            display: block;
            color: #eaeaea;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            margin-top: 6px;
            border-radius: 10px;
            border: none;
            outline: none;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.9);
            transition: box-shadow 0.2s ease, transform 0.1s ease;
        }

        input:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.5);
            transform: translateY(-1px);
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #667eea, #764ba2);
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        /* Links */
        .links {
            margin-top: 18px;
            text-align: center;
            font-size: 14px;
        }

        .links a {
            color: #fff;
            text-decoration: none;
            opacity: 0.9;
        }

        .links a:hover {
            text-decoration: underline;
            opacity: 1;
        }

        /* Small screens */
        @media (max-width: 400px) {
            .login-card {
                padding: 22px;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <h1>Welcome Back</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            required
            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Login</button>
    </form>

    <div class="links">
        <p><a href="forgot_password.php">Forgot password?</a></p>
        <p>Don’t have an account? <a href="register.php">Create one</a></p>
    </div>
</div>

</body>
</html>