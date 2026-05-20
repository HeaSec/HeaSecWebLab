<?php
/**
 * HeaSec天积安全团队 - 条件竞争上传靶场 - 第二关延迟验证脚本
 * 版本: v1.1.0
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 此脚本由 upload.php 通过 exec() 后台调用
 * 用于异步执行30秒延迟验证，避免阻塞主请求
 */

// 设置运行环境
define('HEASEC_RANGE_ACCESS', true);

// 定义允许的图片后缀
$allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

// 获取命令行参数
if ($argc < 5) {
    exit(1);
}

$tmpPath = $argv[1];          // 临时文件完整路径
$fileExtension = $argv[2];    // 文件扩展名
$imagesDir = $argv[3];        // 正式目录路径
$randomFileName = $argv[4];   // 随机文件名

// 延迟30秒后执行验证（攻击者有时间窗口访问临时文件）
sleep(30);

// 检查临时文件是否还存在
if (file_exists($tmpPath)) {
    if (in_array(strtolower($fileExtension), $allowedImageExtensions)) {
        // 符合格式要求：移动到 images 目录（保持随机文件名）
        $newPath = $imagesDir . $randomFileName;
        rename($tmpPath, $newPath);
    } else {
        // 不符合格式要求：直接删除
        @unlink($tmpPath);
    }
}
?>
