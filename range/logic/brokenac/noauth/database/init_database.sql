-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-03-05
-- 靶场: 未授权访问
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_noauth_config`;

-- 创建配置表
CREATE TABLE IF NOT EXISTS `heasec_noauth_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `admin_password` VARCHAR(100) NOT NULL COMMENT '管理员密码（10位随机字符串）',
    `passcode` VARCHAR(30) NOT NULL COMMENT '通关密码（20位随机字符串）',
    `random_path` VARCHAR(100) NOT NULL COMMENT '随机生成的路径信息（文件名、目录名或接口路径）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='未授权访问靶场配置表';

-- 注意：管理员密码、通关密码和随机路径将在用户首次访问对应关卡时自动创建
-- 重置数据库时将清除所有数据，下次访问时重新生成
