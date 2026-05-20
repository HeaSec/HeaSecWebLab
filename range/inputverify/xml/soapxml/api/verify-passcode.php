<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场统一通关密码验证接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['level']) || !isset($input['passcode'])) {
    sendJsonResponse(false, '参数错误');
}

$level = (int)$input['level'];
$passcode = trim($input['passcode']);

if ($level < 1 || $level > 3) {
    sendJsonResponse(false, '无效的关卡编号');
}

if (empty($passcode)) {
    sendJsonResponse(false, '请输入通关密码');
}

$secretPath = getSecretFilePath($level);
generateSecretFile($secretPath);

$storedPasscode = extractPasscode($secretPath);

if ($storedPasscode !== null && $storedPasscode === $passcode) {
    sendJsonResponse(true, '验证通过', ['passed' => true]);
} else {
    sendJsonResponse(false, '通关密码错误');
}
