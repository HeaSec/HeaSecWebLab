<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 第三关重置接口
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

// 删除images目录中的所有文件（除了secret.php和.htaccess）
$deletedCount = 0;
if (file_exists($imagesDir)) {
    $files = glob($imagesDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $basename = basename($file);
            if ($basename !== 'secret.php' && $basename !== '.htaccess') {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => '重置成功，已删除 ' . $deletedCount . ' 个文件'
]);
?>
