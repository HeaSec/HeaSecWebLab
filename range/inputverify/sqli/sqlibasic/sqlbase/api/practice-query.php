<?php
/**
 * HeaSec天积安全团队 - SQL注入练习API
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：处理第四区域（SQL注入尝试区域）的查询请求
 * 支持3个注入场景：数字型、字符型单引号、字符型双引号
 * 注意：此文件故意使用不安全的SQL拼接，仅用于教学演示
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec SQL注入练习API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 定义常量允许访问公共组件
define('HEASEC_RANGE_ACCESS', true);

// 引入公共数据库组件
$commonBasePath = '../../../../../common/';
require_once $commonBasePath . 'includes/database.php';

// 初始化响应
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'sql' => '',
    'scenario' => '',
    'scenario_name' => '',
    'columns' => []
];

// 场景配置
$scenarios = [
    'numeric' => [
        'name' => '数字型注入',
        'description' => '注入点不需要引号闭合',
        'table' => 'heasec_sqlbase_products',
        'columns' => ['id', 'name', 'price', 'description'],
        'param_name' => 'id',
        'sql_template' => "SELECT id, name, price, description FROM heasec_sqlbase_products WHERE id = {input}"
    ],
    'single_quote' => [
        'name' => '字符型注入（单引号）',
        'description' => '注入点需要单引号闭合',
        'table' => 'heasec_sqlbase_products',
        'columns' => ['id', 'name', 'price', 'description'],
        'param_name' => 'name',
        'sql_template' => "SELECT id, name, price, description FROM heasec_sqlbase_products WHERE name = '{input} '"
    ],
    'double_quote' => [
        'name' => '字符型注入（双引号）',
        'description' => '注入点需要双引号闭合',
        'table' => 'heasec_sqlbase_products',
        'columns' => ['id', 'name', 'price', 'description'],
        'param_name' => 'name',
        'sql_template' => 'SELECT id, name, price, description FROM heasec_sqlbase_products WHERE name = "{input} "'
    ]
];

try {
    // 获取请求参数
    $scenario = isset($_GET['scenario']) ? $_GET['scenario'] : 'numeric';
    $input = isset($_GET['input']) ? $_GET['input'] : '';

    // 验证场景
    if (!array_key_exists($scenario, $scenarios)) {
        $response['message'] = '无效的场景类型';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 获取场景配置
    $config = $scenarios[$scenario];
    $response['scenario'] = $scenario;
    $response['scenario_name'] = $config['name'];
    $response['columns'] = $config['columns'];

    // 验证输入
    if (empty($input) && $input !== '0') {
        $response['message'] = '请输入测试内容';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 获取数据库连接
    $pdo = heasec_db('heasec_sqlbase');

    // 构建SQL语句（故意不安全，用于演示SQL注入）
    $sql = str_replace('{input}', $input, $config['sql_template']);
    $response['sql'] = $sql;

    // 生成调试信息
    $response['debug_info'] = "📋 SQL模板:\n" . $config['sql_template'] . "\n\n" .
                              "📝 用户输入: " . $input . "\n\n" .
                              "⚡ 拼接后的SQL语句:\n" . $sql;

    // 执行查询
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 处理结果
    if (count($results) > 0) {
        // 对输出进行HTML转义
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
        $response['message'] = '查询执行成功，但未找到匹配数据';
        $response['data'] = [];
    }

} catch (PDOException $e) {
    // SQL错误，显示错误信息（对于SQL注入教学很重要）
    $response['success'] = false;
    $response['message'] = 'SQL语法错误';
    $response['error'] = $e->getMessage();
    $response['sql'] = isset($sql) ? $sql : '';
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = '系统错误';
    $response['error'] = $e->getMessage();
}

// 返回JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
