<?php
/**
 * HeaSec天积安全团队 - 批量添加手机号接口
 * API: Batch Add Phone Numbers
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
    'success' => true,
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

    // 获取手机号列表
    $phonesText = isset($requestData['phones']) ? trim($requestData['phones']) : '';

    // 基础校验
    if (empty($phonesText)) {
        throw new Exception('手机号列表不能为空');
    }

    // 按行分割手机号
    $phonesArray = preg_split('/\r\n|\r|\n/', $phonesText);
    $phonesArray = array_filter($phonesArray, 'trim'); // 去除空行
    $phonesArray = array_unique($phonesArray); // 去重

    if (empty($phonesArray)) {
        throw new Exception('没有有效的手机号');
    }

    // 获取数据库连接
    $pdo = HeaSec_Database::getConnection('heasec_common');

    // 结果统计
    $results = array(
        'total' => count($phonesArray),
        'success' => 0,
        'failed' => 0,
        'success_list' => array(),
        'failed_list' => array()
    );

    // 逐个处理手机号
    foreach ($phonesArray as $phoneNumber) {
        $phoneNumber = trim($phoneNumber);

        // 跳过空行
        if (empty($phoneNumber)) {
            continue;
        }

        $error = null;

        // 验证手机号格式
        if (strpos($phoneNumber, '110') === 0) {
            $error = '保留号段(110)不允许注册';
        } elseif (!preg_match('/^1[3-9]\d{9}$/', $phoneNumber)) {
            $error = '手机号格式不正确（1开头，11位数字）';
        } else {
            // 检查手机号是否已存在
            try {
                $sql = "SELECT id FROM heasec_sms_simulator WHERE phone_number = ? LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array($phoneNumber));
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $error = '手机号已存在';
                } else {
                    // 插入新手机号
                    $sql = "INSERT INTO heasec_sms_simulator (phone_number, is_default, status) VALUES (?, 0, 1)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array($phoneNumber));

                    // 记录成功
                    $results['success']++;
                    $results['success_list'][] = $phoneNumber;
                    continue; // 成功则跳过错误处理
                }
            } catch (PDOException $e) {
                $error = '数据库错误：' . $e->getMessage();
            }
        }

        // 记录失败
        $results['failed']++;
        $results['failed_list'][] = array(
            'phone' => $phoneNumber,
            'reason' => $error
        );
    }

    // 判断整体是否成功（至少有一个成功）
    $response['success'] = $results['success'] > 0;

    // 生成消息
    if ($results['success'] > 0 && $results['failed'] > 0) {
        $response['message'] = "批量添加完成：成功 {$results['success']} 个，失败 {$results['failed']} 个";
    } elseif ($results['success'] > 0) {
        $response['message'] = "批量添加成功：共添加 {$results['success']} 个手机号";
    } else {
        $response['message'] = "批量添加失败：所有 {$results['failed']} 个手机号都无法添加";
        $response['success'] = false;
    }

    $response['data'] = $results;

} catch (Exception $e) {
    // 捕获异常并返回错误信息
    $response['success'] = false;
    $response['message'] = '[HeaSec] ' . $e->getMessage();
}

// 输出JSON响应
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>