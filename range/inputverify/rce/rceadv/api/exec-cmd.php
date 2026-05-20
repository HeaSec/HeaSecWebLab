<?php
/**
 * HeaSec天积安全团队 - 命令执行实战靶场 - 网络诊断接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-23
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

$host = $_REQUEST['host'] ?? '';
$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

if ($host === '') {
    sendJsonResponse(false, '请输入目标地址');
}

if (containsDestructiveCommand($host)) {
    sendJsonResponse(false, '请求已被安全策略拦截');
}

$command = buildBaseCommand($host);

set_time_limit(10);

$output = [];
$returnVar = 0;
exec($command . ' 2>&1', $output, $returnVar);

if ($isWindows) {
    $output = array_map(function ($line) {
        if (mb_check_encoding($line, 'UTF-8')) {
            return $line;
        }
        return mb_convert_encoding($line, 'UTF-8', 'GBK');
    }, $output);
}

$content = implode("\n", $output);
sendJsonResponse(true, '诊断完成', ['output' => $content]);
