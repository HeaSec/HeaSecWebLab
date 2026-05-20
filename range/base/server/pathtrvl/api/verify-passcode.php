<?php
/**
 * HeaSec天积安全团队 - 路径穿越靶场 - 通关密码验证接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-29
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec PathTrvl Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$level = isset($data['level']) ? intval($data['level']) : 0;
$passcode = isset($data['passcode']) ? trim($data['passcode']) : '';

if (empty($passcode)) {
    sendJsonResponse(false, '请输入通关密码');
}

if ($level < 1 || $level > 3) {
    sendJsonResponse(false, '无效的关卡编号');
}

// 确保对应关卡的secret.php存在
$secretPath = getSecretFilePath($level);
generateSecretFile($secretPath, $level);

// 从secret.php提取通关密码
$storedPasscode = extractPasscode($secretPath);

if ($storedPasscode && $storedPasscode === $passcode) {
    sendJsonResponse(true, '验证通过', ['passed' => true]);
} else {
    sendJsonResponse(false, '通关密码错误');
}
