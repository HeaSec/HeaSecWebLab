<?php
/**
 * HeaSec天积安全团队 - 目录浏览靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-03-29
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 包含文件生成、清理和随机文件名等辅助函数
 */

// 防止直接访问
if (!defined('HEASEC_RANGE_ACCESS')) {
    http_response_code(403);
    exit('禁止直接访问此文件');
}

/**
 * 生成随机文件名
 *
 * @param int $minLen 最小长度，默认8
 * @param int $maxLen 最大长度，默认12
 * @return string 随机字母数字组合的文件名
 */
function generateRandomFileName($minLen = 8, $maxLen = 12)
{
    $length = mt_rand($minLen, $maxLen);
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $name = '';
    for ($i = 0; $i < $length; $i++) {
        $name .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $name;
}

/**
 * 生成随机txt文件到backup、tmp、log三个目录
 * 每个目录生成3个随机命名的txt文件（共9个）
 * 其中随机一个文件包含秘密字符串，其余为干扰内容
 *
 * 机制说明：
 * - 每个会话首次访问时，先清理旧文件再生成新文件
 * - 同一会话内重复访问不重新生成（通过会话标记控制）
 * - 重置功能会清除会话标记，触发下次访问时重新生成
 *
 * @param string $secret 秘密字符串
 * @return void
 */
function generateRandomFiles($secret)
{
    // 当前会话已生成过文件，跳过
    if (isset($_SESSION['dirlist_files_generated']) && $_SESSION['dirlist_files_generated'] === true) {
        return;
    }

    // 新会话：先清理其他会话遗留的旧文件
    cleanupFiles();

    $directories = ['backup', 'tmp', 'log'];

    // 确保目录存在，不存在则创建
    foreach ($directories as $dir) {
        $dirPath = __DIR__ . '/../' . $dir;
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
    }
    $files = [];

    // 每个目录生成3个随机文件
    foreach ($directories as $dir) {
        for ($i = 0; $i < 3; $i++) {
            $fileName = generateRandomFileName(8, 12) . '.txt';
            $files[] = [
                'dir' => $dir,
                'name' => $fileName,
                'path' => __DIR__ . '/../' . $dir . '/' . $fileName
            ];
        }
    }

    // 随机选择一个文件写入秘密
    $secretIndex = array_rand($files);
    $allSuccess = true;
    foreach ($files as $index => $file) {
        $content = ($index === $secretIndex) ? $secret : '不在这哦，再找找其他文件吧';
        $result = file_put_contents($file['path'], $content);
        if ($result === false) {
            $allSuccess = false;
        }
    }

    // 所有文件写入成功才标记已生成
    if ($allSuccess) {
        $_SESSION['dirlist_files_generated'] = true;
    }
}

/**
 * 清理已生成的文件
 * 清理backup、tmp、log目录下所有文件（保留.htaccess）
 *
 * @return void
 */
function cleanupFiles()
{
    $directories = ['backup', 'tmp', 'log'];
    foreach ($directories as $dir) {
        $dirPath = __DIR__ . '/../' . $dir;
        if (is_dir($dirPath)) {
            $items = scandir($dirPath);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item === '.htaccess') {
                    continue;
                }
                $filePath = $dirPath . '/' . $item;
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }
}
