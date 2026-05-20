-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-03-06
-- 靶场: 文件越权访问
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_filebac_data`;

-- 创建数据表
CREATE TABLE IF NOT EXISTS `heasec_filebac_data` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `account` VARCHAR(50) NOT NULL COMMENT '账号（测试用户）',
    `password` VARCHAR(100) NOT NULL COMMENT '密码（明文存储）',
    `user_data` JSON NOT NULL COMMENT 'JSON格式存储用户相关数据',
    `target_identifier` VARCHAR(100) NOT NULL COMMENT '目标文件标识（学号/订单号/手机号）',
    `passcode` VARCHAR(30) NOT NULL COMMENT '通关密码（20位随机字符串）',
    `file_data` JSON NOT NULL COMMENT 'JSON格式存储干扰文件信息',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文件越权访问靶场数据表';

-- 注意：用户数据、通关密码和文件信息将在用户首次访问对应关卡时自动创建
-- 重置数据库时将清除所有数据，下次访问时重新生成
