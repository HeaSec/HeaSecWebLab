<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 第二关上传接口
 * 漏洞机制：临时目录+延迟验证 - 先存tmp目录，延迟验证后移动/删除
 * 版本: v1.1.0
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 条件竞争上传 Range v1.1.0');
header('Content-Type: application/json; charset=utf-8');

// 公共组件路径
$commonBasePath = '../../../../../../common/';

define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('racecondition');
HeaSec_ValidateSession();

// 定义允许的图片后缀（只有这些可以通过审核）
$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

/**
 * 检查文件后缀是否为允许的图片格式
 * @param string $extension 文件后缀
 * @return bool
 */
function isAllowedImageExtension($extension) {
    global $allowedImageExtensions;
    return in_array(strtolower($extension), $allowedImageExtensions);
}

/**
 * 生成随机文件名（保留原始后缀）
 * @param string $extension 文件后缀
 * @return string
 */
function generateRandomFileName($extension = 'jpg') {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $randomName = '';
    for ($i = 0; $i < 12; $i++) {
        $randomName .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $randomName . '.' . $extension;
}

// 处理上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadedFile = $_FILES['file'];

    // 调试信息
    $debugInfo['files'] = $_FILES;
    $debugInfo['files_tmp_name'] = $uploadedFile['tmp_name'];

    // 检查上传错误
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => '文件上传失败：错误码 ' . $uploadedFile['error']
        ]);
        exit;
    }

    // 获取原始文件名和后缀
    $originalFileName = basename($uploadedFile['name']);
    $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    // 文件大小限制（1MB）
    $maxSize = 1 * 1024 * 1024;
    if ($uploadedFile['size'] > $maxSize) {
        echo json_encode([
            'success' => false,
            'message' => '文件大小超过限制（最大1MB）！'
        ]);
        exit;
    }

    // 目录路径
    $imagesDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
    $tmpDir = $imagesDir . 'tmp' . DIRECTORY_SEPARATOR;

    // 确保目录存在
    if (!file_exists($imagesDir)) {
        mkdir($imagesDir, 0755, true);
    }
    if (!file_exists($tmpDir)) {
        mkdir($tmpDir, 0755, true);
    }

    // 【漏洞点】生成随机文件名，但保留原始后缀
    // 攻击者可以上传 shell.php，文件名变为 random123.php
    $randomFileName = generateRandomFileName($fileExtension);
    $tmpPath = $tmpDir . $randomFileName;

    // 移动上传的文件到临时目录
    $moveResult = move_uploaded_file($uploadedFile['tmp_name'], $tmpPath);

    if ($moveResult) {
        // 记录上传信息到会话（用于前端显示审核状态）
        $_SESSION['racecondition_level2_upload'] = [
            'tmp_filename' => $randomFileName,
            'original_name' => $originalFileName,
            'upload_time' => time(),
            'extension' => $fileExtension
        ];

        // 【漏洞点】使用后台进程实现30秒延迟验证
        // 攻击者有30秒的时间窗口来访问临时目录中的文件
        // 使用 exec() 在后台启动独立的 PHP 进程执行延迟验证，避免阻塞当前请求

        $phpBinary = 'D:\phpstudy_pro\Extensions\php\php7.3.4nts\php.exe';
        $verifyScript = __DIR__ . '/verify-delayed.php';

        // 【漏洞点】Windows 下使用后台进程执行延迟验证
        // 注意：这里故意不使用 escapeshellarg，让攻击者有机会通过条件竞争访问临时文件
        // 使用 pclose(popen()) 在后台启动独立进程，避免阻塞当前请求
        $command = sprintf(
            '"%s" "%s" "%s" "%s" "%s" "%s"',
            $phpBinary,
            $verifyScript,
            $tmpPath,
            $fileExtension,
            $imagesDir,
            $randomFileName
        );

        // Windows 下使用 start /B 在后台执行，不等待进程结束
        pclose(popen('start /B ' . $command, 'r'));

        // 【漏洞点】在响应中返回临时文件URL
        // 攻击者可以立即获知文件路径，在审核期间访问
        $tmpUrl = 'images/tmp/' . $randomFileName;

        $response = json_encode([
            'success' => true,
            'message' => '上传成功！文件正在审核中，预计30秒内完成...',
            'filename' => $randomFileName,
            'original_name' => $originalFileName,
            'tmp_url' => $tmpUrl,
            'review_time' => 30
        ]);

        // 输出响应
        echo $response;
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
