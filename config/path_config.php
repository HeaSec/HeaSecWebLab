<?php
/**
 * HeaSec 统一路径配置
 * 自动检测并生成项目所需的所有路径
 *
 * @team 天积安全 (HeavenlySecret)
 * @version 1.0.0
 */

// 获取基础URL
// 获取基础URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : __FILE__;
$basePath = rtrim(dirname($scriptName), '/');
$baseUrl = $protocol . '://' . $host . $basePath . '/';

// 只定义基础路径常量
define('HEASEC_BASE_URL', $baseUrl);

/**
 * 获取URL的快捷函数
 * @param string $type URL类型
 * @return string
 */
function heasec_get_url($type = 'base')
{
    switch ($type) {
        case 'api':
            return HEASEC_BASE_URL . 'api/heasec/';
        case 'assets':
            return HEASEC_BASE_URL . 'assets/';
        case 'css':
            return HEASEC_BASE_URL . 'css/';
        case 'js':
            return HEASEC_BASE_URL . 'js/';
        case 'admin':
            return HEASEC_BASE_URL . 'admin/';
        case 'range':
            return HEASEC_BASE_URL . 'range/';
        default:
            return HEASEC_BASE_URL;
    }
}
?>