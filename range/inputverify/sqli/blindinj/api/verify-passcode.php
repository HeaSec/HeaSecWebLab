<?php
/**
 * HeaSec天积安全团队 - SQL盲注靶场 - 通关密码验证接口
 * 版本: v1.0.0
 * 功能: 统一验证各关卡通关密码
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 获取请求数据（兼容JSON和FormData）
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$level = isset($data['level']) ? intval($data['level']) : 0;
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

if ($level < 1 || $level > 3) {
    sendJsonResponse(false, '无效的关卡编号');
}

if (empty($passcode)) {
    sendJsonResponse(false, '请输入通关密码');
}

// 获取对应关卡的密码并比对
$storedPasscode = getPasscode($level);

if ($storedPasscode !== false && $passcode === $storedPasscode) {
    sendJsonResponse(true, '验证通过', ['passed' => true]);
} else {
    sendJsonResponse(false, '通关密码错误');
}
