<?php
/**
 * HeaSec天积安全团队 - 文件目录执行权限绕过靶场 - 第二关通关验证接口
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

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);
$userSecret = isset($input['secret']) ? trim($input['secret']) : '';

// 获取正确的通关密码
$correctSecret = isset($_SESSION['filedirectory_level2_secret']) ? $_SESSION['filedirectory_level2_secret'] : '';

// 验证密码
if (empty($userSecret)) {
    echo json_encode([
        'success' => false,
        'passed' => false,
        'message' => '请输入通关密码'
    ]);
    exit;
}

if ($userSecret === $correctSecret) {
    // 设置通关状态
    $_SESSION['filedirectory_level2_passed'] = true;

    echo json_encode([
        'success' => true,
        'passed' => true,
        'message' => '验证成功！'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'passed' => false,
        'message' => '验证失败，这不是正确的通关密码'
    ]);
}
?>
