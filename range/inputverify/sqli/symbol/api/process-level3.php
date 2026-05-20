<?php
/**
 * HeaSec天积安全团队 - SQL特殊字符过滤靶场 - 第三关处理接口
 * 版本: v1.0.0
 * 功能: 第三关告警查询处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的告警ID
$id = $_POST['id'] ?? '';

if ($id === '') {
    sendJsonResponse(false, '请输入告警ID');
}

// 引号过滤器：检测单引号和双引号
if (strpos($id, "'") !== false || strpos($id, '"') !== false) {
    sendJsonResponse(false, '输入包含被过滤的字符（单引号或双引号）');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 执行查询
$sql = "SELECT id, alert_name, severity FROM heasec_symbol_alerts WHERE id = " . $id;

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
