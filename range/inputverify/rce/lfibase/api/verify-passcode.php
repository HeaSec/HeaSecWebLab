<?php
/**
 * HeaSec天积安全团队 - 文件包含基础靶场 - 通关密码验证接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-16
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec LfiBase Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

// 获取请求数据
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

// 获取对应关卡的secret文件路径
$secretPath = getSecretFilePath($level);

// 确保secret文件存在（根据关卡使用不同格式）
if ($level === 1) {
    generateSecretFile($secretPath, true);
} else {
    generateSecretFile($secretPath, false);
}

// 根据关卡使用不同的验证方式
if ($level === 1) {
    $storedPasscode = extractTextPasscode($secretPath);
} else {
    $storedPasscode = extractPasscode($secretPath);
}

if ($storedPasscode !== false && $passcode === $storedPasscode) {
    sendJsonResponse(true, '验证通过', ['passed' => true]);
} else {
    sendJsonResponse(false, '通关密码错误');
}
