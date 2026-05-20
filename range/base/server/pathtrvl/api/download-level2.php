<?php
/**
 * HeaSec天积安全团队 - 路径穿越靶场 - 第二关下载接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-31
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec PathTrvl Range v1.0.0');

require_once __DIR__ . '/../includes/functions.php';

$filename = isset($_POST['filename']) ? $_POST['filename'] : '';

if (empty($filename)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '文件名不能为空']);
    exit;
}

$downloadsDir = getDownloadsDir(2);

// 第二关：单次替换../（漏洞点：str_replace只扫描一次）
$filename = str_replace('../', '', $filename);
$filepath = $downloadsDir . '/' . $filename;

// 检查文件是否存在
if (!file_exists($filepath) || !is_file($filepath)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => '文件不存在']);
    exit;
}

$downloadName = basename($filepath);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filepath);
exit;
