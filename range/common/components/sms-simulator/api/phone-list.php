<?php
/**
 * HeaSec天积安全团队 - 获取手机号列表接口
 * API: Get Phone List
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

    // 查询手机号列表及其短信数量（按ID升序，不按默认状态排序）
    $sql = "SELECT p.id, p.phone_number, p.is_default, p.status, p.created_at, p.updated_at,
                   COALESCE(COUNT(s.id), 0) as sms_count
            FROM heasec_sms_simulator p
            LEFT JOIN heasec_sms_message s ON p.id = s.simulator_id
            GROUP BY p.id, p.phone_number, p.is_default, p.status, p.created_at, p.updated_at
            ORDER BY p.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $phones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = '获取手机号列表成功';
    $response['data'] = $phones;

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
