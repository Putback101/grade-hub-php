<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Mailer.php';
require_once __DIR__ . '/../../app/models/User.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Email is required.';
    } else {
        $user = new User();
        $result = $user->createPasswordResetToken($email);

        if (!$result['success']) {
            $error = 'Unable to process your request right now.';
        } else {
            if (!empty($result['token'])) {
                $resetLink = APP_URL . '/public/auth/reset-password?token=' . urlencode($result['token']);
                Mailer::sendPasswordResetEmail($result['email'], $result['name'], $resetLink);
            }
            $success = 'If that email exists, a password reset link has been sent.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">
    <style>
        body { font-family: "Inter", system-ui, -apple-system, Segoe UI, sans-serif; background: #f7f9fc; min-height: 100vh; }
        .shell { max-width: 520px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); }
        .field { border: 1.5px solid #cbd5e1; border-radius: 12px; background: #fff; position: relative; transition: box-shadow .15s ease, border-color .15s ease; }
        .field:focus-within { border-color: #1f2a4a; box-shadow: 0 0 0 2px rgba(31,42,74,.2); }
        .field > input { border: 0 !important; box-shadow: none !important; background: transparent; }
        .submit-btn { background: #1f2a4a; border: 0; appearance: none; -webkit-appearance: none; border-radius: .75rem; }
    </style>
</head>
<body class="flex items-center justify-center p-6">
    <div class="shell w-full">
        <div class="card p-6 sm:p-7">
            <h2 class="text-xl font-bold text-slate-900 mb-1">Forgot your password?</h2>
            <p class="text-sm text-slate-500 mb-6">Enter your email and we will send a reset link.</p>

            <?php if ($error): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <div class="field px-3 py-3">
                        <i class="fas fa-envelope text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                        <input type="email" name="email" required class="w-full focus:ring-0 focus:outline-none text-sm" style="padding-left: 28px;" placeholder="you@university.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="submit-btn w-full text-white font-semibold py-3 rounded-xl transition duration-200" style="margin-top: 12px;">Send Reset Link</button>
            </form>

            <p class="mt-5 text-sm text-center text-slate-600">
                <a href="../login" class="text-slate-900 font-semibold hover:underline">Back to login</a>
            </p>
        </div>
    </div>
</body>
</html>
