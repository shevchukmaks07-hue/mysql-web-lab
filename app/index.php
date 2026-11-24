<?php
/*
 * Вебзастосунок «форма → INSERT → SELECT» для MySQL.
 *
 * GET  /           – показати форму і всі записи (SELECT)
 * POST /           – записати дані з форми (INSERT), потім редирект на GET
 *                    (шаблон Post/Redirect/Get: F5 не дублює запис)
 */

declare(strict_types=1);

$cfg = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'webdb',
    'user' => getenv('DB_USER') ?: 'webuser',
    'pass' => getenv('DB_PASS') ?: 'webpass',
];

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$error = null;
$rows  = [];

try {
    // 1) TCP-з'єднання з MySQL на порт 3306 + рукостискання + автентифікація
    $pdo = new PDO(
        "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4",
        $cfg['user'],
        $cfg['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name  = trim((string)($_POST['full_name']  ?? ''));
        $group = trim((string)($_POST['group_name'] ?? ''));
        $email = trim((string)($_POST['email']      ?? ''));

        if ($name === '' || $group === '' || $email === '') {
            throw new InvalidArgumentException('Заповніть усі поля.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Некоректний e-mail.');
        }

        // 2) INSERT. Параметризований запит захищає від SQL-ін'єкцій:
        //    PDO сам екранує значення перед відправкою на сервер.
        $stmt = $pdo->prepare(
            'INSERT INTO students (full_name, group_name, email) VALUES (:n, :g, :e)'
        );
        $stmt->execute([':n' => $name, ':g' => $group, ':e' => $email]);

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?added=' . $pdo->lastInsertId(), true, 303);
        exit;
    }

    // 3) SELECT усіх записів
    $rows = $pdo->query(
        'SELECT id, full_name, group_name, email, created_at FROM students ORDER BY id'
    )->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$added = isset($_GET['added']) ? (int)$_GET['added'] : 0;
?>
<!doctype html>
<html lang="uk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MySQL Web Lab</title>
<style>
  :root { --fg:#1d232b; --muted:#66707c; --line:#d9dee4; --accent:#00758f; --bg:#f6f8fa; }
  * { box-sizing: border-box; }
  body { margin:0; font:15px/1.5 system-ui, "Segoe UI", Roboto, sans-serif; color:var(--fg); background:var(--bg); }
  main { max-width: 860px; margin: 40px auto; padding: 0 20px; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  p.sub { color: var(--muted); margin: 0 0 24px; }
  form { display:grid; grid-template-columns: 2fr 1fr 2fr auto; gap:10px; background:#fff;
         border:1px solid var(--line); border-radius:8px; padding:16px; }
  label { display:flex; flex-direction:column; font-size:12px; color:var(--muted); gap:4px; }
  input { font:inherit; padding:8px 10px; border:1px solid var(--line); border-radius:6px; }
  input:focus { outline:2px solid var(--accent); outline-offset:-1px; }
  button { align-self:end; font:inherit; padding:8px 16px; border:0; border-radius:6px;
           background:var(--accent); color:#fff; cursor:pointer; }
  .msg { margin:16px 0; padding:10px 14px; border-radius:6px; }
  .ok  { background:#e5f4ea; color:#1b5e33; }
  .err { background:#fde8e8; color:#8a1c1c; }
  table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff;
          border:1px solid var(--line); border-radius:8px; overflow:hidden; }
  th, td { text-align:left; padding:8px 12px; border-bottom:1px solid var(--line); }
  th { background:#eef1f4; font-size:12px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); }
  tr.new td { background:#fff8db; }
  td.num { font-variant-numeric: tabular-nums; color:var(--muted); }
  code { font-size: 13px; }
  @media (max-width: 700px) { form { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<main>
  <h1>Реєстрація студентів</h1>
  <p class="sub">Заповніть поля та натисніть <kbd>Enter</kbd> — дані потраплять у MySQL
     (<code><?= h($cfg['host'] . ':' . $cfg['port'] . '/' . $cfg['name']) ?></code>).</p>

  <!-- Enter у будь-якому полі = submit, бо у формі є кнопка type="submit" -->
  <form method="post" autocomplete="off">
    <label>ПІБ <input name="full_name" required maxlength="100" autofocus></label>
    <label>Група <input name="group_name" required maxlength="20"></label>
    <label>E-mail <input name="email" type="email" required maxlength="100"></label>
    <button type="submit">Додати</button>
  </form>

  <?php if ($error): ?>
    <div class="msg err">Помилка: <?= h($error) ?></div>
  <?php elseif ($added): ?>
    <div class="msg ok">Запис №<?= $added ?> додано (INSERT), нижче — результат SELECT.</div>
  <?php endif; ?>

  <table>
    <thead><tr><th>id</th><th>ПІБ</th><th>Група</th><th>E-mail</th><th>Створено</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="5" style="color:var(--muted)">Таблиця порожня.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr class="<?= (int)$r['id'] === $added ? 'new' : '' ?>">
        <td class="num"><?= (int)$r['id'] ?></td>
        <td><?= h((string)$r['full_name']) ?></td>
        <td><?= h((string)$r['group_name']) ?></td>
        <td><?= h((string)$r['email']) ?></td>
        <td class="num"><?= h((string)$r['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p class="sub" style="margin-top:12px">Записів: <?= count($rows) ?></p>
</main>
</body>
</html>
