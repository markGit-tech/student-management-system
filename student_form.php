<?php
/** Student form: CREATE (no id) and UPDATE (?id=). Admin only. */
require_once __DIR__ . '/includes/functions.php';
require_admin();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$courses = courses_all();
$errors = [];

$data = ['student_no' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'birthdate' => '',
         'gender' => '', 'address' => '', 'course_id' => '', 'year_level' => 1, 'status' => 'active'];
$existing = null;
if ($id) {
    $st = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $st->execute([$id]);
    $existing = $st->fetch();
    if (!$existing) { flash('Student not found.', 'error'); redirect('students.php'); }
    $data = array_merge($data, $existing);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['student_no', 'first_name', 'last_name', 'email', 'phone', 'birthdate', 'gender', 'address', 'course_id', 'year_level', 'status'] as $k) $data[$k] = clean($k);
    $data['email'] = strtolower($data['email']);
    $password = $_POST['password'] ?? '';

    // ---- Server-side validation ----
    if ($data['student_no'] === '') $data['student_no'] = $existing['student_no'] ?? generate_student_no();
    if (!preg_match('/^[A-Za-z0-9\-]{3,20}$/', $data['student_no'])) $errors['student_no'] = 'Use 3-20 letters, numbers or dashes.';
    if ($data['first_name'] === '' || mb_strlen($data['first_name']) > 60) $errors['first_name'] = 'First name is required (max 60).';
    if ($data['last_name'] === ''  || mb_strlen($data['last_name'])  > 60) $errors['last_name']  = 'Last name is required (max 60).';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    if ($data['phone'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $data['phone'])) $errors['phone'] = 'Invalid phone number.';
    if ($data['birthdate'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $data['birthdate']);
        if (!$d || $d->format('Y-m-d') !== $data['birthdate'] || $d > new DateTime()) $errors['birthdate'] = 'Invalid birthdate.';
    }
    if ($data['gender'] !== '' && !in_array($data['gender'], ['Male', 'Female', 'Other'], true)) $errors['gender'] = 'Invalid gender.';
    if (!in_array((int)$data['course_id'], array_column($courses, 'id'))) $errors['course_id'] = 'Select a course.';
    if (!in_array((int)$data['year_level'], range(1, 6))) $errors['year_level'] = 'Year level must be 1-6.';
    if (!in_array($data['status'], ['active', 'inactive', 'graduated'], true)) $errors['status'] = 'Invalid status.';
    if (!$id && $password !== '' && ($m = strong_password($password))) $errors['password'] = $m;

    // ---- Uniqueness ----
    if (!$errors) {
        $u = $pdo->prepare('SELECT id FROM students WHERE (email = ? OR student_no = ?) AND id <> ?');
        $u->execute([$data['email'], $data['student_no'], $id]);
        if ($u->fetch()) $errors['email'] = 'Email or student number already exists.';
        elseif (!$id && $password !== '') {
            $x = $pdo->prepare('SELECT 1 FROM users WHERE email = ?'); $x->execute([$data['email']]);
            if ($x->fetch()) $errors['email'] = 'A user account with this email already exists.';
        }
    }

    if (!$errors) {
        $vals = [$data['student_no'], $data['first_name'], $data['last_name'], $data['email'], $data['phone'] ?: null,
                 $data['birthdate'] ?: null, $data['gender'] ?: null, $data['address'] ?: null,
                 (int)$data['course_id'], (int)$data['year_level'], $data['status']];
        $pdo->beginTransaction();
        try {
            if ($id) {
                $pdo->prepare('UPDATE students SET student_no=?, first_name=?, last_name=?, email=?, phone=?, birthdate=?, gender=?, address=?, course_id=?, year_level=?, status=? WHERE id=?')
                    ->execute([...$vals, $id]);
                if ($existing['user_id']) {   // keep login account in sync
                    $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?')
                        ->execute([$data['first_name'] . ' ' . $data['last_name'], $data['email'], $existing['user_id']]);
                }
                flash('Student updated.');
            } else {
                $uid = null;
                if ($password !== '') {
                    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,'student')")
                        ->execute([$data['first_name'] . ' ' . $data['last_name'], $data['email'], password_hash($password, PASSWORD_DEFAULT)]);
                    $uid = (int)$pdo->lastInsertId();
                }
                $pdo->prepare('INSERT INTO students (student_no, first_name, last_name, email, phone, birthdate, gender, address, course_id, year_level, status, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([...$vals, $uid]);
                flash('Student added.');
            }
            $pdo->commit();
            redirect('students.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['email'] = 'Database error. Please try again.';
        }
    }
}

