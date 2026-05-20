<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第三关退出登录接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once '../../includes/functions.php';

$level = 3;
initRangeSession($level);

// 清除会话数据
unset($_SESSION['session_user_id_level3']);
unset($_SESSION['session_username_level3']);
unset($_SESSION['session_realname_level3']);
unset($_SESSION['session_role_level3']);
unset($_SESSION['session_logged_in_level3']);

// 标准退出，重新生成会话ID
session_regenerate_id(true);

sendJsonResponse(true, '您已安全退出登录');
