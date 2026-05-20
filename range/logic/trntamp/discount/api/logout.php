<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 退出登录接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Discount Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

define('HEASEC_RANGE_ACCESS', true);

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';

HeaSec_InitRangeSession('discount');

require_once 'includes/functions.php';

$data = getRequestData();
$level = isset($data['level']) ? intval($data['level']) : 1;

// 清除指定关卡的会话数据
unset($_SESSION['discount_user_id_level' . $level]);
unset($_SESSION['discount_username_level' . $level]);
unset($_SESSION['discount_passcode_level' . $level]);

sendJsonResponse(true, '退出成功');
