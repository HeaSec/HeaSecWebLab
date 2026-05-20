<?php
/**
 * HeaSec天积安全团队 - 文件相关XSS靶场 - 文件上传API
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件相关XSS上传API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 只允许POST请求'
    ]);
    exit;
}

// 检查文件是否上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 文件上传失败，请重试'
    ]);
    exit;
}

// 获取文件类型参数
$fileType = isset($_POST['type']) ? $_POST['type'] : '';

// 验证文件类型参数
if (!in_array($fileType, ['svg', 'pdf', 'image'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 无效的文件类型参数'
    ]);
    exit;
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileMime = $file['type'];

// 上传目录
$uploadDir = __DIR__ . '/../uploads/temp/';

// 确保上传目录存在
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/**
 * 验证SVG文件
 */
function validateSvg($file, $fileName, $fileSize, $fileMime) {
    // 文件大小限制 1MB
    $maxSize = 1 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => '文件大小超过限制（最大1MB）'];
    }

    // 验证扩展名
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($ext !== 'svg') {
        return ['success' => false, 'message' => '文件类型不支持，请上传SVG文件'];
    }

    // 验证MIME类型
    $allowedMimes = ['image/svg+xml', 'image/svg', 'application/svg+xml', 'text/svg+xml'];
    if (!in_array($fileMime, $allowedMimes)) {
        return ['success' => false, 'message' => 'MIME类型不支持，请上传有效的SVG文件'];
    }

    // 读取文件内容
    $content = file_get_contents($file);

    // SVG语法验证：检查是否包含<svg>标签
    if (stripos($content, '<svg') === false) {
        return ['success' => false, 'message' => '无效的SVG文件格式'];
    }

    // 关键过滤：检测并拦截<script>标签（大小写不敏感）
    if (preg_match('/<script[\s>]/i', $content)) {
        return ['success' => false, 'message' => 'SVG文件包含不安全的内容'];
    }

    return ['success' => true, 'content' => $content];
}

/**
 * 验证PDF文件
 */
function validatePdf($file, $fileName, $fileSize, $fileMime, $uploadDir) {
    // 文件大小限制 5MB
    $maxSize = 5 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => '文件大小超过限制（最大5MB）'];
    }

    // 验证扩展名
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return ['success' => false, 'message' => '文件类型不支持，请上传PDF文件'];
    }

    // 验证MIME类型
    $allowedMimes = ['application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf', 'text/x-pdf'];
    if (!in_array($fileMime, $allowedMimes)) {
        return ['success' => false, 'message' => 'MIME类型不支持，请上传有效的PDF文件'];
    }

    // 不重命名文件，保持原始文件名
    $newFileName = basename($fileName);
    $filePath = $uploadDir . $newFileName;

    // 移动文件到临时目录
    if (!move_uploaded_file($file, $filePath)) {
        return ['success' => false, 'message' => '文件保存失败'];
    }

    return [
        'success' => true,
        'file_path' => 'uploads/temp/' . $newFileName,
        'message' => '文件上传成功'
    ];
}

/**
 * 验证图片文件
 */
function validateImage($file, $fileName, $fileSize, $fileMime, $uploadDir) {
    // 文件大小限制 2MB
    $maxSize = 2 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => '文件大小超过限制（最大2MB）'];
    }

    // 验证扩展名
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['png', 'jpg', 'jpeg', 'gif'];
    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'message' => '文件类型不支持，请上传PNG、JPG或GIF图片'];
    }

    // 验证MIME类型
    $allowedMimes = ['image/png', 'image/jpeg', 'image/gif'];
    if (!in_array($fileMime, $allowedMimes)) {
        return ['success' => false, 'message' => 'MIME类型不支持，请上传有效的图片文件'];
    }

    // 不重命名文件，保持原始文件名
    $newFileName = basename($fileName);
    $filePath = $uploadDir . $newFileName;

    // 移动文件到临时目录
    if (!move_uploaded_file($file, $filePath)) {
        return ['success' => false, 'message' => '文件保存失败'];
    }

    return [
        'success' => true,
        'file_path' => 'uploads/temp/' . $newFileName,
        'message' => '文件上传成功'
    ];
}

// 根据文件类型进行验证
try {
    switch ($fileType) {
        case 'svg':
            $result = validateSvg($fileTmpName, $fileName, $fileSize, $fileMime);
            break;
        case 'pdf':
            $result = validatePdf($fileTmpName, $fileName, $fileSize, $fileMime, $uploadDir);
            break;
        case 'image':
            $result = validateImage($fileTmpName, $fileName, $fileSize, $fileMime, $uploadDir);
            break;
        default:
            $result = ['success' => false, 'message' => '未知的文件类型'];
    }

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'] ?? '文件上传成功',
            'file_path' => $result['file_path'] ?? '',
            'content' => $result['content'] ?? ''
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '[HeaSec] ' . $result['message']
        ]);
    }

} catch (Exception $e) {
    error_log('[HeaSec] 上传API错误: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 服务器内部错误'
    ]);
}
?>
