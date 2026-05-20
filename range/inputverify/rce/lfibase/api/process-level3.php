<?php
/**
 * HeaSec天积安全团队 - 文件包含基础靶场 - 第三关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-16
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec LfiBase Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/includes/functions.php';

$page = $_GET['page'] ?? '';

// 过滤空字节字符，防止 %00 截断绕过后缀名白名单
$page = str_replace(chr(0), '', $page);

if ($page === '') {
    sendJsonResponse(false, '请指定要查看的页面');
}

// ===== 防御措施1：后缀名白名单 =====
$allowedExtensions = ['jpg', 'jpeg', 'gif', 'png', 'txt'];
$ext = strtolower(pathinfo($page, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    sendJsonResponse(false, '不允许的文件类型，仅支持: ' . implode(', ', $allowedExtensions));
}

// ===== 防御措施2：关键词过滤 =====
$blockedKeywords = ['php://', 'data://', 'zip://', 'phar://', 'expect://', 'input:'];
foreach ($blockedKeywords as $keyword) {
    if (stripos($page, $keyword) !== false) {
        sendJsonResponse(false, '请求中包含不允许的关键字');
    }
}

// 通过所有检查后，拼接完整路径并包含文件
// 判断是否为流包装器协议，流包装器不需要拼接基础路径
if (preg_match('/^[a-zA-Z]+:\/\//', $page)) {
    $targetFile = $page;
} else {
    $targetFile = dirname(__DIR__) . '/' . $page;
}

ob_start();
include($targetFile);
$content = ob_get_clean();

sendJsonResponse(true, '页面加载成功', [
    'content' => $content,
    'page' => $page
]);
