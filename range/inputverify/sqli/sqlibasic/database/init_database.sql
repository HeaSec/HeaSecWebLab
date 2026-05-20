-- =====================================================
-- HeaSec天积安全团队 - SQL注入基础靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-04-01
-- 团队: 天积安全 (HeavenlySecret)
-- 数据库: heasec_sqlbase
-- =====================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_sqlbase`
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE `heasec_sqlbase`;

-- =====================================================
-- 用户表（用于SQL注入演示）
-- =====================================================
DROP TABLE IF EXISTS `heasec_sqlbase_users`;
CREATE TABLE `heasec_sqlbase_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID',
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `email` VARCHAR(100) NOT NULL COMMENT '邮箱',
    `role` VARCHAR(20) DEFAULT 'user' COMMENT '角色',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 插入用户测试数据
INSERT INTO `heasec_sqlbase_users` (`username`, `password`, `email`, `role`) VALUES
('admin', 'admin123', 'admin@heasec.com', 'administrator'),
('testuser', 'test123', 'test@heasec.com', 'user'),
('demo', 'demo123', 'demo@heasec.com', 'user'),
('alice', 'alice123', 'alice@heasec.com', 'user'),
('bob', 'bob123', 'bob@heasec.com', 'user');

-- =====================================================
-- 产品表（用于SQL注入场景练习）
-- =====================================================
DROP TABLE IF EXISTS `heasec_sqlbase_products`;
CREATE TABLE `heasec_sqlbase_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '产品ID',
    `name` VARCHAR(100) NOT NULL COMMENT '产品名称',
    `price` DECIMAL(10,2) NOT NULL COMMENT '价格',
    `description` TEXT COMMENT '描述',
    `stock` INT DEFAULT 0 COMMENT '库存',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='产品表';

-- 插入产品测试数据
INSERT INTO `heasec_sqlbase_products` (`name`, `price`, `description`, `stock`) VALUES
('Apple', 5.00, 'Fresh red apple', 100),
('Banana', 3.00, 'Yellow banana from tropical', 150),
('Orange', 4.00, 'Sweet orange', 80),
('Grape', 8.00, 'Purple grape', 60),
('Watermelon', 15.00, 'Big watermelon', 30);

-- =====================================================
-- 秘密数据表（用于UNION注入获取目标数据）
-- =====================================================
DROP TABLE IF EXISTS `heasec_sqlbase_secrets`;
CREATE TABLE `heasec_sqlbase_secrets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID',
    `secret_name` VARCHAR(100) NOT NULL COMMENT '秘密名称',
    `secret_value` VARCHAR(255) NOT NULL COMMENT '秘密值',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='秘密数据表';

-- 插入秘密数据（注入目标）
INSERT INTO `heasec_sqlbase_secrets` (`secret_name`, `secret_value`) VALUES
('FLAG', 'HEASEC{SQL_1nj3ct10n_b4s1c_m4st3r3d}'),
('API_KEY', 'sk-heasec-xxxxxxxxxxxxxxxxxxxx'),
('DB_PASSWORD', 'Sup3rS3cr3tP@ssw0rd!'),
('ADMIN_TOKEN', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9');

-- =====================================================
-- 文章表（用于第四区域的注入练习）
-- =====================================================
DROP TABLE IF EXISTS `heasec_sqlbase_articles`;
CREATE TABLE `heasec_sqlbase_articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '文章ID',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` TEXT COMMENT '内容',
    `author` VARCHAR(50) COMMENT '作者',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章表';

-- 插入文章测试数据
INSERT INTO `heasec_sqlbase_articles` (`title`, `content`, `author`) VALUES
('SQL注入攻击概述', 'SQL注入是一种常见的Web安全漏洞...', 'admin'),
('如何防御SQL注入', '使用预处理语句是防御SQL注入的最佳实践...', 'testuser'),
('数据库安全最佳实践', '本文介绍数据库安全的最佳实践...', 'demo');
