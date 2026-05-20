<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 删除备份文件接口
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
    sendJsonResponse(false, '请指定要删除的备份文件');
}

$backupDir = dirname(__DIR__) . '/backups/';
$fullPath = $backupDir . basename($file);

if (!file_exists($fullPath)) {
    sendJsonResponse(false, '备份文件不存在');
}

if (unlink($fullPath)) {
    sendJsonResponse(true, '备份文件删除成功');
} else {
    sendJsonResponse(false, '备份文件删除失败');
}
