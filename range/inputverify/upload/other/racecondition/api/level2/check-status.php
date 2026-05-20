<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 第二关审核状态检查接口
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

// 定义允许的图片后缀
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

// 处理请求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 从会话获取上传信息
    if (isset($_SESSION['racecondition_level2_upload'])) {
        $uploadInfo = $_SESSION['racecondition_level2_upload'];
        $uploadTime = $uploadInfo['upload_time'];
        $currentTime = time();
        $elapsedTime = $currentTime - $uploadTime;
        $remainingTime = max(0, 30 - $elapsedTime);

        // 检查文件状态
        $tmpDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
        $imagesDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        $tmpPath = $tmpDir . $uploadInfo['tmp_filename'];
        $finalPath = $imagesDir . $uploadInfo['tmp_filename'];

        $status = 'pending'; // 默认：审核中
        $avatarUrl = null;

        if (file_exists($finalPath)) {
            // 文件已在正式目录（审核通过）
            $status = 'approved';
            $avatarUrl = 'images/' . $uploadInfo['tmp_filename'];
        } elseif (!file_exists($tmpPath) && $elapsedTime >= 30) {
            // 临时文件已被删除且已过审核时间（审核拒绝）
            $status = 'rejected';
        } elseif (file_exists($tmpPath)) {
            // 文件还在临时目录
            if ($elapsedTime >= 30) {
                // 【关键修复】已超时，在状态检查时执行延迟验证逻辑
                // 这确保即使后台进程失败，验证也能正常完成
                if (isAllowedImageExtension($uploadInfo['extension'])) {
                    // 符合图片格式要求：移动到正式目录
                    if (rename($tmpPath, $finalPath)) {
                        $status = 'approved';
                        $avatarUrl = 'images/' . $uploadInfo['tmp_filename'];
                    }
                } else {
                    // 不符合格式要求：删除临时文件
                    @unlink($tmpPath);
                    $status = 'rejected';
                }
            } else {
                // 未超时，仍在审核中
                $status = 'pending';
            }
        }

        echo json_encode([
            'success' => true,
            'status' => $status,
            'tmp_filename' => $uploadInfo['tmp_filename'],
            'original_name' => $uploadInfo['original_name'],
            'extension' => $uploadInfo['extension'],
            'elapsed_time' => $elapsedTime,
            'remaining_time' => $remainingTime,
            'avatar_url' => $avatarUrl,
            'tmp_url' => 'images/tmp/' . $uploadInfo['tmp_filename']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'status' => 'none',
            'message' => '暂无上传记录'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '无效的请求'
    ]);
}
?>
