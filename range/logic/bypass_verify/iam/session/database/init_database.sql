-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-04-01
-- 靶场: 会话安全
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_session_param_logs`;
DROP TABLE IF EXISTS `heasec_session_active_sessions`;
DROP TABLE IF EXISTS `heasec_session_passcodes`;
DROP TABLE IF EXISTS `heasec_session_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_session_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `role` VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色（user/admin）',
    `realname` VARCHAR(50) NOT NULL COMMENT '姓名',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会话安全靶场用户表';

-- 创建活跃会话表（第二关使用）
CREATE TABLE IF NOT EXISTS `heasec_session_active_sessions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `session_id` VARCHAR(128) NOT NULL COMMENT '会话ID',
    `username` VARCHAR(50) NOT NULL COMMENT '登录用户名',
    `role` VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色（user/admin）',
    `login_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
    `last_active` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后活跃时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`),
    KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会话安全靶场活跃会话表';

-- 创建请求参数记录表（第三关使用）
CREATE TABLE IF NOT EXISTS `heasec_session_param_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `param_type` VARCHAR(20) NOT NULL COMMENT '参数类型（username/sid/url）',
    `param_value` TEXT NOT NULL COMMENT '参数值',
    `session_id` VARCHAR(128) NOT NULL COMMENT '当时的会话ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level_session` (`level`, `session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会话安全靶场请求参数记录表';

-- 创建通关密码表
CREATE TABLE IF NOT EXISTS `heasec_session_passcodes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `passcode` VARCHAR(50) NOT NULL COMMENT '20位随机通关密码',
    `session_id` VARCHAR(128) NOT NULL COMMENT '关联的会话ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`),
    KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='会话安全靶场通关密码表';

-- 插入测试用户数据（test账号）
INSERT INTO `heasec_session_users` (`level`, `username`, `password`, `role`, `realname`) VALUES
(1, 'test', '123456', 'user', '卓策仕'),
(2, 'test', '123456', 'user', '卓策仕'),
(3, 'test', '123456', 'user', '卓策仕');

-- 插入管理员用户数据（仅第二关，密码在首次访问时动态更新）
INSERT INTO `heasec_session_users` (`level`, `username`, `password`, `role`, `realname`) VALUES
(2, 'admin', 'placeholder', 'admin', '关莉媛');
