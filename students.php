<?php
/** Students: list (READ) with search, filter, pagination + DELETE. Admin only. */
require_once __DIR__ . '/includes/functions.php';
require_admin();
$pdo = db();

// ---- DELETE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT user_id FROM students WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row) {
        $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
        if ($row['user_id']) $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'")->execute([$row['user_id']]);
        flash('Student deleted.');
    }
    $pdo->commit();
    redirect('students.php');
}

// ---- READ: search + filter + pagination ----
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$courseF = (int)($_GET['course'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;

$where = []; $params = [];
if ($q !== '') {
    $where[] = '(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_no LIKE ? OR s.email LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if (in_array($status, ['active', 'inactive', 'graduated'], true)) { $where[] = 's.status = ?'; $params[] = $status; }
if ($courseF) { $where[] = 's.course_id = ?'; $params[] = $courseF; }
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM students s $sqlWhere");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$st = $pdo->prepare("SELECT s.*, c.code FROM students s JOIN courses c ON c.id = s.course_id $sqlWhere ORDER BY s.id DESC LIMIT $perPage OFFSET $offset");
$st->execute($params);
$students = $st->fetchAll();
$courses = courses_all();

$qs = fn(array $o = []) => http_build_query(array_merge(['q' => $q, 'status' => $status, 'course' => $courseF ?: '', 'page' => $page], $o));
$badge = ['active' => 'bg-emerald-100 text-emerald-700', 'inactive' => 'bg-slate-200 text-slate-600', 'graduated' => 'bg-sky-100 text-sky-700'];

$pageTitle = 'Students';
include __DIR__ . '/includes/header.php';
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
  <div><h1 class="text-2xl font-bold">Students</h1><p class="text-slate-500 text-sm"><?= $total ?> record<?= $total === 1 ? '' : 's' ?> found</p></div>
  <a href="student_form.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">+ Add Student</a>
</div>

<form method="get" class="bg-white rounded-xl shadow-sm p-4 mb-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
  <input name="q" value="<?= e($q) ?>" placeholder="Search name, ID, email…" class="<?= INPUT_CLASS ?> lg:col-span-2">
  <select name="course" class="<?= INPUT_CLASS ?>"><option value="">All courses</option>
    <?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>" <?= $courseF === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['code']) ?></option><?php endforeach; ?></select>
  <div class="flex gap-2">
    <select name="status" class="<?= INPUT_CLASS ?>"><option value="">Any status</option>
      <?php foreach (['active', 'inactive', 'graduated'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select>
    <button class="bg-slate-800 text-white px-4 rounded-lg text-sm">Filter</button>
  </div>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-x-auto">
  <table class="w-full text-sm min-w-[720px]">
    <thead class="bg-slate-50 text-slate-500 text-left">
      <tr><th class="px-4 py-3">Student No.</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Course</th><th class="px-4 py-3">Year</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
    <?php if (!$students): ?><tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No students found.</td></tr><?php endif; ?>
    <?php foreach ($students as $s): ?>
      <tr class="hover:bg-slate-50">
        <td class="px-4 py-3 font-mono text-xs"><?= e($s['student_no']) ?></td>
        <td class="px-4 py-3"><div class="font-medium"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></div><div class="text-xs text-slate-500"><?= e($s['email']) ?></div></td>
        <td class="px-4 py-3"><?= e($s['code']) ?></td>
        <td class="px-4 py-3"><?= (int)$s['year_level'] ?></td>
        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $badge[$s['status']] ?>"><?= e(ucfirst($s['status'])) ?></span></td>
        <td class="px-4 py-3">
          <div class="flex justify-end gap-2">
            <a href="student_form.php?id=<?= $s['id'] ?>" class="px-3 py-1 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100">Edit</a>
            <form method="post" data-confirm="Delete <?= e($s['first_name']) ?>? This cannot be undone.">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button class="px-3 py-1 rounded-md bg-red-50 text-red-700 hover:bg-red-100">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($pages > 1): ?>
<nav class="flex flex-wrap gap-1 mt-5 justify-center" aria-label="Pagination">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?<?= e($qs(['page' => $p])) ?>" class="px-3 py-1.5 rounded-md text-sm <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-white hover:bg-slate-200' ?>"><?= $p ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
