<?php
/**
 * HeaSec天积安全团队 - 文件管理API - 清空文件
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

$uploadDir = __DIR__ . '/../uploads/temp/';

if (!is_dir($uploadDir)) {
    echo json_encode(['success' => true, 'message' => '目录已空']);
    exit;
}

$iterator = new FilesystemIterator($uploadDir, FilesystemIterator::SKIP_DOTS);

$deletedCount = 0;
foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile()) {
        if (unlink($fileInfo->getPathname())) {
            $deletedCount++;
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => '成功清理 ' . $deletedCount . ' 个文件'
]);
?>
