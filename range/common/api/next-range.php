<?php
/**
 * HeaSec天积安全团队 - 下一靶场API接口
 * 版本: v1.0.0
 * 创建日期: 2025-11-15
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 提供下一靶场信息查询的RESTful API接口
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
require_once __DIR__ . '/../classes/HeaSec_NextRangeDetector.php';

// 初始化响应数组
$response = array(
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => time()
);

try {
    // 验证请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只支持GET和POST请求方法');
    }

    // 获取请求参数
    $rangeCode = '';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $rangeCode = $_GET['code'] ?? '';
    } else {
        $postData = json_decode(file_get_contents('php://input'), true);
        $rangeCode = $postData['code'] ?? '';
    }

    // 验证参数
    if (empty($rangeCode)) {
        throw new Exception('缺少必要参数：code（靶场代码）');
    }

    // 初始化检测器
    $detector = new HeaSec_NextRangeDetector();

    // 验证靶场代码是否有效
    if (!$detector->validateRangeCode($rangeCode)) {
        throw new Exception('无效的靶场代码：' . $rangeCode);
    }

    // 获取下一个靶场信息
    $nextRangeInfo = $detector->getNextRange($rangeCode);

    // 获取进度信息
    $progressInfo = $detector->getRangeProgress($rangeCode);

    // 构建响应数据
    $response['success'] = true;
    $response['message'] = '查询成功';
    $response['data'] = array(
        'current_range' => $rangeCode,
        'next_range' => $nextRangeInfo,
        'progress' => $progressInfo
    );

} catch (Exception $e) {
    // 错误处理
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
    $response['data'] = null;

    // 记录错误日志
    error_log('[HeaSec NextRange API Error] ' . $e->getMessage());
}

// 返回JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>