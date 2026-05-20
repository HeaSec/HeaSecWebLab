<?php
/**
 * HeaSec天积安全团队 - XXE绕过靶场 - 下载XML模板接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-10
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec XXEBypass Range v1.0.0');

$templatePath = dirname(__DIR__) . '/templates/product-template.xml';

if (!file_exists($templatePath)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '模板文件不存在']);
    exit;
}

header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="product-template.xml"');
header('Content-Length: ' . filesize($templatePath));

readfile($templatePath);
exit;
