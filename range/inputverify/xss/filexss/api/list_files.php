<?php
/**
 * HeaSec天积安全团队 - 文件管理API - 获取文件列表
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec File Manager API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . '/../uploads/temp/';

if (!is_dir($uploadDir)) {
    echo json_encode(['success' => true, 'files' => []]);
    exit;
}

$filesList = [];
$iterator = new FilesystemIterator($uploadDir, FilesystemIterator::SKIP_DOTS);

foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile()) {
        $filesList[] = [
            'name' => $fileInfo->getFilename(),
            'size' => $fileInfo->getSize(),
            'path' => 'uploads/temp/' . $fileInfo->getFilename(),
            'time' => date('Y-m-d H:i:s', $fileInfo->getMTime())
        ];
    }
}

// 按照修改时间排序，最新上传的在前面
usort($filesList, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

echo json_encode([
    'success' => true,
    'files' => $filesList
]);
?>
