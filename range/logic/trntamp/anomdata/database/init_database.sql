-- heasec_logic 数据库初始化脚本
-- 创建时间: 2026-03-18
-- 靶场: 异常数据处理靶场
-- 说明: 此脚本可用于重置数据库到初始状态

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `heasec_logic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `heasec_logic`;

-- 删除已存在的表（确保重置到初始状态）
DROP TABLE IF EXISTS `heasec_anomdata_orders`;
DROP TABLE IF EXISTS `heasec_anomdata_products`;
DROP TABLE IF EXISTS `heasec_anomdata_transactions`;
DROP TABLE IF EXISTS `heasec_anomdata_users`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `heasec_anomdata_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡（1:第一关，2:第二关，3:第三关）',
    `username` VARCHAR(50) NOT NULL COMMENT '账号',
    `password` VARCHAR(100) NOT NULL COMMENT '密码',
    `alipay_balance` DECIMAL(15,3) NOT NULL DEFAULT 0.000 COMMENT '支付宝余额（第一关使用）',
    `bank_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '银行卡余额（第一关使用）',
    `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT '普通余额（第二关和第三关使用）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_level_username` (`level`, `username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='异常数据处理靶场用户表';

-- 创建交易记录表
CREATE TABLE IF NOT EXISTS `heasec_anomdata_transactions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `type` VARCHAR(20) NOT NULL COMMENT '交易类型（withdraw/transfer/purchase）',
    `amount` DECIMAL(15,3) NOT NULL COMMENT '交易金额',
    `target_account` VARCHAR(50) DEFAULT NULL COMMENT '目标账户（转账时使用）',
    `detail` VARCHAR(255) DEFAULT NULL COMMENT '交易详情',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态（1:成功）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_level` (`user_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='异常数据处理靶场交易记录表';

-- 创建商品表
CREATE TABLE IF NOT EXISTS `heasec_anomdata_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
    `price` DECIMAL(10,2) NOT NULL COMMENT '商品单价',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='异常数据处理靶场商品表';

-- 创建订单表
CREATE TABLE IF NOT EXISTS `heasec_anomdata_orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `level` TINYINT NOT NULL COMMENT '关卡',
    `product_id` INT UNSIGNED NOT NULL COMMENT '商品ID',
    `quantity` INT NOT NULL COMMENT '购买数量',
    `total_amount` DECIMAL(15,2) NOT NULL COMMENT '订单总金额',
    `passcode` VARCHAR(50) DEFAULT NULL COMMENT '通关密码/二维码内容',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态（1:已完成）',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_user_level` (`user_id`, `level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='异常数据处理靶场订单表';

-- 插入初始用户数据（heasec账号）
INSERT INTO `heasec_anomdata_users` (`level`, `username`, `password`, `alipay_balance`, `bank_balance`, `balance`) VALUES
(1, 'heasec', '123456', 10.000, 0.00, 0.00),
(2, 'heasec', '123456', 0.000, 0.00, 300.00),
(3, 'heasec', '123456', 0.000, 0.00, 20.00);

-- 插入商品数据
INSERT INTO `heasec_anomdata_products` (`level`, `name`, `price`) VALUES
(2, '天积元宝', 100.00),
(3, '天积发布会门票', 100.00);
