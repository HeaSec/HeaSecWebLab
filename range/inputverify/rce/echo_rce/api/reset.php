<?php
/**
 * HeaSec天积安全团队 - 回显型命令注入靶场 - 重置接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('echo_rce');

require_once '../includes/functions.php';
initEchoRceSession();

$_SESSION['echo_rce_level1_passed'] = false;
$_SESSION['echo_rce_level2_passed'] = false;
$_SESSION['echo_rce_level3_passed'] = false;

sendJsonResponse(true, '重置成功，所有关卡已恢复初始状态');
