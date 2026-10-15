<?php
require_once __DIR__ . '/includes/functions.php';
if (user()) redirect('dashboard.php');

$errors = [];
$courses = courses_all();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $first = clean('first_name'); $last = clean('last_name');
    $email = strtolower(clean('email'));
    $course = (int)clean('course_id');
    $pass = $_POST['password'] ?? ''; $conf = $_POST['confirm'] ?? '';

    if ($first === '' || mb_strlen($first) > 60) $errors['first_name'] = 'First name is required (max 60 chars).';
    if ($last === ''  || mb_strlen($last) > 60)  $errors['last_name']  = 'Last name is required (max 60 chars).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (!in_array($course, array_column($courses, 'id'))) $errors['course_id'] = 'Select a course.';
    if ($msg = strong_password($pass)) $errors['password'] = $msg;
    if ($pass !== $conf) $errors['confirm'] = 'Passwords do not match.';

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ? UNION SELECT 1 FROM students WHERE email = ?');
        $st->execute([$email, $email]);
        if ($st->fetch()) $errors['email'] = 'This email is already registered.';
    }
    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)')
                ->execute(["$first $last", $email, password_hash($pass, PASSWORD_DEFAULT), 'student']);
            $uid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO students (user_id, student_no, first_name, last_name, email, course_id, year_level, status) VALUES (?,?,?,?,?,?,1,"active")')
                ->execute([$uid, generate_student_no(), $first, $last, $email, $course]);
            $pdo->commit();
            flash('Registration successful! You can now log in.');
            redirect('login.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['email'] = 'Could not register. Please try again.';
        }
    }
}


$inp = INPUT_CLASS;
function f(string $k): string { return e($_POST[$k] ?? ''); }
$pageTitle = 'Register'; $authPage = true;
include __DIR__ . '/includes/header.php';
?>
<div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl p-6 sm:p-8 my-6">
  <h1 class="text-2xl font-bold text-center mb-1">Create Student Account</h1>
  <p class="text-slate-500 text-sm text-center mb-6">Fill in your details to get started</p>
  <form method="post" data-validate novalidate class="space-y-4">
    <?= csrf_field() ?>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="block text-sm font-medium mb-1">First name</label>
        <input name="first_name" required maxlength="60" value="<?= f('first_name') ?>" class="<?= $inp ?>"><?= field_err($errors, 'first_name') ?></div>
      <div><label class="block text-sm font-medium mb-1">Last name</label>
        <input name="last_name" required maxlength="60" value="<?= f('last_name') ?>" class="<?= $inp ?>"><?= field_err($errors, 'last_name') ?></div>
    </div>
    <div><label class="block text-sm font-medium mb-1">Email</label>
      <input id="regEmail" name="email" type="email" required value="<?= f('email') ?>" class="<?= $inp ?>">
      <p id="emailHint" class="text-xs mt-1"></p><?= field_err($errors, 'email') ?></div>
    <div><label class="block text-sm font-medium mb-1">Course</label>
      <select name="course_id" required class="<?= $inp ?>">
        <option value="">-- Select course --</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (int)($_POST['course_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['code'] . ' – ' . $c['name']) ?></option>
        <?php endforeach; ?>
      </select><?= field_err($errors, 'course_id') ?></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="block text-sm font-medium mb-1">Password</label>
        <input id="password" name="password" type="password" required minlength="8" class="<?= $inp ?>">
        <div class="h-1.5 bg-slate-200 rounded mt-2"><div id="strengthBar" class="h-1.5 rounded w-0 transition-all"></div></div>
        <?= field_err($errors, 'password') ?></div>
      <div><label class="block text-sm font-medium mb-1">Confirm password</label>
        <input id="confirm" name="confirm" type="password" required class="<?= $inp ?>"><?= field_err($errors, 'confirm') ?></div>
    </div>
    <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg py-2.5 transition">Register</button>
  </form>
  <p class="text-center text-sm text-slate-500 mt-5">Already registered? <a class="text-indigo-600 font-medium hover:underline" href="login.php">Log in</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
