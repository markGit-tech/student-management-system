<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pdo = db();
$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';

if (is_admin()):
    $stats = [
        'Total Students' => (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
        'Active'         => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn(),
        'Graduated'      => (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='graduated'")->fetchColumn(),
        'Courses'        => (int)$pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    ];
    $colors = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-sky-500'];
    $perCourse = $pdo->query('SELECT c.code, c.name, COUNT(s.id) AS total FROM courses c LEFT JOIN students s ON s.course_id=c.id GROUP BY c.id ORDER BY total DESC')->fetchAll();
    $counts = array_map('intval', array_column($perCourse, 'total'));
    $max = max(1, ...($counts ?: [1]));
    $recent = $pdo->query('SELECT s.*, c.code FROM students s JOIN courses c ON c.id=s.course_id ORDER BY s.id DESC LIMIT 5')->fetchAll();
?>
<h1 class="text-2xl font-bold mb-1">Admin Dashboard</h1>
<p class="text-slate-500 mb-6">Overview of your school records.</p>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
  <?php $i = 0; foreach ($stats as $label => $val): ?>
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 <?= str_replace('bg-', 'border-', $colors[$i % 4]) ?>">
      <div class="text-slate-500 text-sm"><?= e($label) ?></div>
      <div class="text-3xl font-bold mt-1"><?= $val ?></div>
    </div>
  <?php $i++; endforeach; ?>
</div>

<div class="grid lg:grid-cols-2 gap-6">
  <section class="bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-semibold mb-4">Students per Course</h2>
    <?php foreach ($perCourse as $r): ?>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1"><span title="<?= e($r['name']) ?>"><?= e($r['code']) ?></span><span class="font-medium"><?= (int)$r['total'] ?></span></div>
        <div class="h-2.5 bg-slate-100 rounded-full"><div class="h-2.5 bg-indigo-500 rounded-full" style="width: <?= round($r['total'] / $max * 100) ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </section>
  <section class="bg-white rounded-xl shadow-sm p-5">
    <div class="flex justify-between items-center mb-4"><h2 class="font-semibold">Recently Added</h2><a href="students.php" class="text-sm text-indigo-600 hover:underline">View all</a></div>
    <ul class="divide-y divide-slate-100">
      <?php foreach ($recent as $s): ?>
        <li class="py-3 flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold"><?= e(strtoupper($s['first_name'][0] . $s['last_name'][0])) ?></div>
          <div class="min-w-0 flex-1"><div class="font-medium truncate"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></div><div class="text-xs text-slate-500"><?= e($s['student_no']) ?> · <?= e($s['code']) ?></div></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<?php else:
    $st = $pdo->prepare('SELECT s.*, c.code, c.name AS course_name FROM students s JOIN courses c ON c.id=s.course_id WHERE s.user_id = ?');
    $st->execute([user()['id']]);
    $me = $st->fetch();
?>
<h1 class="text-2xl font-bold mb-1">Hello, <?= e(user()['name']) ?> 👋</h1>
<p class="text-slate-500 mb-6">Here is your student information.</p>
<?php if (!$me): ?>
  <div class="bg-amber-100 text-amber-800 rounded-lg p-4">No student record is linked to your account yet. Please contact the administrator.</div>
<?php else: ?>
  <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <?php foreach (['Student No.' => $me['student_no'], 'Course' => $me['code'], 'Year Level' => 'Year ' . $me['year_level'], 'Status' => ucfirst($me['status'])] as $l => $v): ?>
      <div class="bg-white rounded-xl shadow-sm p-5"><div class="text-slate-500 text-sm"><?= e($l) ?></div><div class="text-xl font-bold mt-1"><?= e($v) ?></div></div>
    <?php endforeach; ?>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-semibold mb-3">Details</h2>
    <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
      <div><dt class="text-slate-500">Full name</dt><dd><?= e($me['first_name'] . ' ' . $me['last_name']) ?></dd></div>
      <div><dt class="text-slate-500">Program</dt><dd><?= e($me['course_name']) ?></dd></div>
      <div><dt class="text-slate-500">Email</dt><dd><?= e($me['email']) ?></dd></div>
      <div><dt class="text-slate-500">Phone</dt><dd><?= e($me['phone'] ?: '—') ?></dd></div>
      <div><dt class="text-slate-500">Birthdate</dt><dd><?= e($me['birthdate'] ?: '—') ?></dd></div>
      <div><dt class="text-slate-500">Address</dt><dd><?= e($me['address'] ?: '—') ?></dd></div>
    </dl>
    <a href="profile.php" class="inline-block mt-4 text-indigo-600 text-sm font-medium hover:underline">Edit my contact info →</a>
  </div>
<?php endif; endif;
include __DIR__ . '/includes/footer.php'; ?>
