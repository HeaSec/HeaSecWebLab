<?php
/**
 * HeaSec天积安全团队 - 无回显命令注入靶场 - 通关密码验证接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-21
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec BlindRce Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

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

// 靶场根目录
$basePath = dirname(__DIR__);

if ($level === 1) {
    // 第一关：从database.php提取数据库密码比对
    ensureDatabaseConfig($basePath . '/config/database.php');
    $storedPasscode = extractDbPassword($basePath . '/config/database.php');
} elseif ($level === 2) {
    // 第二关：从passcode.php提取通关密码比对
    generateSecretFile($basePath . '/config/level2/passcode.php');
    $storedPasscode = extractPasscode($basePath . '/config/level2/passcode.php');
} elseif ($level === 3) {
    // 第三关：从passcode.php提取通关密码比对
    generateSecretFile($basePath . '/config/level3/passcode.php');
    $storedPasscode = extractPasscode($basePath . '/config/level3/passcode.php');
} else {
    sendJsonResponse(false, '无效的关卡编号');
}

if ($storedPasscode !== false && $passcode === $storedPasscode) {
    sendJsonResponse(true, '验证通过', ['passed' => true]);
} else {
    sendJsonResponse(false, '通关密码错误');
}
