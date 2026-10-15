<?php
require_once __DIR__ . '/functions.php';
$u = user();
$currentPage = basename($_SERVER['PHP_SELF']);
$title = ($pageTitle ?? 'Page') . ' | ' . APP_NAME;
$flash = pull_flash();

function nav_item(string $href, string $label, string $svg, string $page): string {
    $active = $page === $href ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
    return '<a href="' . $href . '" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition ' . $active . '">'
         . '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">' . $svg . '</svg>' . $label . '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
<?php if (!empty($authPage)): ?>
<main class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-indigo-700 via-indigo-600 to-sky-500">
<?php else: ?>
<div class="lg:flex min-h-screen">
  <header class="lg:hidden flex items-center justify-between bg-slate-900 text-white px-4 py-3 sticky top-0 z-30">
    <span class="font-bold">🎓 <?= e(APP_NAME) ?></span>
    <button id="navToggle" aria-label="Menu" class="p-2 rounded hover:bg-slate-800">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
  </header>
  <aside id="sidebar" class="hidden lg:flex flex-col w-full lg:w-64 bg-slate-900 lg:min-h-screen lg:sticky lg:top-0 lg:h-screen p-4 z-20">
    <div class="hidden lg:block text-white text-xl font-bold px-2 py-3 mb-4">🎓 <?= e(APP_NAME) ?></div>
    <nav class="space-y-1 flex-1">
      <?= nav_item('dashboard.php', 'Dashboard', '<path d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/>', $currentPage) ?>
      <?php if (is_admin()): ?>
        <?= nav_item('students.php', 'Students', '<path d="M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zm-7 9v-2a4 4 0 014-4h6a4 4 0 014 4v2"/>', $currentPage) ?>
        <?= nav_item('courses.php', 'Courses', '<path d="M12 6v12M4 5.5C6 4 9 4 12 6c3-2 6-2 8-.5v13c-2-1.5-5-1.5-8 .5-3-2-6-2-8-.5z"/>', $currentPage) ?>
      <?php endif; ?>
      <?= nav_item('profile.php', 'My Profile', '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1"/>', $currentPage) ?>
    </nav>
    <div class="border-t border-slate-700 pt-4 mt-4 text-sm">
      <div class="text-white font-medium truncate"><?= e($u['name']) ?></div>
      <div class="text-slate-400 text-xs mb-3 truncate"><?= e(ucfirst($u['role'])) ?> · <?= e($u['email']) ?></div>
      <a href="logout.php" class="block text-center bg-slate-800 hover:bg-red-600 text-slate-200 hover:text-white rounded-lg py-2 transition">Log out</a>
    </div>
  </aside>
  <main class="flex-1 p-4 sm:p-6 lg:p-8 min-w-0">
<?php endif; ?>
<?php if ($flash): ?>
  <div class="mb-4 rounded-lg px-4 py-3 text-sm font-medium <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' ?>" role="alert">
    <?= e($flash['msg']) ?>
  </div>
<?php endif; ?>
