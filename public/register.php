<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../app/middleware/Auth.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

if (Auth::isAuthenticated()) {
    header('Location: ./dashboard');
    exit;
}

$registerError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $department = $_POST['department'] ?? '';
    $student_id = $_POST['student_id'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $registerError = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $registerError = 'Passwords do not match.';
    } else {
        $user = new User();
        $result = $user->register([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'department' => $department,
            'student_id' => $student_id
        ]);

        if ($result['success']) {
            header('Location: ./login?registered=1');
            exit;
        } else {
            $registerError = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GradeHub</title>
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
            max-width: 560px;
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
            <a href="./login">Login</a>
            <a href="#" class="active">Sign Up</a>
        </div>

        <div class="auth-card p-6 sm:p-7">
            <h2 class="text-xl font-bold text-slate-900 mb-1">Create account</h2>
            <p class="text-sm text-slate-500 mb-6">It only takes a minute</p>

                <?php if ($registerError): ?>
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm flex gap-3">
                    <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                    <span><?php echo htmlspecialchars($registerError); ?></span>
                </div>
                <?php endif; ?>

            <form method="POST" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                            <div class="field px-3 py-3">
                                <i class="fas fa-user text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                                <input type="text" name="full_name" required class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="Your name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                            <div class="field px-3 py-3">
                                <i class="fas fa-envelope text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                                <input type="email" name="email" required class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="you@university.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Role</label>
                            <div class="field px-3 py-3">
                                <i class="fas fa-id-badge text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                                <select name="role" required class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" id="role-select" style="padding-left: 28px;">
                                    <option value="">Select your role</option>
                                    <option value="student">Student</option>
                                    <option value="faculty">Faculty</option>
                                </select>
                            </div>
                        </div>
                        <div id="student-field" class="hidden">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Student ID</label>
                            <div class="field px-3 py-3">
                                <i class="fas fa-hash text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                                <input type="text" name="student_id" class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="STU-2024-00123" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                            </div>
                        </div>
                        <div id="faculty-field" class="hidden">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Department</label>
                            <div class="field px-3 py-3">
                                <i class="fas fa-building text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                                <input type="text" name="department" class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="Computer Science" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                            <div class="field px-3 py-3">
                                <i class="fas fa-lock text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                                <input type="password" name="password" required class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="********">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                            <div class="field px-3 py-3">
                                <i class="fas fa-check text-slate-400 text-sm" style="position:absolute; left:14px; top:50%; transform:translateY(-50%);"></i>
                                <input type="password" name="confirm_password" required class="w-full border-0 focus:ring-0 focus:outline-none bg-transparent text-sm" style="padding-left: 28px;" placeholder="********">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn w-full text-white font-semibold py-3 rounded-xl transition duration-200">
                        Create Account
                    </button>
                </form>

            <div class="text-center mt-5 text-sm text-slate-600 sm:hidden">
                <p>Already have an account? <a href="./login" class="text-slate-900 font-semibold hover:underline">Sign in</a></p>
            </div>
        </div>
    </div>

    <script>
        const roleSelect = document.getElementById('role-select');
        const studentField = document.getElementById('student-field');
        const facultyField = document.getElementById('faculty-field');

        function updateRoleFields() {
            const value = roleSelect.value;
            if (value === 'student') {
                studentField.classList.remove('hidden');
                facultyField.classList.add('hidden');
            } else if (value === 'faculty') {
                facultyField.classList.remove('hidden');
                studentField.classList.add('hidden');
            } else {
                studentField.classList.add('hidden');
                facultyField.classList.add('hidden');
            }
        }

        roleSelect.addEventListener('change', updateRoleFields);
        updateRoleFields();
    </script>
</body>
</html>
