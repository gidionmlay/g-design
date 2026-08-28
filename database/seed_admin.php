<?php
/**
 * G DESIGN — idempotent admin seeder.
 *
 * Usage:  php database/seed_admin.php
 *
 * Creates a single development administrator. Safe to run repeatedly — never
 * duplicates. Reads credentials from environment variables:
 *
 *   ADMIN_EMAIL=     (default: admin@example.com)
 *   ADMIN_PASSWORD=  (no default; generated if absent — never hardcoded)
 *   ADMIN_NAME=      (default: G DESIGN Administrator)
 *   ADMIN_USERNAME=  (default: admin)
 *
 * A real production password must NOT be hardcoded in source. Pass it via the
 * environment (e.g. ADMIN_PASSWORD=<secret> php database/seed_admin.php).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/backend/bootstrap.php';

if (!function_exists('env_get') || true) {
    function env_get(string $key, string $default = ''): string
    {
        $v = $_ENV[$key] ?? getenv($key);
        return is_string($v) && $v !== '' ? $v : $default;
    }
}

$email = strtolower(trim(env_get('ADMIN_EMAIL', 'admin@example.com')));
$username = strtolower(trim(env_get('ADMIN_USERNAME', 'admin')));
$name = trim(env_get('ADMIN_NAME', 'G DESIGN Administrator'));
$password = env_get('ADMIN_PASSWORD', '');

if ($password === '') {
    // Development convenience: generate a random password and print it once.
    $password = bin2hex(random_bytes(8));
    $generated = true;
} else {
    $generated = false;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid ADMIN_EMAIL: $email\n");
    exit(1);
}

try {
    $pdo = App\Core\Database::pdo();
} catch (Throwable $e) {
    fwrite(STDERR, "DB connection failed: {$e->getMessage()}\n");
    fwrite(STDERR, "Check your .env DB_* settings and that MySQL is running.\n");
    exit(1);
}

// Idempotency: skip creation if the email already exists.
$stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetchColumn() !== false) {
    printf("Admin already exists (%s). No duplicate created.\n", $email);
    exit(0);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO admins (username, email, password_hash, full_name, role, is_active)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$username, $email, $hash, $name, 'admin', 1]);

printf("Admin created: %s (%s)\n", $name, $email);
if ($generated) {
    printf("Generated password: %s\n", $password);
} else {
    printf("Password set from environment.\n");
}
