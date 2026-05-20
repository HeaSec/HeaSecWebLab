-- =============================================
-- HeaSec天积安全团队 - 密码重置凭证可猜测靶场
-- 数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-01-22
-- 团队: 天积安全 (HeavenlySecret)
-- =============================================
-- 数据库: heasec_logic (与其他逻辑漏洞靶场共用)
-- 表前缀: heasec_resetlink_
-- 说明: 此脚本用于初始化靶场数据到初始状态
-- =============================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- =============================================
-- 删除已存在的表（确保重置到初始状态）
-- =============================================
DROP TABLE IF EXISTS `heasec_resetlink_users`;

-- =============================================
-- 创建用户表
-- =============================================
CREATE TABLE IF NOT EXISTS `heasec_resetlink_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `user_id` VARCHAR(10) NOT NULL COMMENT '用户ID（8位随机字符串）',
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `is_admin` TINYINT NOT NULL DEFAULT 0 COMMENT '是否是管理员（0:否，1:是）',
    `friend_added` TINYINT NOT NULL DEFAULT 0 COMMENT '好友是否已添加（0:未添加，1:已添加）',
    `friend_username` VARCHAR(50) DEFAULT NULL COMMENT '已添加的好友账号',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`),
    UNIQUE KEY `idx_level_userid` (`level`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='密码重置功能绕过靶场用户表';

-- =============================================
-- 插入初始数据（test账号）
-- =============================================
INSERT INTO `heasec_resetlink_users` (`level`, `username`, `password`, `user_id`, `phone`, `is_admin`) VALUES
(1, 'test', '123456', 'test001', '13866668888', 0),
(2, 'test', '123456', 'test002', '13866668888', 0),
(3, 'test', '123456', 'test003', '13866668888', 0);

-- =============================================
-- 注意：admin账号的密码和user_id将在用户首次访问对应关卡时自动创建
-- =============================================
