<?php
/**
 * HeaSec天积安全团队 - 反序列化练习靶场 - 第一关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserBase Range v1.0.0');

require_once '../includes/functions.php';

/**
 * 用户信息类
 * 用于存储和展示用户基本信息
 */
class UserInfo {
    /** @var string 用户姓名 */
    public $name = 'guest';

    /** @var string 用户角色 */
    public $role = 'user';

    /**
     * 获取用户信息摘要
     * @return string 格式化的用户信息
     */
    public function getInfo() {
        return "姓名：{$this->name}，角色：{$this->role}";
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

if ($obj !== false && $obj instanceof UserInfo) {
    $result = [
        'name' => $obj->name,
        'role' => $obj->role,
        'info' => $obj->getInfo()
    ];

    // 检查角色属性
    if ($obj->role === 'admin') {
        $passcode = extractPasscode(getSecretFilePath(1));
        $result['secret'] = $passcode;
        sendJsonResponse(true, '反序列化成功！检测到管理员权限', ['data' => $result]);
    } else {
        sendJsonResponse(true, '反序列化成功', ['data' => $result]);
    }
} else {
    sendJsonResponse(false, '反序列化失败，请检查数据格式');
}
