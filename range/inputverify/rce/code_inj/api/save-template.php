<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 保存模板接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');

require_once dirname(__DIR__) . '/includes/functions.php';

$templateName = $_POST['template_name'] ?? '';
$templateContent = $_POST['template_content'] ?? '';

if (empty($templateName) || empty($templateContent)) {
    sendJsonResponse(false, '模板名称和内容不能为空');
}

$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $templateName);
$templateDir = dirname(__DIR__) . '/templates/';
if (!is_dir($templateDir)) {
    mkdir($templateDir, 0755, true);
}
$templateFile = $templateDir . $safeName . '.php';

file_put_contents($templateFile, $templateContent);

sendJsonResponse(true, '模板保存成功', [
    'filename' => $safeName . '.php',
    'filepath' => 'templates/' . $safeName . '.php'
]);
