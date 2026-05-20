-- =====================================================
-- HeaSec天积安全团队 - 用户枚举靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-02-27
-- 团队: 天积安全 (HeavenlySecret)
-- =====================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_userenum_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_userenum_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名（手机号）',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户枚举靶场用户表';

-- 注意：测试账号和目标账号将在用户首次访问对应关卡时自动创建
-- 测试账号：13866668888 / 123456
-- 目标账号：1100591XXXX / 随机4位字符串
