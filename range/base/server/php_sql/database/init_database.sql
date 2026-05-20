-- ================================================
-- HeaSec天积安全团队 - 服务端脚本基础靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2025-12-30
-- 数据库: heasec_base (共享数据库)
-- ================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `heasec_base`;

-- ================================================
-- 表1: PHP数据库操作示例表
-- 用于: examples/database.php
-- ================================================
DROP TABLE IF EXISTS `heasec_server_sql`;
CREATE TABLE IF NOT EXISTS `heasec_server_sql` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID，自增主键',
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名，唯一',
    `email` VARCHAR(100) NOT NULL COMMENT '邮箱地址',
    `age` INT DEFAULT 18 COMMENT '年龄，默认18',
    `status` TINYINT DEFAULT 1 COMMENT '状态：1启用 0禁用',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PHP服务端语言基础示例表';

-- ================================================
-- 表2: PHP Cookie和Session示例用户表
-- 用于: examples/session.php
-- ================================================
DROP TABLE IF EXISTS `heasec_server_user`;
CREATE TABLE IF NOT EXISTS `heasec_server_user` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID，自增主键',
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名，唯一',
    `password` VARCHAR(255) NOT NULL COMMENT '密码（实际应用应存储哈希值）',
    `email` VARCHAR(100) COMMENT '邮箱地址',
    `status` TINYINT DEFAULT 1 COMMENT '状态：1启用 0禁用',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PHP服务端语言基础用户表';

-- ================================================
-- 初始化测试数据（仅用于演示）
-- ================================================

-- 为Cookie/Session示例初始化测试账号（密码为明文，仅用于演示）
INSERT IGNORE INTO `heasec_server_user` (`username`, `password`, `email`, `status`) VALUES
('admin', 'admin123', 'admin@heasec.com', 1),
('user', 'user123', 'user@heasec.com', 1),
('test', 'test123', 'test@heasec.com', 1);

-- ================================================
-- 初始化完成
-- ================================================
