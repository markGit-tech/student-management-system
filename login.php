<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(clean('email'));
    $pass  = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
        $error = 'Please enter a valid email and password.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $row = $st->fetch();
        if ($row && password_verify($pass, $row['password'])) {
            login_user($row);
            flash('Welcome back, ' . $row['name'] . '!');
            redirect('dashboard.php');
        }
        $error = 'Invalid email or password.';   // generic message on purpose
    }
}
$pageTitle = 'Login'; $authPage = true;
include __DIR__ . '/includes/header.php';
?>
<div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
  <div class="text-center mb-6">
    <div class="text-5xl mb-2">🎓</div>
    <h1 class="text-2xl font-bold"><?= e(APP_NAME) ?></h1>
    <p class="text-slate-500 text-sm">Sign in to your account</p>
  </div>
  <?php if ($error): ?><div class="mb-4 rounded-lg bg-red-100 text-red-800 px-4 py-3 text-sm"><?= e($error) ?></div><?php endif; ?>
  <form method="post" data-validate novalidate class="space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium mb-1" for="email">Email</label>
      <input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>"
             class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
    </div>
    <div>
      <label class="block text-sm font-medium mb-1" for="password">Password</label>
      <input id="password" name="password" type="password" required
             class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
    </div>
    <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg py-2.5 transition">Log in</button>
  </form>
  <p class="text-center text-sm text-slate-500 mt-5">No account yet? <a class="text-indigo-600 font-medium hover:underline" href="register.php">Register as student</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
