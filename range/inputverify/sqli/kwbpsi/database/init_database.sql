-- ============================================================
-- HeaSec天积安全团队 - SQL关键字过滤靶场 - 数据库初始化脚本
-- 版本: v1.0.0
-- 数据库: heasec_sqli
-- 表名前缀: heasec_kwbpsi_
-- 说明: 本脚本可重复执行（使用 DROP TABLE IF EXISTS）
-- 注意: 本脚本只创建本靶场的表，不影响同数据库下其他靶场的表
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- -----------------------------------------------------------
-- 商品信息表（第一关：关键字大小写/双写绕过）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_kwbpsi_goods;
CREATE TABLE heasec_kwbpsi_goods (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '商品名称',
    price DECIMAL(10,2) NOT NULL COMMENT '价格',
    stock INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '库存数量',
    secret_key VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品信息表（第一关）';

INSERT INTO heasec_kwbpsi_goods (name, price, stock, secret_key) VALUES
('笔记本电脑', 5999.00, 120, ''),
('机械键盘', 299.00, 350, ''),
('无线鼠标', 89.00, 500, ''),
('4K显示器', 2499.00, 80, ''),
('降噪耳机', 1299.00, 200, '');

-- -----------------------------------------------------------
-- 订单信息表（第二关：关键字内联注释绕过）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_kwbpsi_orders;
CREATE TABLE heasec_kwbpsi_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(50) NOT NULL COMMENT '订单号',
    customer VARCHAR(50) NOT NULL COMMENT '客户姓名',
    amount DECIMAL(10,2) NOT NULL COMMENT '订单金额',
    verify_code VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单信息表（第二关）';

INSERT INTO heasec_kwbpsi_orders (order_no, customer, amount, verify_code) VALUES
('ORD2025-0001', '张三', 299.00, ''),
('ORD2025-0002', '李四', 5898.00, ''),
('ORD2025-0003', '王五', 1299.00, ''),
('ORD2025-0004', '赵六', 89.00, ''),
('ORD2025-0005', '钱七', 3798.00, '');

-- -----------------------------------------------------------
-- 客户反馈表（第三关：多过滤组合绕过）
-- -----------------------------------------------------------
DROP TABLE IF EXISTS heasec_kwbpsi_feedback;
CREATE TABLE heasec_kwbpsi_feedback (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customer VARCHAR(50) NOT NULL COMMENT '客户姓名',
    content VARCHAR(200) NOT NULL COMMENT '反馈内容',
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '评分',
    auth_token VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户反馈表（第三关）';

INSERT INTO heasec_kwbpsi_feedback (customer, content, rating, auth_token) VALUES
('张三', '商品质量很好，物流很快', 5, ''),
('李四', '价格实惠，下次还会购买', 4, ''),
('王五', '包装有破损，但客服处理及时', 3, ''),
('赵六', '整体满意，希望多搞活动', 4, ''),
('孙七', '第一次购买，体验不错', 5, '');
