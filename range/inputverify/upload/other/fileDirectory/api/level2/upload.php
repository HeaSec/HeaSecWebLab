<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 第二关上传接口
 * 漏洞机制：Zip Slip - 解压时未过滤压缩包内文件名中的路径穿越字符
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

    // 检查文件后缀
    $fileName = $uploadedFile['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExt !== 'zip') {
        echo json_encode([
            'success' => false,
            'message' => '仅支持ZIP格式的压缩包！'
        ]);
        exit;
    }

    // images目录路径
    $imagesDir = dirname(__DIR__, 2) . '/exec/images/';

    // 确保images目录存在
    if (!file_exists($imagesDir)) {
        mkdir($imagesDir, 0755, true);
    }

    // 临时保存上传的zip文件
    $tempZip = $imagesDir . 'temp_' . time() . '.zip';
    if (!move_uploaded_file($uploadedFile['tmp_name'], $tempZip)) {
        echo json_encode([
            'success' => false,
            'message' => '文件上传失败，请检查目录权限！'
        ]);
        exit;
    }

    // 使用ZipArchive解压文件
    $zip = new ZipArchive;
    if ($zip->open($tempZip) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // 安全检查：禁止解压 .htaccess 文件（大小写不敏感）
            if (strtolower(basename($filename)) === '.htaccess') {
                $zip->close();
                @unlink($tempZip);
                echo json_encode([
                    'success' => false,
                    'message' => '检测到非法文件！'
                ]);
                exit;
            }

            // 【漏洞点】未过滤压缩包内文件名中的路径穿越字符
            // 攻击者可以在压缩包内文件名中使用 ../ 进行路径穿越
            // 注意：ZipArchive::extractTo() 在某些环境下会自动过滤 ../
            // 这里使用手动解压方式来保留路径穿越字符

            // 跳过目录条目（以/结尾）
            if (substr($filename, -1) === '/') {
                continue;
            }

            // 获取文件内容
            $fileContent = $zip->getFromName($filename);
            if ($fileContent !== false) {
                // 构建目标路径（保留路径穿越字符）
                $targetPath = $imagesDir . $filename;

                // 确保目标目录存在
                $targetDir = dirname($targetPath);
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                // 写入文件
                file_put_contents($targetPath, $fileContent);
            }
        }
        $zip->close();

        // 删除临时zip文件
        @unlink($tempZip);

        echo json_encode([
            'success' => true,
            'message' => '解压成功！'
        ]);
    } else {
        // 删除临时zip文件
        @unlink($tempZip);

        echo json_encode([
            'success' => false,
            'message' => '解压失败，请检查压缩包格式！'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '无效的请求'
    ]);
}
?>
