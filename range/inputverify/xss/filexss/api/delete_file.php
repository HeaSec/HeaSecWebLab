<?php
/**
 * HeaSec天积安全团队 - 文件管理API - 删除文件
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec File Manager API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$filename = isset($_POST['filename']) ? basename($_POST['filename']) : '';

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => '文件名不合法']);
    exit;
}

$filePath = __DIR__ . '/../uploads/temp/' . $filename;

if (file_exists($filePath)) {
    if (unlink($filePath)) {
        echo json_encode(['success' => true, 'message' => '删除成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '物理删除失败，无权限或文件被占用']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '文件不存在']);
}
?>
