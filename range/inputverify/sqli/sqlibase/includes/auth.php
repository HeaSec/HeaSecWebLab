<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场认证辅助
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取登录会话键名。
 *
 * @return string
 */
function sqlibase_get_auth_session_key()
{
    return 'sqlibase_user';
}

/**
 * 获取当前登录用户。
 *
 * @return array|null
 */
function sqlibase_get_current_user()
{
    return isset($_SESSION[sqlibase_get_auth_session_key()]) ? $_SESSION[sqlibase_get_auth_session_key()] : null;
}

/**
 * 检查是否已登录。
 *
 * @return bool
 */
function sqlibase_is_logged_in()
{
    return isset($_SESSION[sqlibase_get_auth_session_key()]);
}

/**
 * 设置登录状态。
 *
 * @param array $user 用户信息
 */
function sqlibase_set_login($user)
{
    $_SESSION[sqlibase_get_auth_session_key()] = [
        'id'       => (int) $user['id'],
        'username' => $user['username'],
        'name'     => $user['name'],
        'role'     => $user['role'],
    ];
}

/**
 * 退出登录。
 */
function sqlibase_logout()
{
    unset($_SESSION[sqlibase_get_auth_session_key()]);
    session_regenerate_id(true);
}

/**
 * 导出当前用户数据。
 *
 * @return array|null
 */
function sqlibase_export_user()
{
    $user = sqlibase_get_current_user();
    if (!$user) {
        return null;
    }

    return [
        'username' => $user['username'],
        'name'     => $user['name'],
        'role'     => $user['role'],
    ];
}

/**
 * 构建会话状态。
 *
 * @return array
 */
function sqlibase_build_session_state()
{
    $user = sqlibase_get_current_user();

    if (!$user) {
        return ['logged_in' => false];
    }

    return [
        'logged_in' => true,
        'user' => sqlibase_export_user(),
    ];
}
