<?php
/** Profile: students edit their contact info; every user can change password. */
require_once __DIR__ . '/includes/functions.php';
require_login();
$pdo = db();
$uid = user()['id'];
$errors = [];

$st = $pdo->prepare('SELECT * FROM students WHERE user_id = ?');
$st->execute([$uid]);
$me = $st->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'contact' && $me) {
        $phone = clean('phone'); $address = clean('address'); $birth = clean('birthdate');
        if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) $errors['phone'] = 'Invalid phone number.';
        if ($birth !== '') { $d = DateTime::createFromFormat('Y-m-d', $birth); if (!$d || $d->format('Y-m-d') !== $birth || $d > new DateTime()) $errors['birthdate'] = 'Invalid date.'; }
        if (mb_strlen($address) > 255) $errors['address'] = 'Max 255 characters.';
        if (!$errors) {
            $pdo->prepare('UPDATE students SET phone=?, address=?, birthdate=? WHERE id=?')->execute([$phone ?: null, $address ?: null, $birth ?: null, $me['id']]);
            flash('Profile updated.'); redirect('profile.php');
        }
    } elseif (($_POST['action'] ?? '') === 'password') {
        $cur = $_POST['current'] ?? ''; $new = $_POST['password'] ?? ''; $conf = $_POST['confirm'] ?? '';
        $row = $pdo->prepare('SELECT password FROM users WHERE id = ?'); $row->execute([$uid]); $hash = $row->fetchColumn();
        if (!password_verify($cur, $hash)) $errors['current'] = 'Current password is incorrect.';
        if ($m = strong_password($new)) $errors['password'] = $m;
        if ($new !== $conf) $errors['confirm'] = 'Passwords do not match.';
        if (!$errors) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('Password changed.'); redirect('profile.php');
        }
    }
}
$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
$I = INPUT_CLASS;
?>
<h1 class="text-2xl font-bold mb-5">My Profile</h1>
<div class="grid lg:grid-cols-2 gap-6 max-w-5xl">
  <section class="bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-semibold mb-3">Account</h2>
    <p class="text-sm"><span class="text-slate-500">Name:</span> <?= e(user()['name']) ?></p>
    <p class="text-sm"><span class="text-slate-500">Email:</span> <?= e(user()['email']) ?></p>
    <p class="text-sm mb-4"><span class="text-slate-500">Role:</span> <?= e(ucfirst(user()['role'])) ?></p>
    <?php if ($me): ?>
    <form method="post" data-validate novalidate class="space-y-3 border-t pt-4">
      <?= csrf_field() ?><input type="hidden" name="action" value="contact">
      <h3 class="font-medium">Contact information</h3>
      <div><label class="block text-sm mb-1">Phone</label><input name="phone" value="<?= e($_POST['phone'] ?? $me['phone']) ?>" class="<?= $I ?>"><?= field_err($errors, 'phone') ?></div>
      <div><label class="block text-sm mb-1">Birthdate</label><input type="date" name="birthdate" max="<?= date('Y-m-d') ?>" value="<?= e($_POST['birthdate'] ?? $me['birthdate']) ?>" class="<?= $I ?>"><?= field_err($errors, 'birthdate') ?></div>
      <div><label class="block text-sm mb-1">Address</label><input name="address" maxlength="255" value="<?= e($_POST['address'] ?? $me['address']) ?>" class="<?= $I ?>"><?= field_err($errors, 'address') ?></div>
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-semibold">Save</button>
    </form>
    <?php endif; ?>
  </section>
  <section class="bg-white rounded-xl shadow-sm p-5 h-fit">
    <h2 class="font-semibold mb-3">Change password</h2>
    <form method="post" data-validate novalidate class="space-y-3">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div><label class="block text-sm mb-1">Current password</label><input type="password" name="current" required class="<?= $I ?>"><?= field_err($errors, 'current') ?></div>
      <div><label class="block text-sm mb-1">New password</label><input id="password" type="password" name="password" required minlength="8" class="<?= $I ?>">
        <div class="h-1.5 bg-slate-200 rounded mt-2"><div id="strengthBar" class="h-1.5 rounded w-0 transition-all"></div></div><?= field_err($errors, 'password') ?></div>
      <div><label class="block text-sm mb-1">Confirm new password</label><input id="confirm" type="password" name="confirm" required class="<?= $I ?>"><?= field_err($errors, 'confirm') ?></div>
      <button class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2 rounded-lg font-semibold">Update password</button>
    </form>
  </section>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
