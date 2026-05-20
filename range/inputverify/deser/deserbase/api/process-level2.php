<?php
/**
 * HeaSec天积安全团队 - 反序列化练习靶场 - 第二关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserBase Range v1.0.0');

require_once '../includes/functions.php';

// 切换工作目录到靶场根目录，确保文件路径相对路径正确解析
chdir(dirname(__DIR__));

/**
 * 文件读取器类
 * 用于读取指定文件的内容并以字符串形式返回
 */
class FileReader {
    /** @var string 要读取的文件路径 */
    public $filename = 'logs/info.log';

    /**
     * 将对象转换为字符串时自动调用
     * 读取 filename 指定文件的内容并返回
     * @return string 文件内容或错误信息
     */
    public function __toString() {
        if (file_exists($this->filename)) {
            return file_get_contents($this->filename);
        }
        return "文件不存在：{$this->filename}";
    }
}

// 接收JSON格式的请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$serializedData = isset($data['data']) ? $data['data'] : '';

if (empty($serializedData)) {
    sendJsonResponse(false, '请输入序列化数据');
}

// 直接反序列化用户输入
$obj = unserialize($serializedData);

if ($obj !== false) {
    // 将对象用于字符串拼接，触发 __toString()
    $result = [
        'class'    => get_class($obj),
        'content'  => "文件内容：" . $obj,
        'filename' => property_exists($obj, 'filename') ? $obj->filename : '未知'
    ];
    sendJsonResponse(true, '反序列化成功', ['data' => $result]);
} else {
    sendJsonResponse(false, '反序列化失败，请检查数据格式');
}
