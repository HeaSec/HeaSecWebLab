-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-03-05
-- 靶场: 水平越权基础
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_idref_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_idref_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `account` VARCHAR(50) DEFAULT NULL COMMENT '账号（仅test和guanliyuan有）',
    `password` VARCHAR(100) DEFAULT NULL COMMENT '密码（仅test和guanliyuan有）',
    `name` VARCHAR(50) NOT NULL COMMENT '真实姓名',
    `phone` VARCHAR(20) NOT NULL COMMENT '手机号',
    `idcard` VARCHAR(20) NOT NULL COMMENT '身份证号',
    `num_id` INT DEFAULT NULL COMMENT '数字ID（用于第一关，1000-9999）',
    `user_id` VARCHAR(10) DEFAULT NULL COMMENT '用户ID（用于第三关，10位随机字符串）',
    `passcode` VARCHAR(30) DEFAULT NULL COMMENT '通关密码（仅guanliyuan账号有）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_account` (`level`, `account`),
    UNIQUE KEY `idx_level_num_id` (`level`, `num_id`),
    UNIQUE KEY `idx_level_user_id` (`level`, `user_id`),
    KEY `idx_level_phone` (`level`, `phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='水平越权基础靶场用户表';

-- 注意：用户数据将在首次访问对应关卡时由PHP代码自动创建
-- 每关包含：test账号、guanliyuan账号、8个干扰用户
