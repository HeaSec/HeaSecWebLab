-- heasec_base 数据库初始化脚本
-- 创建时间: 2026-03-28
-- 靶场: CRLF注入
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_base`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_crlf_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_crlf_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(255) NOT NULL COMMENT '密码（使用password_hash加密）',
    `passcode` VARCHAR(20) DEFAULT NULL COMMENT '通关密码（通关时与当前会话秘密一致）',
    `payload` VARCHAR(500) DEFAULT NULL COMMENT '通关时使用的payload',
    `completed_at` DATETIME DEFAULT NULL COMMENT '通关时间',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CRLF注入靶场用户表';

-- 插入测试账号（密码为123456的bcrypt哈希值）
INSERT INTO `heasec_crlf_users` (`username`, `password`) VALUES
('heasec', '$2y$10$cX6V/c0JsIvPBgvHMKoqd.4oVuoyvIQvo2cjQrLXHD7oMgW6vDj9q');
