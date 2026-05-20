<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 查看日志内容接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/../../../common/includes/session_manager.php';
HeaSec_InitRangeSession('code_inj');

$logFileName = isset($_SESSION['code_inj_log_file']) ? $_SESSION['code_inj_log_file'] : null;

if (empty($logFileName)) {
    sendJsonResponse(true, '', [
        'data' => [
            'content' => '尚未配置日志文件，请先设置日志文件名',
            'log_file' => ''
        ]
    ]);
}

$logDir = dirname(__DIR__) . '/logs/';
$logFile = $logDir . $logFileName;

if (!file_exists($logFile)) {
    sendJsonResponse(true, '', [
        'data' => [
            'content' => '日志文件为空',
            'log_file' => $logFileName
        ]
    ]);
}

$content = file_get_contents($logFile);

sendJsonResponse(true, '', [
    'data' => [
        'content' => $content,
        'log_file' => $logFileName
    ]
]);
