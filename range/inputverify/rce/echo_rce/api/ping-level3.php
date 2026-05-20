<?php
/**
 * HeaSec天积安全团队 - 回显型命令注入靶场 - 第三关网络诊断接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('echo_rce');

require_once '../includes/functions.php';
initEchoRceSession();

$ip = $_GET['ip'] ?? '';

if (empty($ip)) {
    sendJsonResponse(false, '请输入IP地址');
}

$isWindows = ($_SESSION['echo_rce_os'] === 'windows');
$pingCmd = $isWindows ? 'ping -n 1 ' : 'ping -c 1 ';

// 第三关：安全过滤处理
if (preg_match('/\s/', $ip)) {
    sendJsonResponse(false, '检测到空白字符，请求已被拒绝');
}

$command = $pingCmd . $ip;

$output = [];
$returnVar = 0;
exec($command, $output, $returnVar);
$result = toUtf8(implode("\n", $output));

$detectResult = detectCommandExecution($result, 3, $isWindows);

if ($detectResult['detected']) {
    $_SESSION['echo_rce_level3_passed'] = true;
}

sendJsonResponse(true, '诊断完成', [
    'output' => $result,
    'detected' => $detectResult['detected'],
    'passcode' => $detectResult['passcode'] ?? null
]);
