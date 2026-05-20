<?php
/**
 * HeaSec天积安全团队 - 获取短信发送日志列表接口
 * API: Get SMS Log List
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('HeavenlySecret: HeaSec-API-v1.0.0');

// 定义访问常量并引入数据库组件
define('HEASEC_RANGE_ACCESS', true);
require_once dirname(dirname(dirname(__DIR__))) . '/includes/HeaSec_Database.php';

// 初始化响应数组
$response = array(
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => time()
);

try {
    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 获取分页和筛选参数
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['page_size']) ? max(1, min(100, intval($_GET['page_size']))) : 20;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
    $sender = isset($_GET['sender']) ? trim($_GET['sender']) : '';

    $offset = ($page - 1) * $pageSize;

    // 构建查询条件
    $whereConditions = array();
    $params = array();

    if ($status !== '') {
        $whereConditions[] = 'send_status = ?';
        $params[] = $status;
    }

    if ($phone !== '') {
        $whereConditions[] = 'phone_number LIKE ?';
        $params[] = '%' . $phone . '%';
    }

    if ($sender !== '') {
        $whereConditions[] = 'sender LIKE ?';
        $params[] = '%' . $sender . '%';
    }

    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }

    // 查询总数
    $sql = "SELECT COUNT(*) as total FROM heasec_sms_log $whereClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 查询日志列表
    $sql = "SELECT id, phone_number, sender, message_content, send_status,
                   detail_info, ip_address, created_at
            FROM heasec_sms_log
            $whereClause
            ORDER BY id DESC
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $params[] = $pageSize;
    $params[] = $offset;
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 计算总页数
    $totalPages = ceil($total / $pageSize);

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = '获取日志列表成功';
    $response['data'] = array(
        'logs' => $logs,
        'total' => $total,
        'page' => $page,
        'page_size' => $pageSize,
        'total_pages' => $totalPages
    );

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
