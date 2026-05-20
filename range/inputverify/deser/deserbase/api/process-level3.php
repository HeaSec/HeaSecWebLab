<?php
/**
 * HeaSec天积安全团队 - 反序列化练习靶场 - 第三关处理接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserBase Range v1.0.0');

require_once '../includes/functions.php';

/**
 * 用户资料类
 * 用于存储和更新用户个人信息
 */
class UserProfile {
    /** @var string 用户昵称 */
    public $nickname = '新用户';

    /** @var string 头像文件名 */
    public $avatar = 'avatar01.png';

    /** @var string 个性签名 */
    public $signature = '这个人很懒，什么都不想说啊';

    /** @var bool 是否为VIP用户 */
    public $isVIP = false;
}

// 接收JSON格式的请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$serializedData = isset($data['data']) ? $data['data'] : '';

if (empty($serializedData)) {
    sendJsonResponse(false, '请输入序列化数据');
}

// 安全过滤：移除危险关键字
$filteredData = str_replace('danger', '', $serializedData);

// 对原始数据执行反序列化，用于后续验证VIP是否通过字符串逃逸获得
$originalObj = @unserialize($serializedData);

// 反序列化过滤后的数据
$obj = unserialize($filteredData);

if ($obj !== false && $obj instanceof UserProfile) {
    $result = [
        'nickname'  => $obj->nickname,
        'avatar'    => $obj->avatar,
        'signature' => $obj->signature,
        'isVIP'     => $obj->isVIP ? '是' : '否'
    ];

    // 检查是否为VIP用户
    if ($obj->isVIP === true) {
        // 验证VIP是否通过字符串逃逸获得（而非直接属性篡改）
        // 如果原始（未过滤）数据中isVIP就已经为true，说明用户直接篡改了属性值
        // 只有通过字符串逃逸（过滤导致结构变化）注入的VIP才被认可
        $isDirectTampering = ($originalObj !== false
            && $originalObj instanceof UserProfile
            && $originalObj->isVIP === true);

        if (!$isDirectTampering) {
            // 通过字符串逃逸获得VIP，给予通关密码
            $passcode = extractPasscode(getSecretFilePath(3));
            $result['secret'] = $passcode;
            sendJsonResponse(true, '资料更新成功！检测到VIP用户', ['data' => $result]);
        }
    }

    // 普通用户或检测到直接篡改
    sendJsonResponse(true, '资料更新成功', ['data' => $result]);
} else {
    sendJsonResponse(false, '反序列化失败，请检查数据格式');
}
