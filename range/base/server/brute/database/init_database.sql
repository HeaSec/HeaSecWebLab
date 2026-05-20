-- heasec_base 数据库初始化脚本
-- 创建时间: 2025-12-09 22:33:59
-- 靶场: 暴力破解
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_base`;

-- 靶场其他表结构请在此处添加
-- 建议使用表前缀: heasec_brute_

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_brute_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_brute_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(100) NOT NULL COMMENT '密码（存储哈希值）',
    `level` TINYINT NOT NULL DEFAULT 1 COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username_level` (`username`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='暴力破解用户表';

-- 注意：第二关和第三关的账号将在用户首次访问时自动创建
-- 第二关：从zhangjing、chenbin、wangwei、lijie、linting中随机选择，密码固定为123456
-- 第三关：从test、user、guest中随机选择用户名，从test、abc123、password中随机选择密码

