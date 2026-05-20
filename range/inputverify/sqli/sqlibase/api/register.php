<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场注册接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    sqlibase_require_method('POST');

    $request = sqlibase_get_request_data();
    $username = sqlibase_get_string($request, 'username');
    $password = sqlibase_get_string($request, 'password');
    $name = sqlibase_get_string($request, 'name');

    if ($username === '' || $password === '' || $name === '') {
        sqlibase_json_error('请填写完整信息');
    }

    if (!sqlibase_is_valid_username($username)) {
        sqlibase_json_error('用户名须为4-20位字母、数字或下划线');
    }

    if (!sqlibase_is_valid_password($password)) {
        sqlibase_json_error('密码长度须为6-20位');
    }

    if (!sqlibase_is_valid_name($name)) {
        sqlibase_json_error('姓名长度须为1-50个字符');
    }

    $pdo = sqlibase_get_pdo();

    $existing = sqlibase_fetch_user_by_username($pdo, $username);
    if ($existing) {
        sqlibase_json_error('用户名已存在');
    }

    sqlibase_create_user($pdo, [
        'username' => $username,
        'password' => $password,
        'name'     => $name,
    ]);

    sqlibase_json_success('注册成功');
});
