<?php
/**
 * HeaSec天积安全团队 - 命令执行实战靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-23
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, '仅支持POST请求');
}

try {
    $pdo = HeaSec_Database::getConnection('heasec_inputverify');
    $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    // 先尝试系统级清理，再操作数据库
    $cleanupMessages = [];

    if ($isWindows) {
        // 删除创建的heasec用户
        $userOutput = [];
        @exec('net user heasec /delete 2>&1', $userOutput);
        // 删除RDP计划任务
        @exec('schtasks /delete /tn "HeaSecRDP" /f 2>&1');
    } else {
        @exec('userdel -r heasec 2>&1');
        // 移除crontab中的HeaSecRDP定时任务
        @exec('(crontab -l 2>/dev/null | grep -v HeaSecRDP) | crontab - 2>/dev/null');
    }

    // 使用事务清除全部成就记录
    $pdo->beginTransaction();
    $pdo->exec("DELETE FROM heasec_rceadv_achievements");
    $pdo->commit();

    sendJsonResponse(true, '靶场已重置');
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse(false, '重置失败：' . $e->getMessage());
}
