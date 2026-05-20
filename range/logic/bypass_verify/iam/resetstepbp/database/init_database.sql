-- HeaSec天积安全团队 - 密码重置流程绕过靶场 - 数据库初始化脚本
-- 数据库名: heasec_logic
-- 版本: v1.1.0
-- 创建日期: 2026-02-04
-- 更新日期: 2026-04-01
-- 团队: 天积安全 (HeavenlySecret)
-- 表前缀: heasec_resetstepbp_

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_resetstepbp_reset_tokens`;
DROP TABLE IF EXISTS `heasec_resetstepbp_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_resetstepbp_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `user_id` VARCHAR(10) NOT NULL COMMENT '用户ID（8位随机字符串）',
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `is_admin` TINYINT NOT NULL DEFAULT 0 COMMENT '是否是管理员（0:否，1:是）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`),
    UNIQUE KEY `idx_level_userid` (`level`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='密码重置流程绕过靶场用户表';

-- 创建密码重置令牌表（第三关HOST头注入）
CREATE TABLE IF NOT EXISTS `heasec_resetstepbp_reset_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `token` VARCHAR(64) NOT NULL COMMENT '重置令牌（32位随机十六进制字符串）',
    `host` VARCHAR(255) NOT NULL COMMENT '生成链接时使用的HOST头',
    `reset_link` VARCHAR(500) NOT NULL COMMENT '生成的完整重置链接',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `expires_at` DATETIME NOT NULL COMMENT '过期时间（创建时间+30分钟）',
    `used` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已使用（0:未使用，1:已使用）',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_token` (`token`),
    KEY `idx_username` (`username`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='密码重置令牌表（第三关HOST头注入）';

-- 插入第三关attacker账号（密码固定为123456）
INSERT INTO `heasec_resetstepbp_users` (`level`, `username`, `password`, `user_id`, `phone`, `is_admin`)
VALUES (3, 'attacker', '123456', 'atkR3s7B', '13866668888', 0);

-- 注意：admin账号的密码和user_id将在用户首次访问对应关卡时自动创建（随机生成）
-- admin账号手机号：11055557777（使用保留号段，短信模拟器无法查看）
-- 第三关attacker账号：attacker/123456，手机号：13866668888（攻击者可控，可通过短信模拟器查看）
