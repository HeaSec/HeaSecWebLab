<?php
/**
 * HeaSec天积安全团队 - 越权访问综合实战获取用户信息接口
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

define('HEASEC_RANGE_ACCESS', true);
$commonBasePath = '../../../../common/';
require_once __DIR__ . '/../includes/bootstrap.php';

privesc_handle_api(function () {
    privesc_require_method('GET');

    $pdo = privesc_get_pdo();
    privesc_ensure_seed_data($pdo);

    $currentUser = privesc_require_login($pdo);

    // 必须带 username 参数
    if (!isset($_GET['username'])) {
        privesc_json_error('缺少必要参数 username');
    }

    $username = trim((string) $_GET['username']);

    // 如果 username 参数为空，返回所有用户信息（漏洞点：信息泄露）
    if ($username === '') {
        $users = privesc_get_user_list($pdo);
        if (empty($users)) {
            privesc_json_error('暂无用户数据');
        }
        // 返回所有用户，前端只展示第一个
        privesc_json_success('', [
            'user' => $users[0],
            'all_users' => $users,
        ]);
        return;
    }

    // 校验用户名格式
    if (!privesc_is_valid_username($username)) {
        privesc_json_error('用户名格式不正确');
    }

    // 校验是否为当前登录用户（权限检查）
    if ($username !== $currentUser['username']) {
        privesc_json_error('无权访问其他用户信息');
    }

    $targetUser = privesc_fetch_user_by_username($pdo, $username);
    if (!$targetUser) {
        privesc_json_error('用户不存在');
    }

    privesc_json_success('', [
        'user' => privesc_build_public_user_info($pdo, $targetUser, true),
    ]);
});
