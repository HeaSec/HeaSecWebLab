<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 预览渲染模板接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

$templateFile = $_GET['file'] ?? '';

if (empty($templateFile)) {
    sendJsonResponse(false, '请指定要预览的模板');
}

$basePath = dirname(__DIR__) . '/templates/';
$fullPath = $basePath . basename($templateFile);

if (!file_exists($fullPath)) {
    sendJsonResponse(false, '模板文件不存在');
}

ob_start();
include($fullPath);
$content = ob_get_clean();

sendJsonResponse(true, '模板渲染成功', [
    'content' => $content,
    'file' => $templateFile
]);
