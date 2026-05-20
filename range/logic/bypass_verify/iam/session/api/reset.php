<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 重置接口
 * 版本: v1.0.0
 *
 * 重置数据库、清除会话、重新生成通关密码、创建管理员会话
 */

header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once '../includes/functions.php';

initRangeSession(2);

// 使用公共函数获取Cookie路径
$cookiePath = getRangeCookiePath();

$sessionName = 'HEASEC_RANGE_SESSION_SESSION';

// 步骤1：执行数据库重置脚本
$initSqlFile = '../database/init_database.sql';

if (file_exists($initSqlFile)) {
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    $sql = file_get_contents($initSqlFile);

    // 移除注释
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/^DELIMITER\s+.*$/im', '', $sql);

    $sqlStatements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($sqlStatements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (Exception $e) {
                error_log('[HeaSec Session Reset] SQL执行警告: ' . $e->getMessage());
            }
        }
    }

    // 步骤2：为每关生成通关密码
    for ($i = 1; $i <= 3; $i++) {
        generatePasscode($i, $pdo);
    }

    // 步骤3：生成第二关管理员密码
    $adminPassword = generateAdminPassword(10);
    $stmt = $pdo->prepare("UPDATE heasec_session_users SET password = ? WHERE level = 2 AND username = 'admin'");
    $stmt->execute([$adminPassword]);

    // 步骤4-5：确保第二关管理员活跃会话存在（带重试、写后验证、自动修复）
    $adminSessionId = ensureAdminSession($pdo, $cookiePath);
    if (empty($adminSessionId)) {
        sendJsonResponse(false, '系统初始化失败：无法创建管理员会话');
    }
}

// 步骤6：清除当前用户的会话数据，创建新会话
session_write_close();
$newSessionId = generateRandomSessionId();
session_id($newSessionId);
session_name($sessionName);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => $cookiePath,
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (!session_start()) {
    error_log('[HeaSec Session Reset] 用户会话创建失败');
    sendJsonResponse(false, '系统初始化失败：无法创建用户会话');
}

sendJsonResponse(true, '重置成功');
