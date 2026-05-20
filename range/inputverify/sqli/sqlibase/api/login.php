<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场登录接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

sqlibase_handle_api(function () {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        sqlibase_json_error('请输入用户名和密码');
    }

    $pdo = sqlibase_get_pdo();

    $sql = "SELECT * FROM heasec_sqlibase_users WHERE username = '$username' AND password = '$password' AND status = 1";

    try {
        $stmt = $pdo->query($sql);
        if ($stmt && $user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            sqlibase_set_login($user);
            sqlibase_json_success('登录成功', [
                'username' => $user['username'],
                'name'     => $user['name'],
                'role'     => $user['role'],
            ]);
        } else {
            sqlibase_json_error('用户名或密码错误', 401);
        }
    } catch (PDOException $e) {
        sqlibase_json_error('SQL错误: ' . $e->getMessage());
    }
});
