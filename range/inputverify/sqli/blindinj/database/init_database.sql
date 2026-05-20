-- ============================================================
-- HeaSec天积安全团队 - SQL盲注靶场 - 数据库初始化脚本
-- 版本: v1.0.0
-- 数据库: heasec_sqli
-- 表名前缀: heasec_blindinj_
-- 说明: 本脚本可重复执行（使用 DROP TABLE IF EXISTS）
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- -----------------------------------------------------------
-- 通关密码表（第一关：报错注入目标）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_blindinj_flag;
CREATE TABLE heasec_blindinj_flag (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    password VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码（20位随机字符串，首次访问时由PHP生成）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通关密码表（第一关）';

INSERT INTO heasec_blindinj_flag (id, password) VALUES (1, '');

-- -----------------------------------------------------------
-- MySQL变量密码表（第二关/第三关）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_blindinj_vars;
CREATE TABLE heasec_blindinj_vars (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    level TINYINT UNSIGNED NOT NULL COMMENT '关卡编号（2或3）',
    var_name VARCHAR(50) NOT NULL COMMENT '变量名',
    var_value VARCHAR(100) NOT NULL DEFAULT '' COMMENT '密码值（L2为20位，L3为10位随机字符串）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    UNIQUE KEY idx_level_varname (level, var_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='MySQL变量密码表（第二关/第三关）';

INSERT INTO heasec_blindinj_vars (level, var_name, var_value) VALUES
(2, 'password', ''),
(3, 'password3', '');

-- -----------------------------------------------------------
-- 用户表（第二关：登录场景）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_blindinj_users;
CREATE TABLE heasec_blindinj_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    password VARCHAR(100) NOT NULL COMMENT '密码',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态（1=正常，0=禁用）',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表（第二关）';

INSERT INTO heasec_blindinj_users (username, password, status, email) VALUES
('admin', 'admin123', 1, 'admin@heasec.com'),
('test', 'test123', 1, 'test@heasec.com'),
('guest', 'guest123', 0, 'guest@heasec.com');

-- -----------------------------------------------------------
-- 商品表（第一关：查询场景）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_blindinj_products;
CREATE TABLE heasec_blindinj_products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '商品名称',
    price DECIMAL(10,2) DEFAULT NULL COMMENT '价格',
    stock INT UNSIGNED DEFAULT 0 COMMENT '库存',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态（1=上架，0=下架）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表（第一关）';

INSERT INTO heasec_blindinj_products (name, price, stock, status) VALUES
('天积元宝', 100.00, 50, 1),
('天积小元宝', 50.00, 100, 1),
('天积铜钱', 10.00, 200, 1);

-- -----------------------------------------------------------
-- 系统检查记录表（第三关：检查场景）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_blindinj_checks;
CREATE TABLE heasec_blindinj_checks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    check_key VARCHAR(100) NOT NULL COMMENT '检查参数键名',
    status VARCHAR(50) DEFAULT 'normal' COMMENT '检查状态',
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '检查时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统检查记录表（第三关）';

INSERT INTO heasec_blindinj_checks (check_key, status) VALUES
('health', 'normal'),
('database', 'normal'),
('cache', 'normal');
