-- ============================================================
-- HeaSec天积安全团队 - SQL特殊注入场景靶场 - 数据库初始化脚本
-- 版本: v1.0.0
-- 数据库: heasec_sqli（与同分类下其他SQL注入靶场共享）
-- 表名前缀: heasec_specsi_
-- 说明: 本脚本可重复执行（使用 DROP TABLE IF EXISTS）
-- 注意: 本脚本只创建本靶场的表，不影响同数据库下其他靶场的表
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- -----------------------------------------------------------
-- 客户信息表（第一关：二次注入）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_specsi_customers;
CREATE TABLE heasec_specsi_customers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(200) NOT NULL COMMENT '用户名（需足够长度容纳information_schema探测payload）',
    password VARCHAR(100) NOT NULL COMMENT '密码',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色',
    UNIQUE KEY uk_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户信息表（第一关：二次注入）';

INSERT INTO heasec_specsi_customers (username, password, email, role) VALUES
('admin', '', 'admin@heasec.com', 'admin'),
('zhangsan', 'pass123', 'zhangsan@test.com', 'user'),
('lisi', 'pass456', 'lisi@test.com', 'user'),
('wangwu', 'pass789', 'wangwu@test.com', 'user'),
('zhaoliu', 'pass000', 'zhaoliu@test.com', 'user');

-- -----------------------------------------------------------
-- 服务订单表（第一关：二次注入触发点）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_specsi_orders;
CREATE TABLE heasec_specsi_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product VARCHAR(100) NOT NULL COMMENT '服务项目',
    amount DECIMAL(10,2) DEFAULT 0.00 COMMENT '金额',
    customer VARCHAR(100) NOT NULL COMMENT '关联客户用户名'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务订单表（第一关）';

INSERT INTO heasec_specsi_orders (product, amount, customer) VALUES
('安全渗透测试', 5000.00, 'admin'),
('漏洞扫描服务', 2000.00, 'zhangsan'),
('安全培训课程', 3000.00, 'lisi'),
('应急响应服务', 8000.00, 'admin'),
('代码审计服务', 4000.00, 'wangwu');

-- -----------------------------------------------------------
-- 账户信息表（第二关：宽字节注入）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_specsi_accounts;
CREATE TABLE heasec_specsi_accounts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    password VARCHAR(100) NOT NULL COMMENT '密码',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账户信息表（第二关：宽字节注入）';

INSERT INTO heasec_specsi_accounts (username, password, email, role) VALUES
('admin', '', 'admin@heasec.com', 'admin'),
('test', 'test123', 'test@heasec.com', 'user'),
('user1', 'user111', 'user1@test.com', 'user'),
('user2', 'user222', 'user2@test.com', 'user'),
('guest', 'guest00', 'guest@test.com', 'user');

-- -----------------------------------------------------------
-- 商品表（第二关：宽字节注入触发点）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_specsi_products;
CREATE TABLE heasec_specsi_products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '商品名称',
    price DECIMAL(10,2) DEFAULT NULL COMMENT '价格',
    stock INT UNSIGNED DEFAULT 0 COMMENT '库存'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表（第二关）';

INSERT INTO heasec_specsi_products (name, price, stock) VALUES
('天积元宝', 100.00, 50),
('天积小元宝', 50.00, 100),
('天积铜钱', 10.00, 200),
('天积令牌', 200.00, 30),
('天积护身符', 150.00, 20);

-- -----------------------------------------------------------
-- 员工信息表（第三关：双URL编码绕过）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_specsi_employees;
CREATE TABLE heasec_specsi_employees (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    password VARCHAR(100) NOT NULL COMMENT '密码',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='员工信息表（第三关：双URL编码绕过）';

INSERT INTO heasec_specsi_employees (username, password, email, role) VALUES
('admin', '', 'admin@heasec.com', 'admin'),
('test', 'test123', 'test@heasec.com', 'user'),
('dev01', 'dev1944', 'dev01@test.com', 'user'),
('ops01', 'ops8283', 'ops01@test.com', 'user'),
('sec01', 'sec3920', 'sec01@test.com', 'user');

-- -----------------------------------------------------------
-- 安全日志表（第三关：双URL编码绕过触发点）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_specsi_logs;
CREATE TABLE heasec_specsi_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(50) NOT NULL COMMENT '日志类型',
    message VARCHAR(255) NOT NULL COMMENT '日志内容'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='安全日志表（第三关）';

INSERT INTO heasec_specsi_logs (log_type, message) VALUES
('登录日志', '用户admin于08:30登录系统'),
('操作日志', '用户test查看了商品列表'),
('告警日志', '检测到异常登录尝试'),
('系统日志', '系统备份任务于02:00完成'),
('审计日志', '管理员查看了安全审计报告');

-- 密码存储说明：
-- 所有关卡通关密码均存储在 config/ 目录下的文件中
-- L1: config/secret.php（键名: level1_pass）
-- L2: config/secret2.php（键名: level2_pass）
-- L3: config/secret3.php（键名: level3_pass）
-- admin用户密码首次访问页面时动态生成并UPDATE对应表的password字段
