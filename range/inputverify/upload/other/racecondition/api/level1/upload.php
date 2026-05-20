<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 第一关上传接口
 * 漏洞机制：延迟删除 - 上传后3秒延迟删除危险文件
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

// 定义危险后缀列表
$dangerousExtensions = ['php', 'php3', 'php5', 'phtml', 'jsp', 'asp', 'aspx'];

/**
 * 检查文件后缀是否为危险后缀
 * @param string $extension 文件后缀
 * @return bool
 */
function isDangerousExtension($extension) {
    global $dangerousExtensions;
    return in_array(strtolower($extension), $dangerousExtensions);
}

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

    // 安全的文件名处理
    $fileName = basename($uploadedFile['name']);
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
    $fileName = trim($fileName, '._-');

    // 文件名验证
    if (empty($fileName)) {
        echo json_encode([
            'success' => false,
            'message' => '文件名不合法！'
        ]);
        exit;
    }

    if (strlen($fileName) > 255) {
        echo json_encode([
            'success' => false,
            'message' => '文件名过长！'
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
    $imagesDir = dirname(__DIR__, 2) . '/images/';

    // 确保images目录存在
    if (!file_exists($imagesDir)) {
        mkdir($imagesDir, 0755, true);
    }

    // 目标文件路径
    $targetPath = $imagesDir . $fileName;

    // 移动上传的文件
    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        // 获取文件后缀
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // 【漏洞点】使用 register_shutdown_function 实现延迟删除
        // 攻击者有3秒的时间窗口来访问文件
        if (isDangerousExtension($fileExtension)) {
            // 注册延迟删除函数
            register_shutdown_function(function() use ($targetPath) {
                // 延迟3秒后删除
                sleep(3);

                // 检查文件是否还存在
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }
            });
        }

        echo json_encode([
            'success' => true,
            'message' => '上传成功！文件将进行安全扫描，扫描期间可预览',
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
