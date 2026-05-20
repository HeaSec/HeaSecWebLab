<?php
/**
 * HeaSec天积安全团队 - 反序列化实战靶场 - 第二关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-15
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');

// 切换工作目录到靶场根目录，确保文件路径相对路径正确解析
chdir(dirname(__DIR__));

// 引入所有POP链涉及的类（使反序列化时能找到类定义）
require_once dirname(__DIR__) . '/classes/PluginManager.php';
require_once dirname(__DIR__) . '/classes/Logger.php';
require_once dirname(__DIR__) . '/classes/CacheCleaner.php';
require_once dirname(__DIR__) . '/classes/FileReader.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// 接收用户提交的序列化数据
$data = json_decode(file_get_contents('php://input'), true);
$serializedData = $data['data'] ?? '';

if (empty($serializedData)) {
    sendJsonResponse(false, '请输入序列化数据');
}

// 直接反序列化（漏洞点：未对输入做任何校验和过滤）
$obj = unserialize($serializedData);

if ($obj !== false) {
    $responseData = [
        'className' => get_class($obj),
        'processResult' => 'plugin loaded',
        'message' => '插件配置导入完成'
    ];

    // 触发对象的析构方法以执行插件初始化流程
    // 对象销毁时自动调用 __destruct，遍历插件并执行初始化
    unset($obj);

    // 检查全局变量是否捕获到了秘密内容（POP链成功读取文件的标志）
    if (isset($GLOBALS['__pop_chain_result']) && $GLOBALS['__pop_chain_result'] !== '') {
        $secretContent = $GLOBALS['__pop_chain_result'];
        if (preg_match('/\$secret_passcode\s*=\s*[\'"]([^\'"]+)[\'"]/', $secretContent, $matches)) {
            $responseData['secret'] = $matches[1];
            $response = ['success' => true, 'message' => '插件配置导入完成！检测到敏感数据泄露', 'data' => $responseData];
        } else {
            $response = ['success' => true, 'message' => '插件配置导入完成', 'data' => $responseData];
        }
    } else {
        $response = ['success' => true, 'message' => '插件配置导入完成', 'data' => $responseData];
    }
} else {
    $response = ['success' => false, 'message' => '反序列化失败，请检查数据格式'];
}

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
