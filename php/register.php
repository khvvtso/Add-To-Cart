<?php
// php/register.php
session_start();
require_once __DIR__ . '/db_project.php';

$errors = [];

function sanitize($value) {
    return htmlspecialchars(trim($value));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($name === '') $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($password === '' || strlen($password) < 6) $errors[] = 'Password is required (min 6 characters).';

    if (empty($errors)) {
        // Check if email already exists
        $stmt = $pdo_project->prepare('SELECT Email FROM users WHERE Email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $existing = $stmt->fetch();
        if ($existing) {
            $errors[] = 'An account with that email already exists.';
        } else {
            // Insert new user with hashed password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo_project->prepare('INSERT INTO users (`Name`, `Email`, `password`) VALUES (:name, :email, :password)');
            $ok = $insert->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hash
            ]);

            if ($ok) {
                // Registration successful -> redirect to login
                header('Location: login.php');
                exit;
            } else {
                $errors[] = 'Registration failed — please try again.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Account</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <style>
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

        .register-card {
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 16px;
            padding: 32px;
            color: #fff;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .register-card h1 {
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
            margin-top: 24px;
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

        @media (max-width: 400px) {
            .register-card {
                padding: 22px;
            }
        }
    </style>
</head>
<body>

<div class="register-card">
    <h1>Create Account</h1>

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
        <label for="name">Full name</label>
        <input
            id="name"
            name="name"
            type="text"
            required
            value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">

        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            required
            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Create Account</button>
    </form>

    <div class="links">
        <p>Already have an account? <a href="login.php">Log in</a></p>
    </div>
</div>

</body>
</html>