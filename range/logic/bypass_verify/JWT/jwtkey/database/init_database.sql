-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-03-03
-- 靶场: JWT密钥注入
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_jwtkey_records`;
DROP TABLE IF EXISTS `heasec_jwtkey_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_jwtkey_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `role` VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色（user/admin）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='JWT密钥注入靶场用户表';

-- 创建成就记录表
CREATE TABLE IF NOT EXISTS `heasec_jwtkey_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `attack_type` VARCHAR(30) NOT NULL COMMENT '攻击类型（kid_injection/jku_injection/kid_traversal）',
    `success_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '成功次数',
    `last_success_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '最后成功时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_attack_type` (`attack_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='JWT密钥注入靶场攻击记录表';

-- 注意：test账号和admin账号将在用户首次访问时自动创建
