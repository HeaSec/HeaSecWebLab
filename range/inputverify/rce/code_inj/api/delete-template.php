<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 删除模板接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

$file = $data['file'] ?? '';

if (empty($file)) {
    sendJsonResponse(false, '请指定要删除的模板');
}

$templateDir = dirname(__DIR__) . '/templates/';
$fullPath = $templateDir . basename($file);

if (!file_exists($fullPath)) {
    sendJsonResponse(false, '模板文件不存在');
}

if (unlink($fullPath)) {
    sendJsonResponse(true, '模板删除成功');
} else {
    sendJsonResponse(false, '模板删除失败');
}
