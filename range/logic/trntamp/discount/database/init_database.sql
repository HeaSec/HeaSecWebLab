-- ============================================================
-- HeaSec天积安全团队 - 优惠滥用靶场数据库初始化脚本
-- 版本: v1.0.0
-- 创建日期: 2026-03-21
-- 团队: 天积安全 (HeavenlySecret)
-- 数据库: heasec_logic
-- ============================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- ============================================================
-- 删除已存在的表（确保重置到初始状态）
-- ============================================================
DROP TABLE IF EXISTS `heasec_discount_orders`;
DROP TABLE IF EXISTS `heasec_discount_coupons`;
DROP TABLE IF EXISTS `heasec_discount_products`;
DROP TABLE IF EXISTS `heasec_discount_users`;

-- ============================================================
-- 创建用户表
-- ============================================================
CREATE TABLE IF NOT EXISTS `heasec_discount_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额',
    `points` INT NOT NULL DEFAULT 0 COMMENT '积分',
    `first_purchase` TINYINT NOT NULL DEFAULT 0 COMMENT '是否已首购（0:否，1:是）',
    `passcode` VARCHAR(50) DEFAULT NULL COMMENT '通关密码（用户首次登录时生成）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠滥用靶场用户表';

-- ============================================================
-- 创建商品表
-- ============================================================
CREATE TABLE IF NOT EXISTS `heasec_discount_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `price` DECIMAL(10,2) NOT NULL COMMENT '商品单价',
    `allow_points` TINYINT NOT NULL DEFAULT 0 COMMENT '是否支持积分支付（0:否，1:是）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠滥用靶场商品表';

-- ============================================================
-- 创建优惠券表
-- ============================================================
CREATE TABLE IF NOT EXISTS `heasec_discount_coupons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `name` VARCHAR(100) NOT NULL COMMENT '优惠券名称',
    `min_amount` DECIMAL(10,2) NOT NULL COMMENT '最低消费金额',
    `discount` DECIMAL(10,2) NOT NULL COMMENT '优惠金额',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠滥用靶场优惠券表';

-- ============================================================
-- 创建订单表
-- ============================================================
CREATE TABLE IF NOT EXISTS `heasec_discount_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `order_no` VARCHAR(50) NOT NULL COMMENT '订单号',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `product_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `quantity` INT NOT NULL COMMENT '购买数量',
    `price` DECIMAL(10,2) NOT NULL COMMENT '购买时的单价',
    `coupon_ids` VARCHAR(255) DEFAULT NULL COMMENT '使用的优惠券ID（JSON数组格式，如[1,2]）',
    `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
    `payment_type` VARCHAR(20) NOT NULL DEFAULT 'balance' COMMENT '支付方式（balance/points）',
    `total_amount` DECIMAL(10,2) NOT NULL COMMENT '订单金额',
    `paid_amount` DECIMAL(10,2) NOT NULL COMMENT '实际支付金额',
    `used_points` INT NOT NULL DEFAULT 0 COMMENT '使用的积分',
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT '状态（pending/paid）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `paid_at` DATETIME DEFAULT NULL COMMENT '支付时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_order_no` (`order_no`),
    KEY `idx_user_level` (`user_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='优惠滥用靶场订单表';

-- ============================================================
-- 插入用户数据（heasec账号）
-- ============================================================
INSERT INTO `heasec_discount_users` (`level`, `username`, `password`, `balance`, `points`) VALUES
(1, 'heasec', '123456', 70.00, 0),
(2, 'heasec', '123456', 50.00, 12000),
(3, 'heasec', '123456', 100.00, 0);

-- ============================================================
-- 插入商品数据
-- ============================================================
INSERT INTO `heasec_discount_products` (`level`, `name`, `price`, `allow_points`) VALUES
-- 第一关商品
(1, '天积元宝', 100.00, 0),
-- 第二关商品
(2, '天积元宝', 50.00, 0),
(2, '天积小元宝', 20.00, 1),
-- 第三关商品
(3, '天积元宝', 50.00, 0);

-- ============================================================
-- 插入优惠券数据（仅第一关使用）
-- ============================================================
INSERT INTO `heasec_discount_coupons` (`level`, `name`, `min_amount`, `discount`) VALUES
(1, '满50减10优惠券', 50.00, 10.00),
(1, '满100减20优惠券', 100.00, 20.00);
