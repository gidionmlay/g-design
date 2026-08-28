<?php
/**
 * Admin data access.
 *
 * Passwords are stored ONLY as password_hash() output. The password_hash
 * column is never selected for API responses.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AdminModel
{
    /** @var array<int,string> columns returned to the outside world */
    private const PUBLIC_COLUMNS = [
        'id',
        'username',
        'email',
        'full_name',
        'role',
        'is_active',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Create a new admin.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function create(array $data): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            'INSERT INTO admins
                (username, email, password_hash, full_name, role, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['full_name'] ?? null,
            $data['role'] ?? 'admin',
            $data['is_active'] ?? 1,
        ]);

        $admin = self::findById((int) $pdo->lastInsertId());

        return self::withoutHash($admin);
    }

    /** @return array<string,mixed>|null */
    public static function findById(int $id): ?array
    {
        return self::findBy('id', (string) $id);
    }

    /** @return array<string,mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', strtolower(trim($email)));
    }

    /** @return array<string,mixed>|null */
    public static function findByUsername(string $username): ?array
    {
        return self::findBy('username', trim($username));
    }

    /**
     * Locate an admin by a login identifier (email or username).
     *
     * @return array<string,mixed>|null
     */
    public static function findForLogin(string $identifier): ?array
    {
        $val = strtolower(trim($identifier));
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM admins WHERE email = ? OR username = ? LIMIT 1'
        );
        $stmt->execute([$val, trim($identifier)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public static function updateLastLogin(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE admins SET last_login_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    /**
     * Fetch the stored password hash for verification (never exposed).
     */
    public static function findPasswordHash(int $id): ?string
    {
        $stmt = Database::pdo()->prepare(
            'SELECT password_hash FROM admins WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();
        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    /**
     * Replace the password hash with a freshly generated one.
     * `updated_at` is maintained automatically by the column's ON UPDATE clause.
     */
    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE admins SET password_hash = ? WHERE id = ?'
        );
        $stmt->execute([$passwordHash, $id]);
    }

    public static function updateUsername(int $id, string $username): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE admins SET username = ? WHERE id = ?'
        );
        $stmt->execute([$username, $id]);
    }

    /**
     * Whether a different admin already uses this username.
     */
    public static function usernameExists(string $username, int $exceptId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id FROM admins WHERE username = ? AND id <> ? LIMIT 1'
        );
        $stmt->execute([$username, $exceptId]);
        return $stmt->fetchColumn() !== false;
    }

    public static function deactivate(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE admins SET is_active = 0 WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public static function isActive(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT is_active FROM admins WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $active = $stmt->fetchColumn();
        return $active !== false && (int) $active === 1;
    }

    /**
     * @param string $column one of id/email/username
     * @return array<string,mixed>|null
     */
    private static function findBy(string $column, string $value): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM admins WHERE `$column` = ? LIMIT 1"
        );
        $stmt->execute([$value]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Strip the password_hash from an admin record so it is never leaked.
     *
     * @param array<string,mixed>|null $admin
     * @return array<string,mixed>|null
     */
    public static function withoutHash(?array $admin): ?array
    {
        if ($admin === null) {
            return null;
        }
        $out = [];
        foreach (self::PUBLIC_COLUMNS as $col) {
            if (array_key_exists($col, $admin)) {
                $out[$col] = $admin[$col];
            }
        }
        return $out;
    }
}
