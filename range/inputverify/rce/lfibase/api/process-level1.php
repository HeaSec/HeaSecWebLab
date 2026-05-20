<?php
/**
 * HeaSec天积安全团队 - 文件包含基础靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-16
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec LfiBase Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 引入公共函数
require_once dirname(__DIR__) . '/includes/functions.php';

// 接收 page 参数
$page = $_GET['page'] ?? '';

if ($page === '') {
    sendJsonResponse(false, '请指定要查看的页面');
}

// 处理目标文件路径
$baseDir = dirname(__DIR__);
if (preg_match('/^php:\/\/filter\/.*\/resource=(.+)$/', $page, $matches)) {
    // php://filter 伪协议：将 resource 路径转换为绝对路径
    $resourcePath = $matches[1];
    $absoluteResource = realpath($baseDir . '/' . $resourcePath) ?: ($baseDir . '/' . $resourcePath);
    $targetFile = str_replace('resource=' . $resourcePath, 'resource=' . $absoluteResource, $page);
} elseif (preg_match('/^[a-zA-Z]+:\/\//', $page)) {
    // 其他流包装器协议，直接使用
    $targetFile = $page;
} else {
    // 普通文件路径：拼接基于靶场根目录的完整路径（API文件位于api/子目录）
    $targetFile = $baseDir . '/' . $page;
}

// 使用 output buffering 捕获 include() 的输出
ob_start();
include($targetFile);
$content = ob_get_clean();

sendJsonResponse(true, '页面加载成功', [
    'content' => $content,
    'page' => $page
]);
