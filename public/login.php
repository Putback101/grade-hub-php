<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';

if (Auth::isAuthenticated()) {
    header('Location: ./dashboard');
    exit;
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $loginError = 'Email and password are required';
    } else {
        $result = Auth::login($email, $password);
        if ($result['success']) {
            header('Location: ./dashboard');
            exit;
        } else {
            $loginError = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GradeHub</title>
    <link rel="stylesheet" href="./assets/css/tailwind.css">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --bg: #f7f9fc;
            --card: #ffffff;
            --border: #e2e8f0;
            --primary: #1f2a4a;
            --accent: #2fb59a;
        }
        body {
            font-family: "Inter", system-ui, -apple-system, Segoe UI, sans-serif;
            background: var(--bg);
            min-height: 100vh;
        }
        .auth-shell {
            max-width: 520px;
            margin: 0 auto;
        }
        .brand-badge {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2fb59a 0%, #1fa07f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 10px 24px rgba(47, 181, 154, 0.25);
        }
        .tab-pill {
            background: #f3f6fb;
            border-radius: 12px;
            padding: 6px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        .tab-pill a {
            text-align: center;
            padding: 8px 0;
            border-radius: 10px;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
        }
        .tab-pill a.active {
            background: #fff;
            color: var(--ink);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        }
        .auth-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }
        .field {
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            transition: box-shadow 0.15s ease, border-color 0.15s ease;
            position: relative;
        }
        .field:focus-within {
            border-color: #1f2a4a;
            box-shadow: 0 0 0 2px rgba(31, 42, 74, 0.2);
        }
        .submit-btn {
            background: #1f2a4a;
        }
    </style>
</head>
<body class="flex items-center justify-center p-6">
    <div class="auth-shell w-full">
        <div class="flex items-center justify-center gap-4 mb-6">
            <div class="brand-badge">
                <i class="fas fa-graduation-cap text-xl"></i>
            </div>
            <div>
                <div class="text-lg font-bold text-slate-900">GradeHub</div>
                <div class="text-sm text-slate-500">Assessment System</div>
            </div>
        </div>

        <div class="tab-pill mb-5">
            <a href="#" class="active">Login</a>
            <a href="./register">Sign Up</a>
        </div>

        <div class="auth-card p-6 sm:p-7">
            <h2 class="text-xl font-bold text-slate-900 mb-1">Welcome back</h2>
            <p class="text-sm text-slate-500 mb-6">Enter your credentials to access your account</p>

                <?php if ($loginError): ?>
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm flex gap-3">
                    <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                    <span><?php echo htmlspecialchars($loginError); ?></span>
                </div>
                <?php endif; ?>

            <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <div class="field px-3 py-3">
                            <i class="fas fa-envelope text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                            <input type="email" name="email" required class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="you@university.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                        <div class="field px-3 py-3">
                            <i class="fas fa-lock text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                            <input type="password" name="password" required class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="********">
                        </div>
                    </div>

                    <button type="submit" class="submit-btn w-full text-white font-semibold py-3 rounded-xl transition duration-200">
                        Sign In
                    </button>
                </form>

            <div class="text-center mt-5 text-sm text-slate-600 sm:hidden">
                <p>Don't have an account? <a href="./register" class="text-slate-900 font-semibold hover:underline">Sign up</a></p>
            </div>
        </div>
    </div>
</body>
</html>
