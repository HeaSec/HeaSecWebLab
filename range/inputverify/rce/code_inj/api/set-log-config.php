<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 设置日志文件名接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/../../../common/includes/session_manager.php';
HeaSec_InitRangeSession('code_inj');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$logFileName = $data['log_filename'] ?? '';

if (empty($logFileName)) {
    sendJsonResponse(false, '请指定日志文件名');
}

// 仅做基本字符清理，不限制文件扩展名
$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', $logFileName);
if (empty($safeName)) {
    sendJsonResponse(false, '日志文件名无效');
}

$_SESSION['code_inj_log_file'] = $safeName;

sendJsonResponse(true, '日志配置成功', [
    'log_filename' => $safeName,
    'log_filepath' => 'logs/' . $safeName
]);
