<?php
/**
 * HeaSec天积安全团队 - 反序列化实战靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-15
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');

// 切换工作目录到靶场根目录，确保文件路径相对路径正确解析
chdir(dirname(__DIR__));

// 引入目标类（使反序列化时能找到类定义）
require_once dirname(__DIR__) . '/classes/PluginValidator.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// 接收用户提交的序列化数据
$data = json_decode(file_get_contents('php://input'), true);
$serializedData = $data['data'] ?? '';

if (empty($serializedData)) {
    sendJsonResponse(false, '请输入序列化数据');
}

// 直接反序列化（漏洞点：未对输入做任何校验和过滤）
$obj = unserialize($serializedData);

if ($obj !== false && $obj instanceof PluginValidator) {
    // 调用验证器的处理方法（触发内部回调函数执行）
    $result = $obj->validate();

    $responseData = [
        'validatorName' => $obj->validatorName ?? 'unknown',
        'processResult' => is_string($result) ? substr($result, 0, 200) : 'processed',
        'message' => '数据验证完成'
    ];

    // 从回调函数返回值中检测是否读取到了敏感文件内容
    if (is_string($result) && preg_match('/\$secret_passcode\s*=\s*[\'"]([^\'"]+)[\'"]/', $result, $matches)) {
        $responseData['secret'] = $matches[1];
        $response = ['success' => true, 'message' => '数据验证完成！检测到敏感数据泄露', 'data' => $responseData];
    } else {
        $response = ['success' => true, 'message' => '数据验证完成', 'data' => $responseData];
    }
} else {
    $response = ['success' => false, 'message' => '反序列化失败或对象类型不正确，请检查数据格式'];
}

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
