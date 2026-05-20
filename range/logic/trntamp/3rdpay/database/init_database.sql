-- HeaSec天积安全团队 - 三方支付漏洞靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-03-19
-- 团队: 天积安全 (HeavenlySecret)

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_3rdpay_order_items`;
DROP TABLE IF EXISTS `heasec_3rdpay_orders`;
DROP TABLE IF EXISTS `heasec_3rdpay_transactions`;
DROP TABLE IF EXISTS `heasec_3rdpay_products`;
DROP TABLE IF EXISTS `heasec_3rdpay_pay_users`;
DROP TABLE IF EXISTS `heasec_3rdpay_users`;

-- 创建电商用户表
CREATE TABLE IF NOT EXISTS `heasec_3rdpay_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `passcode` VARCHAR(50) DEFAULT NULL COMMENT '通关密码',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='三方支付靶场电商用户表';

-- 创建天积宝用户表
CREATE TABLE IF NOT EXISTS `heasec_3rdpay_pay_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `pay_password` VARCHAR(100) NOT NULL COMMENT '支付密码',
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='三方支付靶场天积宝用户表';

-- 创建天积宝流水表
CREATE TABLE IF NOT EXISTS `heasec_3rdpay_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `amount` DECIMAL(10,2) NOT NULL COMMENT '交易金额',
    `type` VARCHAR(20) NOT NULL COMMENT '交易类型（pay/refund）',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态（1:成功）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_level` (`user_id`, `level`),
    KEY `idx_order_no` (`order_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='三方支付靶场天积宝流水表';

-- 创建商品表
CREATE TABLE IF NOT EXISTS `heasec_3rdpay_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `price` DECIMAL(10,2) NOT NULL COMMENT '商品单价',
    `image` VARCHAR(255) DEFAULT NULL COMMENT '商品图片',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='三方支付靶场商品表';

-- 创建订单表
CREATE TABLE IF NOT EXISTS `heasec_3rdpay_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `quantity` INT NOT NULL COMMENT '购买数量',
    `price` DECIMAL(10,2) NOT NULL COMMENT '商品单价',
    `amount` DECIMAL(10,2) NOT NULL COMMENT '订单金额',
    `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
    `paid_amount` DECIMAL(10,2) DEFAULT NULL COMMENT '实际支付金额',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态（pending/paid/partial_refund/refunded）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `paid_at` DATETIME DEFAULT NULL COMMENT '支付时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_order_no` (`order_no`),
    KEY `idx_user_level` (`user_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='三方支付靶场订单表';

-- 创建订单详情表
CREATE TABLE IF NOT EXISTS `heasec_3rdpay_order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `order_id` INT UNSIGNED NOT NULL COMMENT '订单ID',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `quantity` INT NOT NULL COMMENT '购买数量',
    `price` DECIMAL(10,2) NOT NULL COMMENT '购买时的单价',
    `status` VARCHAR(20) NOT NULL DEFAULT 'normal' COMMENT '状态（normal/refunded）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='三方支付靶场订单详情表';

-- 插入电商用户数据（heasec账号）
INSERT INTO `heasec_3rdpay_users` (`level`, `username`, `password`) VALUES
(1, 'heasec', '123456'),
(2, 'heasec', '123456'),
(3, 'heasec', '123456');

-- 插入天积宝用户数据（heasecpay账号）
INSERT INTO `heasec_3rdpay_pay_users` (`level`, `username`, `pay_password`, `balance`) VALUES
(1, 'heasecpay', '666888', 20.00),
(2, 'heasecpay', '666888', 20.00),
(3, 'heasecpay', '666888', 70.00);

-- 插入商品数据
INSERT INTO `heasec_3rdpay_products` (`level`, `name`, `price`) VALUES
-- 第一关商品
(1, '天积元宝', 100.00),
-- 第二关商品
(2, '天积元宝', 100.00),
-- 第三关商品
(3, '天积元宝', 20.00);
