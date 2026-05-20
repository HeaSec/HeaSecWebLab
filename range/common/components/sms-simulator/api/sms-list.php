<?php
/**
 * HeaSec天积安全团队 - 获取短信列表接口
 * API: Get SMS List
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
    // 获取GET参数
    $phoneId = isset($_GET['phone_id']) ? intval($_GET['phone_id']) : 0;

    // 基础校验：检查必填参数
    if ($phoneId <= 0) {
        throw new Exception('手机号ID不能为空');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 检查手机号是否存在
    $sql = "SELECT id, phone_number FROM heasec_sms_simulator WHERE id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phoneId));
    $phone = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phone) {
        throw new Exception('手机号记录不存在');
    }

    // 查询短信列表
    $sql = "SELECT id, simulator_id, phone_number, sender, message_content, is_read,
                   DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                   DATE_FORMAT(read_at, '%Y-%m-%d %H:%i:%s') as read_at
            FROM heasec_sms_message
            WHERE simulator_id = ?
            ORDER BY created_at DESC
            LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phoneId));
    $smsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 统计未读数量
    $sql = "SELECT COUNT(*) as unread_count FROM heasec_sms_message
            WHERE simulator_id = ? AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phoneId));
    $unreadResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount = intval($unreadResult['unread_count']);

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = '获取短信列表成功';
    $response['data'] = array(
        'phone_id' => $phoneId,
        'phone_number' => $phone['phone_number'],
        'sms_list' => $smsList,
        'unread_count' => $unreadCount,
        'total_count' => count($smsList)
    );

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
