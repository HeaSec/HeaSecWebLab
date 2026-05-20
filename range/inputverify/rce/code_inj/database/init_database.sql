-- heasec_inputverify 数据库初始化脚本（代码注入靶场部分）
-- 创建时间: 2026-04-22
-- 靶场: 代码注入
-- 说明: 此脚本用于初始化/重置代码注入靶场的用户数据表

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_inputverify` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_inputverify`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_code_inj_user`;

CREATE TABLE IF NOT EXISTS `heasec_code_inj_user` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL COMMENT '用户名',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    `bio` TEXT COMMENT '个人简介',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代码注入靶场-用户信息表';

-- 插入预设测试用户数据
INSERT IGNORE INTO `heasec_code_inj_user` (`username`, `email`, `bio`) VALUES
('admin', 'admin@heasec.com', '我是天积平台的管理员，热爱网络安全技术研究。');
