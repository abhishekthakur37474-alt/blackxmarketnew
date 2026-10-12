<?php

require_once __DIR__ . '/config.php';

function bxm_db()
{
    static $pdo = false;

    if ($pdo !== false) {
        return $pdo;
    }

    $cfg = $GLOBALS['BXM']['mysql'] ?? [];
    $dsn = 'mysql:host=' . ($cfg['host'] ?? 'localhost')
        . ';port=' . ($cfg['port'] ?? 3306)
        . ';dbname=' . ($cfg['database'] ?? '')
        . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, $cfg['username'] ?? '', $cfg['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        bxm_log('db_connect_failed', ['error' => $e->getMessage()]);
        $pdo = null;
    }

    return $pdo;
}

function bxm_db_ok()
{
    return bxm_db() instanceof PDO;
}

function bxm_q($sql, $params = [])
{
    $pdo = bxm_db();
    if (!$pdo) {
        return false;
    }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (Throwable $e) {
        bxm_log('db_query_failed', ['error' => $e->getMessage(), 'sql' => $sql]);
        return false;
    }
}

function bxm_all($sql, $params = [])
{
    $stmt = bxm_q($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

function bxm_row($sql, $params = [])
{
    $stmt = bxm_q($sql, $params);
    if (!$stmt) {
        return null;
    }
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function bxm_val($sql, $params = [])
{
    $row = bxm_row($sql, $params);
    if (!$row) {
        return null;
    }
    return reset($row);
}

function bxm_exec($sql, $params = [])
{
    $stmt = bxm_q($sql, $params);
    return $stmt ? $stmt->rowCount() : false;
}

function bxm_last_id()
{
    $pdo = bxm_db();
    return $pdo ? $pdo->lastInsertId() : null;
}

function bxm_uid($bytes = 8)
{
    return bin2hex(random_bytes($bytes));
}

function bxm_now_ms()
{
    return (int) round(microtime(true) * 1000);
}

function bxm_json_col($value)
{
    if ($value === null || $value === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function bxm_json_encode_col($value)
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
