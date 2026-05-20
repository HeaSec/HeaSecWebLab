<?php
/**
 * HeaSec天积安全团队 - HTML前端校验绕过靶场API接口
 * 版本: v1.0.0
 * 创建日期: 2025-12-15
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec HTML前端校验绕过 API v1.0.0');
header('Content-Type: application/json; charset=utf-8');

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '[HeaSec] 只接受POST请求',
        'type' => 'error'
    ]);
    exit;
}

// 获取表单数据
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$age = isset($_POST['age']) ? trim($_POST['age']) : '';
$skill = isset($_POST['skill']) ? trim($_POST['skill']) : '';
$badDeed = isset($_POST['bad_deed']) ? trim($_POST['bad_deed']) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// 后端校验（按顺序进行，遇到第一个错误则返回）
$result = ['success' => false, 'message' => '', 'type' => 'error'];

// 1. 检查必填项（除了干过的坏事）
if (empty($name)) {
    $result['message'] = '申请不通过：缺少姓名信息';
} elseif (empty($nickname)) {
    $result['message'] = '申请不通过：缺少昵称信息';
} elseif (empty($gender)) {
    $result['message'] = '申请不通过：缺少性别信息';
} elseif (empty($phone)) {
    $result['message'] = '申请不通过：缺少手机号信息';
} elseif (empty($age)) {
    $result['message'] = '申请不通过：缺少年龄信息';
} elseif (empty($skill)) {
    $result['message'] = '申请不通过：缺少特长信息';
} elseif (empty($reason)) {
    $result['message'] = '申请不通过：缺少申请理由信息';
}
// 2. 校验昵称
elseif ($nickname === '脚本小子') {
    $result['message'] = '申请不通过：只想当个脚本小子怎么行！';
}
// 3. 校验手机号（后端要求11位，且包含数字和字母）
elseif (strlen($phone) !== 11) {
    $result['message'] = '申请不通过：黑客的手机号必须是11位！';
}
// 检查是否包含数字和字母，且不包含其他字符
elseif (!preg_match('/^[a-zA-Z0-9]+$/', $phone)) {
    $result['message'] = '申请不通过：黑客的手机号要包含数字和字母，但不能有其他字符！';
}
elseif (!preg_match('/[0-9]/', $phone) || !preg_match('/[a-zA-Z]/', $phone)) {
    $result['message'] = '申请不通过：黑客的手机号要包含数字和字母，但不能有其他字符！';
}
// 4. 校验年龄（后端要求16-25岁）
elseif (!is_numeric($age) || intval($age) != $age || intval($age) < 16 || intval($age) > 25) {
    $result['message'] = '申请不通过：年龄必须为整数，且在16-25岁之间';
}
// 5. 校验特长（必须选择"热爱网络安全"）
elseif ($skill === '长得好看') {
    $result['message'] = '申请不通过：特长不对，长得好看又不能当饭吃！';
} elseif ($skill === '脑子灵光') {
    $result['message'] = '申请不通过：特长不对，光靠小聪明是不够的！';
} elseif ($skill !== '热爱网络安全') {
    $result['message'] = '申请不通过：特长不对，不能自己创造特长！';
}
// 6. 校验干过的坏事（不能填写）
elseif (!empty($badDeed)) {
    $result['message'] = '申请不通过：干过坏事的人不能当黑客！';
}
// 7. 校验申请理由（必须是4遍"天积安全牛逼"）
elseif ($reason !== '天积安全牛逼天积安全牛逼天积安全牛逼天积安全牛逼') {
    $result['message'] = '申请不通过：理由必须为4遍天积安全牛逼！';
}
// 所有校验通过
else {
    $result = [
        'success' => true,
        'message' => $name . '同学，你的条件符合要求，恭喜你通过天积安全黑客学院的申请！',
        'type' => 'success',
        'showCongrats' => true
    ];
}

// 返回JSON响应
echo json_encode($result);