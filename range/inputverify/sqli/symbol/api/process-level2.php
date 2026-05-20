<?php
/**
 * HeaSec天积安全团队 - SQL特殊字符过滤靶场 - 第二关处理接口
 * 版本: v1.0.0
 * 功能: 第二关员工查询处理接口
 * 团队: 天积安全 (HeavenlySecret)
 */

$commonBasePath = '../../../../common/';

require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/functions.php';

// 接收用户提交的员工姓名
$name = $_POST['name'] ?? '';

if ($name === '') {
    sendJsonResponse(false, '请输入员工姓名');
}

// 注释符过滤器：检测SQL注释符号
if (preg_match('/--|#|\/\*|\*\//i', $name)) {
    sendJsonResponse(false, '输入包含被过滤的字符（注释符 --、#、/* */）');
}

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_sqli');

// 执行查询
$sql = "SELECT id, name, department FROM heasec_symbol_employees WHERE name = '" . $name . "'";

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
