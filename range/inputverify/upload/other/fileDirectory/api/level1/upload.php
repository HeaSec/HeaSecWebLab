<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 第一关上传接口
 * 漏洞机制：重命名功能路径穿越 - 重命名时可使用../穿越到上级目录
 * 版本: v1.2.0
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件目录执行权限绕过 Range v1.2.0');
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

    // 获取文件名：优先使用自定义文件名，否则使用原始文件名
    // 漏洞点：直接使用用户输入的自定义文件名，未过滤路径穿越字符（../）
    // 攻击者可使用 ../shell.php 将文件上传到 exec/ 目录（该目录允许PHP执行）
    $fileName = isset($_POST['customFileName']) && !empty($_POST['customFileName'])
        ? $_POST['customFileName']
        : basename($uploadedFile['name']);

    // 安全检查：禁止上传 .htaccess 文件（大小写不敏感）
    if (strtolower(basename($fileName)) === '.htaccess') {
        echo json_encode([
            'success' => false,
            'message' => '非法文件名！'
        ]);
        exit;
    }

    // 构建目标路径（漏洞：未过滤 ../）
    $targetPath = $imagesDir . $fileName;

    // 检查目标目录是否存在
    $targetDir = dirname($targetPath);
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 移动上传的文件
    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        echo json_encode([
            'success' => true,
            'message' => '上传成功！',
            'filename' => $fileName,
            'originalName' => $uploadedFile['name']
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
