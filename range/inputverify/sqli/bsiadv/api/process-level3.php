<?php
/**
 * HeaSec天积安全团队 - SQL盲注进阶靶场 - 第三关处理接口
 * 版本: v1.0.0
 * 功能: API令牌校验处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的令牌ID
$tokenId = $_POST['token_id'] ?? '';

if ($tokenId === '') {
    sendJsonResponse(false, '请输入令牌ID');
}

// WAF过滤：拦截判断语句关键字
if (!waf_level3($tokenId)) {
    sendJsonResponse(false, '请求被WAF拦截：检测到判断语句');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 字符型SQL查询 — 直接拼接用户输入
$sql = "SELECT id, token_name, status FROM heasec_bsiadv_tokens WHERE token_id = '" . $tokenId . "'";

try {
    $stmt = $pdo->query($sql);
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($row) {
        sendJsonResponse(true, '令牌校验完成：令牌有效', ['valid' => true]);
    } else {
        sendJsonResponse(true, '令牌校验完成：令牌无效', ['valid' => false]);
    }
} catch (PDOException $e) {
    // 错误也返回"令牌无效"（不展示SQL错误）
    sendJsonResponse(true, '令牌校验完成：令牌无效', ['valid' => false]);
}
