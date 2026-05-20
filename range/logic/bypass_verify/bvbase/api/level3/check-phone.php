<?php
/**
 * HeaSec天积安全团队 - 第三关：手机号校验接口
 * 版本: v1.0.0
 * 创建日期: 2026-01-17
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 第三关：前端调用此接口校验手机号，结果回填到隐藏字段
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec API v1.0.0');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'success' => false,
        'message' => ' 只允许POST请求'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

// 获取参数
$phone = isset($data['phone']) ? trim($data['phone']) : '';

// 初始化响应
$response = array(
    'success' => true,
    'allowed' => false,
    'status' => 'error',
    'message' => ''
);

// 基础格式校验：必须是1开头的11位数字
if (!preg_match('/^1\d{10}$/', $phone)) {
    $response['status'] = 'error';
    $response['message'] = '手机号格式不正确，请输入11位手机号';
} else if (strpos($phone, '110') === 0) {
    // 110开头的手机号：校验通过
    $response['allowed'] = true;
    $response['status'] = 'success';
    $response['message'] = '手机号校验通过';
} else {
    // 其他手机号：格式正确但不在领奖范围
    $response['status'] = 'fail';
    $response['message'] = '手机号不在领奖范围内';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>