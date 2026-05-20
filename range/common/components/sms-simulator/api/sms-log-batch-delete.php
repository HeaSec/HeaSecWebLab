<?php
/**
 * HeaSec天积安全团队 - 批量删除短信日志接口
 * API: Batch Delete SMS Logs
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
    // 获取POST数据
    $postData = file_get_contents('php://input');
    $requestData = json_decode($postData, true);

    // 如果JSON解析失败，尝试从$_POST获取
    if (empty($requestData)) {
        $requestData = $_POST;
    }

    // 获取日志ID列表
    $logIds = isset($requestData['log_ids']) ? $requestData['log_ids'] : array();

    // 基础校验
    if (empty($logIds) || !is_array($logIds)) {
        throw new Exception('日志ID列表不能为空');
    }

    // 过滤并验证ID
    $logIds = array_filter($logIds, function($id) {
        return is_numeric($id) && intval($id) > 0;
    });

    if (empty($logIds)) {
        throw new Exception('没有有效的日志ID');
    }

    // 转换为整数
    $logIds = array_map('intval', $logIds);

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 构建占位符
    $placeholders = str_repeat('?,', count($logIds) - 1) . '?';

    // 批量删除日志
    $sql = "DELETE FROM heasec_sms_log WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($logIds);

    $deletedCount = $stmt->rowCount();

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = "成功删除 {$deletedCount} 条日志";
    $response['data'] = array(
        'deleted_count' => $deletedCount
    );

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