$pageTitle = $id ? 'Edit Student' : 'Add Student';
include __DIR__ . '/includes/header.php';
$I = INPUT_CLASS;
?>
<a href="students.php" class="text-sm text-indigo-600 hover:underline">← Back to students</a>
<h1 class="text-2xl font-bold my-3"><?= e($pageTitle) ?></h1>
<form method="post" data-validate novalidate class="bg-white rounded-xl shadow-sm p-5 sm:p-6 max-w-4xl grid md:grid-cols-2 gap-4">
  <?= csrf_field() ?>
  <div><label class="block text-sm font-medium mb-1">Student No. <span class="text-slate-400 font-normal">(blank = auto)</span></label>
    <input name="student_no" value="<?= e($data['student_no']) ?>" class="<?= $I ?>"><?= field_err($errors, 'student_no') ?></div>
  <div><label class="block text-sm font-medium mb-1">Email *</label>
    <input name="email" type="email" required value="<?= e($data['email']) ?>" class="<?= $I ?>"><?= field_err($errors, 'email') ?></div>
  <div><label class="block text-sm font-medium mb-1">First name *</label>
    <input name="first_name" required maxlength="60" value="<?= e($data['first_name']) ?>" class="<?= $I ?>"><?= field_err($errors, 'first_name') ?></div>
  <div><label class="block text-sm font-medium mb-1">Last name *</label>
    <input name="last_name" required maxlength="60" value="<?= e($data['last_name']) ?>" class="<?= $I ?>"><?= field_err($errors, 'last_name') ?></div>
  <div><label class="block text-sm font-medium mb-1">Phone</label>
    <input name="phone" value="<?= e($data['phone']) ?>" class="<?= $I ?>"><?= field_err($errors, 'phone') ?></div>
  <div><label class="block text-sm font-medium mb-1">Birthdate</label>
    <input name="birthdate" type="date" max="<?= date('Y-m-d') ?>" value="<?= e($data['birthdate']) ?>" class="<?= $I ?>"><?= field_err($errors, 'birthdate') ?></div>
  <div><label class="block text-sm font-medium mb-1">Gender</label>
    <select name="gender" class="<?= $I ?>"><option value="">—</option>
      <?php foreach (['Male', 'Female', 'Other'] as $g): ?><option <?= $data['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?></select></div>
  <div><label class="block text-sm font-medium mb-1">Course *</label>
    <select name="course_id" required class="<?= $I ?>"><option value="">-- Select --</option>
      <?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$data['course_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['code'] . ' – ' . $c['name']) ?></option><?php endforeach; ?></select>
    <?= field_err($errors, 'course_id') ?></div>
  <div><label class="block text-sm font-medium mb-1">Year level *</label>
    <select name="year_level" class="<?= $I ?>"><?php for ($y = 1; $y <= 6; $y++): ?><option value="<?= $y ?>" <?= (int)$data['year_level'] === $y ? 'selected' : '' ?>>Year <?= $y ?></option><?php endfor; ?></select></div>
  <div><label class="block text-sm font-medium mb-1">Status *</label>
    <select name="status" class="<?= $I ?>"><?php foreach (['active', 'inactive', 'graduated'] as $s): ?><option value="<?= $s ?>" <?= $data['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Address</label>
    <input name="address" maxlength="255" value="<?= e($data['address']) ?>" class="<?= $I ?>"></div>
  <?php if (!$id): ?>
  <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Login password <span class="text-slate-400 font-normal">(optional – creates a student login using the email above)</span></label>
    <input id="password" name="password" type="password" autocomplete="new-password" class="<?= $I ?>"><?= field_err($errors, 'password') ?></div>
  <?php endif; ?>
  <div class="md:col-span-2 flex gap-3 pt-2">
    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold"><?= $id ? 'Save changes' : 'Add student' ?></button>
    <a href="students.php" class="px-6 py-2.5 rounded-lg bg-slate-200 hover:bg-slate-300">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
