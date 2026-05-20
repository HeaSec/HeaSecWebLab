-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-01-20
-- 靶场: 图片验证码绕过1
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- =============================================
-- 图片验证码绕过1靶场表结构
-- 表前缀: heasec_imgcodebp1_
-- =============================================

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_imgcodebp1_passwords`;

-- 创建密码表
CREATE TABLE IF NOT EXISTS `heasec_imgcodebp1_passwords` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level` TINYINT NOT NULL COMMENT '关卡（2:第二关，3:第三关）',
    `password` VARCHAR(10) NOT NULL COMMENT '密码',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='图片验证码绕过靶场密码表';

-- 注意：密码记录将在用户首次访问对应关卡时自动创建
