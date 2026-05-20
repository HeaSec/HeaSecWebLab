<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 重置接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Discount Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

HeaSec_InitRangeSession('discount');

require_once  '../includes/functions.php';

// 执行数据库重置脚本
$initSqlFile = '../database/init_database.sql';

if (file_exists($initSqlFile)) {
    $pdo = HeaSec_Database::getConnection('heasec_logic');

    // 读取SQL文件
    $sql = file_get_contents($initSqlFile);

    // 移除注释
    $sql = preg_replace('/^--.*$/m', '', $sql); // 去除行首注释
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // 去除块注释

    // 移除DELIMITER语句（PDO不支持DELIMITER命令）
    $sql = preg_replace('/^DELIMITER\s+.*$/im', '', $sql);

    // 分割SQL语句并逐条执行
    $sqlStatements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($sqlStatements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (Exception $e) {
                // 忽略已存在等警告错误，继续执行
                error_log('[HeaSec Discount Reset] SQL执行警告: ' . $e->getMessage());
            }
        }
    }
}

// 清除所有会话数据
for ($i = 1; $i <= 3; $i++) {
    unset($_SESSION['discount_user_id_level' . $i]);
    unset($_SESSION['discount_username_level' . $i]);
    unset($_SESSION['discount_passcode_level' . $i]);
}

sendJsonResponse(true, '重置成功');
