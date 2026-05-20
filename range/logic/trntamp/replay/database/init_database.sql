-- =====================================================
-- HeaSec天积安全团队 - 重放攻击靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-03-12
-- 团队: 天积安全 (HeavenlySecret)
-- =====================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_replay_signins`;
DROP TABLE IF EXISTS `heasec_replay_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_replay_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='重放攻击靶场用户表';

-- 创建签到记录表
CREATE TABLE IF NOT EXISTS `heasec_replay_signins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `signin_date` DATE NOT NULL COMMENT '签到日期',
    `amount` DECIMAL(10,2) NOT NULL COMMENT '获得的金额',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_level_date` (`user_id`, `level`, `signin_date`)
    -- 注意：不添加唯一索引约束，以便第三关的竞态条件漏洞可以被利用
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='重放攻击靶场签到记录表';

-- 插入初始数据（heasec账号）
INSERT INTO `heasec_replay_users` (`level`, `username`, `password`, `balance`) VALUES
(1, 'heasec', '123456', 0.00),
(2, 'heasec', '123456', 0.00),
(3, 'heasec', '123456', 0.00);

