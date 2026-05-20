<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场第二关退出登录接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once dirname(__DIR__) . '/../includes/functions.php';

unset($_SESSION['soapxml_level2_user']);
session_write_close();

sendJsonResponse(true, '退出成功');
