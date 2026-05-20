<?php
/**
 * HeaSec天积安全团队 - 会话安全配置文件
 * Session Security Configuration
 * 版本: v1.0.0
 * 创建日期: 2025-11-17
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 使用说明：
 * 1. 靶场环境默认关闭安全防护，便于学习和测试
 * 2. 生产环境建议开启所有安全防护
 * 3. 可通过环境变量覆盖配置
 */

return [
    // ============================================
    // 靶场安全防护配置（默认关闭）
    // ============================================

    // 会话劫持防护（总开关）
    'session_hijacking_protection' => false,

    // IP验证（防止会话劫持）
    // 开启后，会验证客户端IP是否与会话创建时一致
    'ip_validation' => false,

    // User-Agent验证（防止会话劫持）
    // 开启后，会验证User-Agent是否与会话创建时一致
    'user_agent_validation' => false,

    // 会话过期检查
    // 开启后，会检查会话是否超时
    'session_expiry_check' => false,

    // 会话固定攻击防护
    // 开启后，使用严格会话模式
    'session_fixation_protection' => false,

    // 严格会话模式
    // 开启后，拒绝未初始化的会话ID
    'strict_session_mode' => false,

    // ============================================
    // Cookie安全配置
    // ============================================

    // Cookie安全标志（仅HTTPS）
    // 生产环境建议开启
    'cookie_secure_flag' => false,

    // Cookie HttpOnly标志（防止XSS）
    // 建议始终开启
    'cookie_httponly_flag' => true,

    // Cookie SameSite策略
    // 可选值: 'Lax', 'Strict', 'None'
    // 'Strict' - 最安全，只允许同站点请求
    // 'Lax' - 平衡安全性和兼容性
    // 'None' - 允许跨站点（需要Secure标志）
    'cookie_samesite_policy' => 'Lax',

    // ============================================
    // 系统配置
    // ============================================

    // 会话垃圾回收
    // 建议始终开启
    'session_gc_enabled' => true,

    // 会话日志记录
    // 开启后会记录安全相关事件到日志文件
    'session_logging' => false,

    // ============================================
    // 快速配置预设
    // ============================================

    /*
     * 使用预设配置的方法：
     *
     * 1. 靶场模式（默认）：
     *    - 关闭所有安全防护
     *    - 保留基本Cookie防护
     *    - 适合学习和测试
     *
     * 2. 开发模式：
     *    - 开启部分安全防护
     *    - 启用日志记录
     *    - 适合开发环境
     *
     * 3. 生产模式：
     *    - 开启所有安全防护
     *    - 启用日志记录
     *    - 适合生产环境
     */
];

// ============================================
// 预设配置示例（注释状态）
// ============================================

/*
// 靶场模式（当前默认）
return [
    'session_hijacking_protection' => false,
    'ip_validation' => false,
    'user_agent_validation' => false,
    'session_expiry_check' => false,
    'session_fixation_protection' => false,
    'strict_session_mode' => false,
    'cookie_secure_flag' => false,
    'cookie_httponly_flag' => true,
    'cookie_samesite_policy' => 'Lax',
    'session_gc_enabled' => true,
    'session_logging' => false
];
*/

/*
// 开发模式
return [
    'session_hijacking_protection' => true,
    'ip_validation' => true,
    'user_agent_validation' => false, // 开发时可能频繁更换浏览器
    'session_expiry_check' => true,
    'session_fixation_protection' => true,
    'strict_session_mode' => true,
    'cookie_secure_flag' => false, // 开发环境通常不用HTTPS
    'cookie_httponly_flag' => true,
    'cookie_samesite_policy' => 'Lax',
    'session_gc_enabled' => true,
    'session_logging' => true
];
*/

/*
// 生产模式
return [
    'session_hijacking_protection' => true,
    'ip_validation' => true,
    'user_agent_validation' => true,
    'session_expiry_check' => true,
    'session_fixation_protection' => true,
    'strict_session_mode' => true,
    'cookie_secure_flag' => true, // 生产环境强制HTTPS
    'cookie_httponly_flag' => true,
    'cookie_samesite_policy' => 'Strict',
    'session_gc_enabled' => true,
    'session_logging' => true
];
*/