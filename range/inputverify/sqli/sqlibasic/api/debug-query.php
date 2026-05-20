<?php
/**
 * HeaSec天积安全团队 - SQL语句调试API
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：处理第一区域（SQL语句调试功能）的查询请求
 * 注意：此文件故意使用不安全的SQL拼接，仅用于教学演示
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec SQL调试API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义常量允许访问公共组件
define('HEASEC_RANGE_ACCESS', true);

// 引入公共数据库组件
$commonBasePath = '../../../../common/';
require_once $commonBasePath . 'includes/database.php';

// 初始化响应
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'sql' => '',
    'debug_info' => ''
];

try {
    // 获取请求参数
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

    // 验证输入
    if (empty($keyword)) {
        $response['message'] = '请填写搜索内容';
        $response['debug_info'] = '用户未输入任何内容';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 获取数据库连接
    $pdo = heasec_db('heasec_sqlbase');

    // 构建SQL语句（故意不安全，用于演示SQL注入）
    // 这里模拟一个简单的用户搜索功能（等号查询，便于UNION注入演示）
    $sql = "SELECT id, username, password, email FROM heasec_sqlbase_users WHERE username = '" . $keyword . "' OR email = '" . $keyword . "'  ";

    // 记录实际执行的SQL语句（用于调试显示）
    $response['sql'] = $sql;
    $response['debug_info'] = "用户，你好！本次实际执行的SQL查询语句为：\n" . $sql;

    // 执行查询
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 处理结果
    if (count($results) > 0) {
        // 对输出进行HTML转义，防止XSS（虽然是SQL注入靶场，但仍需防止XSS）
        foreach ($results as &$row) {
            foreach ($row as $key => $value) {
                $row[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        $response['success'] = true;
        $response['message'] = '查询成功，共找到 ' . count($results) . ' 条记录';
        $response['data'] = $results;
    } else {
        $response['success'] = true;
        $response['message'] = '未找到匹配的数据';
        $response['data'] = [];
    }

} catch (PDOException $e) {
    // 记录错误信息
    $response['success'] = false;
    $response['message'] = '查询执行失败';
    $response['error'] = $e->getMessage();
    $response['debug_info'] = "用户，你好！本次执行的SQL查询语句为：\n" . $sql . "\n\n错误信息：\n" . $e->getMessage();
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = '系统错误';
    $response['error'] = $e->getMessage();
}

// 返回JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
