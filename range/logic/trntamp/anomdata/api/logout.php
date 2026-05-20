<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 退出登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 异常数据 Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共组件
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('anomdata');

// 清除所有关卡的登录状态
for ($level = 1; $level <= 3; $level++) {
    unset($_SESSION['anomdata_user_id_level' . $level]);
    unset($_SESSION['anomdata_username_level' . $level]);
}

// 返回成功响应
echo json_encode([
    'success' => true,
    'message' => '退出成功'
]);
