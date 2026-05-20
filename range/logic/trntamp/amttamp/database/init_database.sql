-- ============================================================
-- HeaSec天积安全团队 - 金额篡改靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-03-14
-- 团队: 天积安全 (HeavenlySecret)
-- 数据库: heasec_logic
-- ============================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_amttamp_order_items`;
DROP TABLE IF EXISTS `heasec_amttamp_orders`;
DROP TABLE IF EXISTS `heasec_amttamp_coupons`;
DROP TABLE IF EXISTS `heasec_amttamp_products`;
DROP TABLE IF EXISTS `heasec_amttamp_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_amttamp_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='金额篡改靶场用户表';

-- 创建商品表
CREATE TABLE IF NOT EXISTS `heasec_amttamp_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `price` DECIMAL(10,2) NOT NULL COMMENT '商品单价',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='金额篡改靶场商品表';

-- 创建订单表
CREATE TABLE IF NOT EXISTS `heasec_amttamp_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `total_amount` DECIMAL(10,2) NOT NULL COMMENT '订单总金额',
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态（1:已完成）',
    `passcode` VARCHAR(50) DEFAULT NULL COMMENT '通关密码（符合条件时生成）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_level` (`user_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='金额篡改靶场订单表';

-- 创建订单详情表
CREATE TABLE IF NOT EXISTS `heasec_amttamp_order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `quantity` INT NOT NULL COMMENT '购买数量',
    `price` DECIMAL(10,2) NOT NULL COMMENT '购买时的单价',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='金额篡改靶场订单详情表';

-- 创建优惠券表
CREATE TABLE IF NOT EXISTS `heasec_amttamp_coupons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `code` VARCHAR(50) NOT NULL COMMENT '优惠券码',
    `original_value` DECIMAL(10,2) NOT NULL COMMENT '原始面值',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态（0:未使用，1:已使用）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_level` (`user_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='金额篡改靶场优惠券表';

-- 插入初始用户数据（heasec账号）
INSERT INTO `heasec_amttamp_users` (`level`, `username`, `password`, `balance`) VALUES
(1, 'heasec', '123456', 1.00),
(2, 'heasec', '123456', 20.00),
(3, 'heasec', '123456', 10.00);

-- 插入商品数据
INSERT INTO `heasec_amttamp_products` (`level`, `name`, `price`) VALUES
-- 第一关商品
(1, '天积元宝', 100.00),
-- 第二关商品
(2, '天积元宝', 100.00),
(2, '天积小元宝', 40.00),
-- 第三关商品
(3, '天积元宝', 100.00);

-- 插入优惠券数据（第三关使用）
INSERT INTO `heasec_amttamp_coupons` (`user_id`, `level`, `code`, `original_value`, `status`)
SELECT id, 3, '', 5.00, 0 FROM `heasec_amttamp_users` WHERE `level` = 3 AND `username` = 'heasec';
