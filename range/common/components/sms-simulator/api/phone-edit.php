<?php
/**
 * HeaSec天积安全团队 - 编辑手机号接口
 * API: Edit Phone Number
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
    $id = isset($requestData['id']) ? intval($requestData['id']) : 0;
    $phoneNumber = isset($requestData['phone_number']) ? trim($requestData['phone_number']) : '';

    // 基础校验：检查必填参数
    if ($id <= 0) {
        throw new Exception('手机号ID不能为空');
    }

    if (empty($phoneNumber)) {
        throw new Exception('手机号不能为空');
    }

    // 验证手机号格式（中国大陆手机号：1开头，11位数字）
    // 增加：限制不能以110开头
    if (strpos($phoneNumber, '110') === 0) {
        throw new Exception('保留号段(110)不允许注册');
    }

    if (!preg_match('/^1[3-9]\d{9}$/', $phoneNumber)) {
        throw new Exception('手机号格式不正确');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 检查手机号ID是否存在
    $sql = "SELECT id FROM heasec_sms_simulator WHERE id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($id));
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('手机号记录不存在');
    }

    // 检查新手机号是否已被其他记录使用
    $sql = "SELECT id FROM heasec_sms_simulator WHERE phone_number = ? AND id != ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phoneNumber, $id));
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('该手机号已被其他记录使用');
    }

    // 更新手机号
    $sql = "UPDATE heasec_sms_simulator SET phone_number = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($phoneNumber, $id));

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = '编辑手机号成功';
    $response['data'] = array(
        'id' => $id,
        'phone_number' => $phoneNumber
    );

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>