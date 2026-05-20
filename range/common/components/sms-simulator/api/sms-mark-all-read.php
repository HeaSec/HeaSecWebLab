<?php
/**
 * HeaSec天积安全团队 - 全部标记已读接口
 * API: Mark All SMS as Read
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

    // 获取参数
    $phoneId = isset($requestData['phone_id']) ? intval($requestData['phone_id']) : 0;

    // 基础校验：检查必填参数
    if ($phoneId <= 0) {
        throw new Exception('手机号ID不能为空');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 检查手机号是否存在
    $sql = "SELECT id FROM heasec_sms_simulator WHERE id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phoneId));
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('手机号记录不存在');
    }

    // 更新该手机号的所有未读短信为已读状态
    $sql = "UPDATE heasec_sms_message
            SET is_read = 1, read_at = NOW()
            WHERE simulator_id = ? AND is_read = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phoneId));
    $updatedCount = $stmt->rowCount();

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = '全部标记已读成功';
    $response['data'] = array(
        'phone_id' => $phoneId,
        'updated_count' => $updatedCount
    );

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
