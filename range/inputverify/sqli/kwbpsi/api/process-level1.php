<?php
/**
 * HeaSec天积安全团队 - SQL关键字过滤靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 功能: 第一关商品查询处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的商品ID
$id = $_POST['id'] ?? '';

if ($id === '') {
    sendJsonResponse(false, '请输入商品ID');
}

// ===== 关键字移除过滤器（大小写敏感）=====
// 使用 str_replace 移除小写形式的SQL关键字（区分大小写）
$blocked_keywords = ['union', 'select', 'from', 'where',
    'insert', 'update', 'delete', 'drop', 'alter', 'create',
    'exec', 'declare', 'sleep', 'into', 'load_file'];
$filtered_id = str_replace($blocked_keywords, '', $id);

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 执行查询
$sql = "SELECT id, name, price, stock FROM heasec_kwbpsi_goods WHERE id = " . $filtered_id;

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
