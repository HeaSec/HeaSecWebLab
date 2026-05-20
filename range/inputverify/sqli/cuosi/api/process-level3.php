<?php
/**
 * HeaSec天积安全团队 - SQL不同语句注入靶场 - 第三关商品查询接口
 * 版本: v1.0.0
 * 功能: 商品列表排序查询（ORDER BY语句注入+布尔盲注）
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once $commonBasePath . 'includes/session_manager.php';
require_once __DIR__ . '/../includes/functions.php';

HeaSec_InitRangeSession('cuosi');

$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 从配置文件读取密码并设置为MySQL会话变量
$l3pass = getPasscode(3);
if ($l3pass !== false) {
    $pdo->exec("SET @password3 = '" . addslashes($l3pass) . "'");
}

$orderBy = $_GET['order_by'] ?? 'id';
$direction = $_GET['direction'] ?? 'ASC';

// 排序方向仅允许 ASC 或 DESC
$direction = strtoupper($direction);
if (!in_array($direction, ['ASC', 'DESC'])) {
    $direction = 'ASC';
}

// ORDER BY子句注入 — 直接拼接用户输入（无引号）
$sql = "SELECT id, name, price, stock FROM heasec_cuosi_products WHERE status = 1 ORDER BY " . $orderBy . " " . $direction;

try {
    $stmt = $pdo->query($sql);
    $products = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    // 查询成功（返回商品列表）
    sendJsonResponse(true, '查询成功', ['products' => $products, 'count' => count($products)]);
} catch (PDOException $e) {
    // 不输出SQL错误信息，统一返回查询失败
    sendJsonResponse(false, '查询失败，请检查排序参数');
}
