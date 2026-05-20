<?php
/**
 * HeaSec 天积安全团队 - 前台API配置文件
 * 使用增强版路径管理系统
 *
 * @package HeaSec_API
 * @version 2.0.0
 * @author 天积安全 (HeavenlySecret)
 * @copyright 天积安全 (HeaSec) 2026
 */

// 引入HeaSec系统引导文件
require_once __DIR__ . '/../bootstrap.php';

// 创建数据库连接（使用HeaSec统一连接方式）
function getConnection() {
    return HeaSec_getConnection();
}

// 统一返回格式（使用HeaSec统一返回格式）
function returnResponse($success, $message, $data = null) {
    return HeaSec_returnResponse($success, $message, $data);
}

// 错误处理（使用HeaSec统一错误处理）
function handleError($message) {
    return HeaSec_handleError($message);
}
?>