<?php
/**
 * HeaSec天积安全团队 - SOAP与XML靶场重置接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once __DIR__ . '/../includes/functions.php';

$basePath = dirname(__DIR__);

// 删除通关密码文件
for ($i = 1; $i <= 3; $i++) {
    $secretFile = $basePath . '/config/secret_level' . $i . '.php';
    if (file_exists($secretFile)) {
        unlink($secretFile);
    }
}

// 重置第一关用户数据（清空为空的<users>根元素）
$level1File = $basePath . '/data/level1_users.xml';
file_put_contents($level1File, '<?xml version="1.0" encoding="UTF-8"?>' . "\n<users>\n</users>\n");

// 重置第二关用户数据（重新生成admin账户 + 随机密码）
$level2File = $basePath . '/data/level2_users.xml';
initLevel2Users($level2File);

// 第三关商品数据不变（预置数据）

// 删除SSRF token文件
$ssrfTokenFile = $basePath . '/data/ssrf_token.txt';
if (file_exists($ssrfTokenFile)) {
    unlink($ssrfTokenFile);
}

// 清除会话中的用户登录状态和SSRF token
unset($_SESSION['soapxml_level1_user']);
unset($_SESSION['soapxml_level2_user']);
unset($_SESSION['soapxml_level3_ssrf_token']);

sendJsonResponse(true, '重置成功');
