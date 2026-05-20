<?php
/**
 * HeaSec天积安全团队 - 发送验证码接口
 * Send SMS Verification Code API
 * 版本: v1.1.0
 * 创建日期: 2026-01-21
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec SMS Bypass API v1.1.0');

// 设置公共组件路径
$commonBasePath = '../../../../common/';

// 引入数据库组件
require_once $commonBasePath . 'includes/database.php';

// 引入配置文件
require_once '../includes/config.php';

// 初始化响应
$response = [
    'success' => false,
    'code' => '',
    'message' => '',
    'data' => null
];

try {
    // 获取POST数据
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON_PARSE_ERROR|请求数据格式错误');
    }

    // 获取用户名参数并验证
    $username = isset($data['username']) ? trim($data['username']) : '';

    if ($username === '') {
        throw new Exception('USERNAME_MISSING|请提供用户名');
    }

    // 验证用户名是否为admin
    if ($username !== 'admin') {
        throw new Exception('USERNAME_NOT_EXIST|用户名不存在');
    }

    // 获取手机号参数
    $phoneParam = isset($data['phone']) ? $data['phone'] : null;

    if ($phoneParam === null || $phoneParam === '') {
        throw new Exception('PHONE_MISSING|请提供手机号');
    }

    // 解析手机号列表
    $phoneList = parsePhoneList($phoneParam);

    if (empty($phoneList)) {
        throw new Exception('NO_VALID_PHONE|未找到有效的手机号，请确保手机号是1开头的11位数字');
    }

    // 关联手机号校验：只在多值注入和参数污染场景下检查第一个手机号是否为admin的手机号
    // 直接篡改场景（单手机号）不进行此校验
    $needPhoneCheck = false;
    if (is_array($phoneParam) && count($phoneList) > 1) {
        // 多值注入：数组且有多个手机号
        $needPhoneCheck = true;
    } elseif (is_string($phoneParam) && preg_match(PHONE_SEPARATOR_PATTERN, $phoneParam)) {
        // 参数污染：字符串包含分隔符
        $needPhoneCheck = true;
    }

    if ($needPhoneCheck) {
        $firstPhone = $phoneList[0];
        if ($firstPhone !== ORIGINAL_PHONE) {
            throw new Exception('PHONE_MISMATCH|关联手机号错误');
        }
    }

    // 生成6位数字验证码
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // 向所有有效手机号发送验证码
    require_once $commonBasePath . 'components/sms-simulator/includes/HeaSec_SmsSender.php';
    $message = "您的验证码是：{$code}，5分钟内有效。";

    $sentCount = 0;
    $sentPhones = [];
    foreach ($phoneList as $phone) {
        $result = HeaSec_SmsSender::send($phone, $message, 'smsbypass');
        if ($result['success']) {
            $sentCount++;
            // 脱敏处理：138****8888
            $sentPhones[] = substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
    }

    // 保存验证码到数据库（替代session存储）
    saveVerificationCode($code, $phoneList, $data);

    // 返回成功响应
    $response['success'] = true;
    $response['code'] = 'SUCCESS';
    $response['message'] = "验证码已发送到{$sentCount}个手机号";
    $response['data'] = [
        'sent_count' => $sentCount,
        'phones' => $sentPhones
    ];

} catch (Exception $e) {
    $parts = explode('|', $e->getMessage(), 2);
    $response['code'] = $parts[0];
    $response['message'] = isset($parts[1]) ? $parts[1] : $parts[0];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
