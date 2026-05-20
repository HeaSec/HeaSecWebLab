<?php
/**
 * HeaSec天积安全团队 - 会话安全靶场 - 第一关退出登录接口
 * 版本: v1.0.0
 */

header('X-HeavenlySecret: HeaSec Session Range v1.0.0');
header('Content-Type: application/json; charset=utf-8');

$commonBasePath = '../../../../../../common/';
require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once '../../includes/functions.php';

$level = 1;
initRangeSession($level);

// 步骤1：在当前会话中设置退出标记
$_SESSION['session_logout_marked_level1'] = true;

// 步骤2：写入并关闭当前会话（保存退出标记和用户数据）
session_write_close();

// 步骤3：重新打开旧会话，确保退出标记和用户数据都写入了
// （已经在上面的session_write_close中完成）

// 步骤4：生成全新会话ID
$newSessionId = generateRandomSessionId();

// 步骤5：设置新会话ID并启动新会话
session_id($newSessionId);
session_start();

// 新会话为空，无用户数据、无退出标记
// 浏览器通过Set-Cookie获得新会话ID

sendJsonResponse(true, '您已安全退出登录');
