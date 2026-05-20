<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/../../../common/includes/HeaSec_Database.php';
require_once dirname(__DIR__) . '/../../../common/includes/session_manager.php';
HeaSec_InitRangeSession('code_inj');

$basePath = dirname(__DIR__);

// 删除所有关卡的secret.php文件
$secretFiles = [
    $basePath . '/config/secret.php',
    $basePath . '/config/level2/secret.php',
    $basePath . '/config/level3/secret.php'
];

foreach ($secretFiles as $file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

// 清空第一关模板文件
$templateDir = $basePath . '/templates/';
if (is_dir($templateDir)) {
    $files = scandir($templateDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.htaccess') {
            continue;
        }
        $filepath = $templateDir . $file;
        if (is_file($filepath)) {
            unlink($filepath);
        }
    }
}

// 清空第二关备份文件
$backupDir = $basePath . '/backups/';
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.htaccess') {
            continue;
        }
        $filepath = $backupDir . $file;
        if (is_file($filepath)) {
            unlink($filepath);
        }
    }
}

// 清空第三关日志文件
$logDir = $basePath . '/logs/';
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.htaccess') {
            continue;
        }
        $filepath = $logDir . $file;
        if (is_file($filepath)) {
            unlink($filepath);
        }
    }
}

// 清除第三关日志配置session
if (isset($_SESSION['code_inj_log_file'])) {
    unset($_SESSION['code_inj_log_file']);
}

// 重置第二关数据库
try {
    $initSqlFile = $basePath . '/database/init_database.sql';
    if (file_exists($initSqlFile)) {
        $pdo = HeaSec_Database::getServerConnection();
        $sql = file_get_contents($initSqlFile);
        // 去除注释
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        // 去除 DELIMITER 语句
        $sql = preg_replace('/DELIMITER\s+\S+/i', '', $sql);
        // 按分号分割并执行
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function ($stmt) {
                return !empty($stmt);
            }
        );
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
    }
} catch (Exception $e) {
    // 数据库重置失败不阻断整个重置流程
}

sendJsonResponse(true, '重置成功');
