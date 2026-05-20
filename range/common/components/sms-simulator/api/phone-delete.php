<?php
/**
 * HeaSec天积安全团队 - 删除手机号接口
 * API: Delete Phone Number
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
    $id = isset($requestData['id']) ? intval($requestData['id']) : 0;

    // 基础校验：检查必填参数
    if ($id <= 0) {
        throw new Exception('手机号ID不能为空');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 检查手机号是否存在
    $sql = "SELECT id, is_default FROM heasec_sms_simulator WHERE id = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($id));
    $phone = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$phone) {
        throw new Exception('手机号记录不存在');
    }

    // 检查是否为默认手机号
    if ($phone['is_default'] == 1) {
        throw new Exception('不能删除默认手机号，请先设置其他手机号为默认');
    }

    // 开始事务
    $pdo->beginTransaction();

    try {
        // 删除关联的短信记录
        $sql = "DELETE FROM heasec_sms_message WHERE simulator_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($id));
        $deletedSmsCount = $stmt->rowCount();

        // 删除手机号记录
        $sql = "DELETE FROM heasec_sms_simulator WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($id));

        // 提交事务
        $pdo->commit();

        // 返回成功响应
        $response['success'] = true;
        $response['message'] = '删除手机号成功';
        $response['data'] = array(
            'id' => $id,
            'deleted_sms_count' => $deletedSmsCount
        );

    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
