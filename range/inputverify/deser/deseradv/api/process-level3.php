<?php
/**
 * HeaSec天积安全团队 - 反序列化实战靶场 - 第三关处理接口
 * 版本: v2.0.0 (重构：内置类 Gadget Chain 利用)
 * 创建日期: 2026-04-15
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 核心变更：
 * - 从"stdClass/ArrayObject 数据信任"升级为"内置类 Gadget Chain 利用"
 * - 攻击者需利用 Exception/Error 的 protected $message 属性构造 payload
 * - 必须掌握 protected 属性的序列化格式（\0*\0message = 10字节）
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v2.0.0');

// 切换工作目录到靶场根目录，确保文件路径相对路径正确解析
chdir(dirname(__DIR__));

// 引入公共函数
require_once dirname(__DIR__) . '/includes/functions.php';

// 接收用户提交的序列化数据
$data = json_decode(file_get_contents('php://input'), true);
$serializedData = $data['data'] ?? '';

if (empty($serializedData)) {
    sendJsonResponse(false, '请输入序列化数据');
}

// 反序列化 — 仅允许 PHP 内置类白名单
// 安全措施：禁止所有自定义类，仅允许经过审计的内置类
$obj = unserialize($serializedData, [
    'allowed_classes' => [
        'Exception',
        'Error',
        'TypeError',
        'ParseError',
        'ArrayObject',
        'ArrayIterator',
        'SplFixedArray'
    ]
]);

if ($obj !== false) {
    $result = [
        'class'         => get_class($obj),
        'previewResult' => ''
    ];

    // === 内置类异常对象处理路径 ===
    // 系统将异常对象视为"错误报告"，提取其中的消息内容进行日志记录式预览
    if ($obj instanceof Exception || $obj instanceof Error) {
        $filePath = $obj->getMessage();

        if ($filePath !== null && is_string($filePath) && $filePath !== '') {
            try {
                $content = @file_get_contents($filePath);

                if ($content !== false) {
                    $result['previewResult'] = $content;

                    // 检查是否读到了秘密文件的内容（20位纯文本随机串）
                    if (strlen($content) >= 20 && preg_match('/^[a-zA-Z0-9]{20}$/', trim($content))) {
                        $result['secret'] = trim($content);
                        $response = ['success' => true, 'message' => '异常报告已处理！检测到敏感数据', 'data' => $result];
                    } else {
                        $response = ['success' => true, 'message' => '异常报告已处理', 'data' => $result];
                    }
                } else {
                    $result['previewResult'] = '[文件无法读取]';
                    $response = ['success' => true, 'message' => '异常报告已处理（目标不可读）', 'data' => $result];
                }
            } catch (Exception $e) {
                $result['previewResult'] = '处理异常：' . $e->getMessage();
                $response = ['success' => true, 'message' => '异常报告处理出错', 'data' => $result];
            }
        } else {
            $response = ['success' => true, 'message' => '异常对象已加载（消息为空）', 'data' => $result];
        }

    // === ArrayObject / ArrayIterator 处理路径（仅返回基本信息，不读取文件） ===
    } elseif ($obj instanceof ArrayObject || $obj instanceof ArrayIterator) {
        $response = ['success' => true, 'message' => '对象已加载（类型：' . get_class($obj) . '）— 该类不支持文件预览功能', 'data' => $result];

    } else {
        // 其他允许的内置类（SplFixedArray 等）仅返回基本信息
        $response = ['success' => true, 'message' => '对象已加载（类型：' . get_class($obj) . '）', 'data' => $result];
    }
} else {
    $response = ['success' => false, 'message' => '反序列化失败，请检查数据格式或确认使用的是允许的内置类'];
}

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v2.0.0');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
