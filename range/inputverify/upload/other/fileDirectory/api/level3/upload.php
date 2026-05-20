<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 第三关上传接口
 * 漏洞机制：.htaccess覆盖攻击 - 不限制文件类型，可通过上传.htaccess覆盖配置
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件目录执行权限绕过 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 公共组件路径
$commonBasePath = '../../../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('filedirectory');
HeaSec_ValidateSession();

// 处理上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadedFile = $_FILES['file'];

    // 检查上传错误
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => '文件上传失败：错误码 ' . $uploadedFile['error']
        ]);
        exit;
    }

    // 文件大小限制（1MB）
    $maxSize = 1 * 1024 * 1024;
    if ($uploadedFile['size'] > $maxSize) {
        echo json_encode([
            'success' => false,
            'message' => '文件大小超过限制（最大1MB）！'
        ]);
        exit;
    }

    // images目录路径
    $imagesDir = dirname(__DIR__, 2) . '/exec/images/';

    // 确保images目录存在
    if (!file_exists($imagesDir)) {
        mkdir($imagesDir, 0755, true);
    }

    // 获取安全的文件名（PHP会自动处理，移除路径信息）
    $fileName = basename($uploadedFile['name']);

    // 【漏洞点】不限制文件类型，允许上传任意文件
    // 攻击者可以上传 .htaccess 覆盖原有配置
    // 然后上传 shell.php 执行恶意代码

    $targetPath = $imagesDir . $fileName;

    // 移动上传的文件
    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'message' => '上传成功！',
            'filename' => $fileName
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '文件上传失败，请检查目录权限！'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '无效的请求'
    ]);
}
?>
