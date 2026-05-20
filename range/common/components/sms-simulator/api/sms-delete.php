<?php
/**
 * HeaSec天积安全团队 - 删除短信接口
 * API: Delete SMS
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
    // 获取POST数据
    $postData = file_get_contents('php://input');
    $requestData = json_decode($postData, true);

    // 如果JSON解析失败，尝试从$_POST获取
    if (empty($requestData)) {
        $requestData = $_POST;
    }

    // 获取参数
    $ids = isset($requestData['ids']) ? $requestData['ids'] : array();

    // 基础校验：检查必填参数
    if (empty($ids) || !is_array($ids)) {
        throw new Exception('短信ID列表不能为空');
    }

    // 过滤并验证ID
    $validIds = array();
    foreach ($ids as $id) {
        $id = intval($id);
        if ($id > 0) {
            $validIds[] = $id;
        }
    }

    if (empty($validIds)) {
        throw new Exception('没有有效的短信ID');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 构建IN查询
    $placeholders = str_repeat('?,', count($validIds) - 1) . '?';
    $sql = "DELETE FROM heasec_sms_message WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($validIds);
    $deletedCount = $stmt->rowCount();

    // 返回成功响应
    $response['success'] = true;
    $response['message'] = '删除短信成功';
    $response['data'] = array(
        'deleted_count' => $deletedCount
    );

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
