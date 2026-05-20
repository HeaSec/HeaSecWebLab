-- =====================================================
-- HeaSec天积安全团队 - 暴力破解前端加密靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-02-25
-- 团队: 天积安全 (HeavenlySecret)
-- =====================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_bruteenc_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_bruteenc_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(100) NOT NULL COMMENT '密码的SHA256哈希值（三关统一存储SHA256哈希）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='暴力破解前端加密靶场用户表';

-- 注意：admin账号的密码将在用户首次访问对应关卡时自动创建
-- 密码原始格式：4位字符串（第一位为大小写字母A-Z/a-z，后三位为数字0-9）
-- 存储格式：三关统一存储SHA256哈希值
