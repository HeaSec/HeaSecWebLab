-- HeaSec天积安全团队 - 密码重置会话覆盖靶场 - 数据库初始化脚本
-- 数据库名: heasec_logic
-- 版本: v1.0.0
-- 创建日期: 2026-01-23
-- 团队: 天积安全 (HeavenlySecret)
-- 表前缀: heasec_sessionoverride_

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_sessionoverride_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_sessionoverride_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关）',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='密码重置会话覆盖靶场用户表';

-- 插入初始数据（test账号）
INSERT INTO `heasec_sessionoverride_users` (`level`, `username`, `password`, `user_id`, `phone`, `is_admin`) VALUES
(1, 'test', '123456', 'test0001', '13866668888', 0),
(2, 'test', '123456', 'test0002', '13866668888', 0);

-- 注意：admin账号的密码和user_id将在用户首次访问对应关卡时自动创建
