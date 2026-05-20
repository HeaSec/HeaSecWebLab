<?php
/**
 * HeaSec 天积安全团队 - 系统引导文件
 * 自动适应不同操作系统和部署环境的统一初始化系统
 *
 * @package HeaSec_Range_Core
 * @version 2.0.0
 * @author 天积安全 (HeavenlySecret)
 * @copyright 天积安全 (HeaSec) 2026
 */

// 确保核心配置文件存在
if (!file_exists(__DIR__ . '/config/config.php')) {
    die('[HeaSec] 错误：核心配置文件 config/config.php 不存在！');
}

// 引入团队核心配置
require_once __DIR__ . '/config/config.php';

// 引入统一路径配置
require_once __DIR__ . '/config/path_config.php';

// 根据系统环境设置错误处理
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows特定的错误处理设置
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Linux/Unix特定的错误处理设置
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

    // 确保日志目录存在
    $logsDir = __DIR__ . '/logs';
    if (!file_exists($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
}

// 设置时区（如果尚未设置）
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Shanghai');
}

// 根据Web服务器类型设置特定的配置
$serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? strtolower($_SERVER['SERVER_SOFTWARE']) : '';
if (strpos($serverSoftware, 'apache') !== false) {
    // Apache特定设置
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
    }
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
} elseif (strpos($serverSoftware, 'nginx') !== false) {
    // Nginx特定设置
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
}

// 设置团队响应头
header('X-Powered-By: HeavenlySecret/HeaSec ' . HEASEC_VERSION . ' (Path Config)');
header('X-Team-Name: ' . HEASEC_TEAM_ABBR);
header('X-System-OS: ' . PHP_OS);
header('X-Server-Software: ' . (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'));

// 设置默认字符编码
mb_internal_encoding('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

// 定义基础系统常量
define('HEASEC_BASE_PATH', __DIR__ . '/');

// 记录系统初始化完成日志
HeaSec_log('system_bootstrap_complete', [
    'base_url' => HEASEC_BASE_URL,
    'base_path' => HEASEC_BASE_PATH,
    'system_info' => [
        'os' => PHP_OS,
        'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'
    ]
]);

// 系统初始化完成
// 从现在开始，可以使用所有HeaSec增强版功能
