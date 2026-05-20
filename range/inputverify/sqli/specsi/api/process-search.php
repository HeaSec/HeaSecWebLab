<?php
/**
 * HeaSec天积安全团队 - SQL特殊注入场景靶场 - 第二关商品搜索接口
 * 版本: v1.0.0
 * 功能: 商品信息搜索处理（宽字节注入漏洞点）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/session_manager.php';
require_once __DIR__ . '/../includes/functions.php';

HeaSec_InitRangeSession('specsi');

// 检查登录状态
$user_id = $_SESSION['specsi_user_id'] ?? 0;
if (!$user_id || ($_SESSION['specsi_level'] ?? 0) !== 2) {
    sendJsonResponse(false, '请先登录');
}

// 接收用户提交的商品名称
$keyword = $_POST['keyword'] ?? '';

if ($keyword === '') {
    sendJsonResponse(false, '请输入商品名称');
}

// addslashes() 转义特殊字符
$keyword = addslashes($keyword);

// 获取数据库连接并设置为GBK字符集
$pdo = HeaSec_Database::getConnection('heasec_sqli');
$pdo->exec("SET NAMES gbk");

// 将addslashes后的值拼接SQL
$sql = "SELECT id, name, price FROM heasec_specsi_products WHERE name = '" . $keyword . "'";

try {
    $stmt = $pdo->query($sql);
    $results = [];
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
    }
    sendJsonResponse(true, '查询完成', ['results' => $results]);
} catch (PDOException $e) {
    sendJsonResponse(false, '查询失败，请检查输入参数');
}
