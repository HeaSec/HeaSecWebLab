<?php
/**
 * HeaSec天积安全团队 - 清空短信日志接口
 * API: Clear SMS Logs
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('HeavenlySecret: HeaSec-API-v1.0.0');

// 定义访问常量并引入数据库组件
define('HEASEC_RANGE_ACCESS', true);
require_once dirname(dirname(dirname(__DIR__))) . '/includes/HeaSec_Database.php';

// 初始化响应数组
$response = array(
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => time()
);

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 获取清空前的日志数量
    $sql = "SELECT COUNT(*) as count FROM heasec_sms_log";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 清空所有日志
    $sql = "TRUNCATE TABLE heasec_sms_log";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = "成功清空 {$beforeCount} 条日志";
    $response['data'] = array(
        'cleared_count' => $beforeCount
    );

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
