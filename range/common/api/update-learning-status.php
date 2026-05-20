<?php
/**
 * HeaSec天积安全团队 - 学习状态更新API接口
 * 版本: v1.0.0
 * 创建日期: 2025-11-15
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 用于自动更新靶场学习状态的API接口
 */

// 设置响应头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('HeavenlySecret: HeaSec-API-v1.0.0');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入必要的类文件
require_once __DIR__ . '/../includes/database.php';

// 初始化响应数组
$response = array(
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => time()
);

try {
    // 验证请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只支持POST请求方法');
    }

    // 获取请求参数
    $postData = json_decode(file_get_contents('php://input'), true);
    $rangeCode = $postData['code'] ?? '';
    $status = $postData['status'] ?? '已掌握'; // 已掌握, 学习中, 待学习

    // 验证必要参数
    if (empty($rangeCode)) {
        throw new Exception('缺少必要参数：code（靶场代码）');
    }

    // 根据靶场代码查找对应的链接记录
    $sql = "SELECT id, learning_status FROM links WHERE code = ? LIMIT 1";
    $linkInfo = heasec_fetch_one($sql, array($rangeCode), 'heasec_cms');

    if (!$linkInfo) {
        throw new Exception('未找到对应的靶场记录：' . $rangeCode);
    }

    $linkId = $linkInfo['id'];
    $currentStatus = $linkInfo['learning_status'];

    // 当目标状态为"学习中"时，只在当前状态为"待学习"时才更新
    if ($status === '学习中' && $currentStatus !== '待学习') {
        $response['success'] = true;
        $response['message'] = '学习状态无需更新（当前状态不是"待学习"）';
        $response['data'] = array(
            'link_id' => $linkId,
            'range_code' => $rangeCode,
            'current_status' => $currentStatus,
            'target_status' => $status,
            'skipped' => true
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 更新学习状态
    $updateSql = "UPDATE links SET learning_status = ?, updated_at = NOW() WHERE id = ?";
    $result = heasec_execute($updateSql, array($status, $linkId), 'heasec_cms');

    if ($result) {
        $affectedRows = $result->rowCount();

        if ($affectedRows > 0) {
            $response['success'] = true;
            $response['message'] = '学习状态更新成功';
            $response['data'] = array(
                'link_id' => $linkId,
                'range_code' => $rangeCode,
                'status' => $status,
                'affected_rows' => $affectedRows
            );

            // 记录日志
            error_log('[HeaSec] 学习状态更新成功: ' . $rangeCode . ' -> ' . $status);
        } else {
            $response['message'] = '学习状态未发生变化';
            $response['success'] = true; // 仍然是成功的，只是状态没变
        }
    } else {
        throw new Exception('数据库更新失败');
    }

} catch (Exception $e) {
    // 错误处理
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
    $response['data'] = null;

    // 记录错误日志
    error_log('[HeaSec Update Status Error] ' . $e->getMessage());
}

// 返回JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>