-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-03-05
-- 靶场: 垂直越权基础
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_vertbp_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_vertbp_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `account` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `role` VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色（user/admin）',
    `passcode` VARCHAR(30) DEFAULT NULL COMMENT '通关密码（仅admin账号有）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_account` (`level`, `account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT='垂直越权基础靶场用户表';

-- 注意：test账号和admin账号将在用户首次访问对应关卡时自动创建
-- admin账号的通关密码在首次创建时生成并存储在数据库中
