<?php
/** Courses: full CRUD on one page (add/edit form + list). Admin only. */
require_once __DIR__ . '/includes/functions.php';
require_admin();
$pdo = db();
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$form = ['code' => '', 'name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        try {
            $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([(int)$_POST['id']]);
            flash('Course deleted.');
        } catch (PDOException $e) {
            flash('Cannot delete: students are still enrolled in this course.', 'error');
        }
        redirect('courses.php');
    }
    $editId = (int)($_POST['id'] ?? 0);
    $form = ['code' => strtoupper(clean('code')), 'name' => clean('name'), 'description' => clean('description')];
    if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $form['code'])) $errors['code'] = 'Code: 2-20 letters/numbers.';
    if ($form['name'] === '' || mb_strlen($form['name']) > 150) $errors['name'] = 'Course name is required.';
    if (mb_strlen($form['description']) > 255) $errors['description'] = 'Max 255 characters.';
    if (!$errors) {
        $d = $pdo->prepare('SELECT 1 FROM courses WHERE code = ? AND id <> ?');
        $d->execute([$form['code'], $editId]);
        if ($d->fetch()) $errors['code'] = 'Course code already exists.';
    }
    if (!$errors) {
        if ($editId) {
            $pdo->prepare('UPDATE courses SET code=?, name=?, description=? WHERE id=?')->execute([$form['code'], $form['name'], $form['description'] ?: null, $editId]);
            flash('Course updated.');
        } else {
            $pdo->prepare('INSERT INTO courses (code, name, description) VALUES (?,?,?)')->execute([$form['code'], $form['name'], $form['description'] ?: null]);
            flash('Course added.');
        }
        redirect('courses.php');
    }
} elseif ($editId) {
    $st = $pdo->prepare('SELECT code, name, description FROM courses WHERE id = ?');
    $st->execute([$editId]);
    $form = $st->fetch() ?: $form;
}

$list = $pdo->query('SELECT c.*, COUNT(s.id) AS total FROM courses c LEFT JOIN students s ON s.course_id = c.id GROUP BY c.id ORDER BY c.code')->fetchAll();
$pageTitle = 'Courses';
include __DIR__ . '/includes/header.php';
$I = INPUT_CLASS;
?>
<h1 class="text-2xl font-bold mb-5">Courses</h1>
<div class="grid lg:grid-cols-3 gap-6">
  <form method="post" data-validate novalidate class="bg-white rounded-xl shadow-sm p-5 space-y-3 h-fit">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $editId ?>">
    <h2 class="font-semibold"><?= $editId ? 'Edit course' : 'Add course' ?></h2>
    <div><label class="block text-sm font-medium mb-1">Code *</label><input name="code" required maxlength="20" value="<?= e($form['code']) ?>" class="<?= $I ?>"><?= field_err($errors, 'code') ?></div>
    <div><label class="block text-sm font-medium mb-1">Name *</label><input name="name" required maxlength="150" value="<?= e($form['name']) ?>" class="<?= $I ?>"><?= field_err($errors, 'name') ?></div>
    <div><label class="block text-sm font-medium mb-1">Description</label><textarea name="description" rows="3" maxlength="255" class="<?= $I ?>"><?= e($form['description']) ?></textarea><?= field_err($errors, 'description') ?></div>
    <div class="flex gap-2">
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-semibold"><?= $editId ? 'Update' : 'Add' ?></button>
      <?php if ($editId): ?><a href="courses.php" class="px-5 py-2 rounded-lg bg-slate-200">Cancel</a><?php endif; ?>
    </div>
  </form>

  <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm min-w-[520px]">
      <thead class="bg-slate-50 text-slate-500 text-left"><tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Course</th><th class="px-4 py-3">Students</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($list as $c): ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 font-semibold"><?= e($c['code']) ?></td>
          <td class="px-4 py-3"><div><?= e($c['name']) ?></div><div class="text-xs text-slate-500"><?= e($c['description']) ?></div></td>
          <td class="px-4 py-3"><?= (int)$c['total'] ?></td>
          <td class="px-4 py-3"><div class="flex justify-end gap-2">
            <a href="?edit=<?= $c['id'] ?>" class="px-3 py-1 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Edit</a>
            <form method="post" data-confirm="Delete course <?= e($c['code']) ?>?">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button class="px-3 py-1 rounded-md bg-red-50 text-red-700 hover:bg-red-100">Delete</button>
            </form></div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
