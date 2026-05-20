<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 3rdPay Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 初始化靶场会话
HeaSec_InitRangeSession('3rdpay');

// 引入公共函数
require_once '../includes/functions.php';

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getServerConnection();

    // 读取SQL文件
    $sqlFile = dirname(__DIR__) . '/database/init_database.sql';
    if (!file_exists($sqlFile)) {
        sendJsonResponse(false, '数据库初始化文件不存在');
    }

    $sqlContent = file_get_contents($sqlFile);

    // 移除注释并分割SQL语句
    $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
    $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);
    $sqlStatements = array_filter(array_map('trim', explode(';', $sqlContent)));

    $pdo->beginTransaction();

    foreach ($sqlStatements as $sql) {
        if (!empty($sql)) {
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                error_log('[HeaSec] Reset SQL error: ' . $e->getMessage());
            }
        }
    }

    $pdo->commit();

    // 清除会话
    session_unset();
    session_destroy();

    sendJsonResponse(true, '重置成功');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[HeaSec] Reset error: ' . $e->getMessage());
    sendJsonResponse(false, '重置失败：' . $e->getMessage());
}
