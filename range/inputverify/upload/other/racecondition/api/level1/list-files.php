<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 第一关获取文件列表接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 条件竞争上传 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 公共组件路径
$commonBasePath = '../../../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('racecondition');
HeaSec_ValidateSession();

// images目录路径
$imagesDir = dirname(__DIR__, 2) . '/images/';

// 获取文件列表
$uploadedFiles = [];
if (file_exists($imagesDir)) {
    $files = scandir($imagesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== 'secret.php' && $file !== '.htaccess' && $file !== 'tmp') {
            $uploadedFiles[] = [
                'name' => $file,
                'path' => 'images/' . $file,
                'size' => filesize($imagesDir . $file)
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'files' => $uploadedFiles
]);
?>
