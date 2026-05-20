-- ============================================================
-- HeaSec天积安全团队 - SQL盲注进阶靶场 - 数据库初始化脚本
-- 版本: v1.0.0
-- 数据库: heasec_sqli（与其他SQL靶场共享）
-- 表名前缀: heasec_bsiadv_
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- 订单表（第一关：比较符号绕过 + 布尔盲注）
DROP TABLE IF EXISTS heasec_bsiadv_orders;
CREATE TABLE heasec_bsiadv_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(50) NOT NULL COMMENT '订单号',
    product_name VARCHAR(100) NOT NULL COMMENT '商品名称',
    amount DECIMAL(10,2) DEFAULT NULL COMMENT '金额',
    status VARCHAR(20) DEFAULT 'pending' COMMENT '订单状态',
    order_secret VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码（首次访问时生成）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表（第一关）';

INSERT INTO heasec_bsiadv_orders (order_no, product_name, amount, status) VALUES
('ORD-001', '天积防火墙', 2999.00, 'completed'),
('ORD-002', '天积入侵检测', 5999.00, 'pending'),
('ORD-003', '天积日志分析', 1999.00, 'completed'),
('ORD-004', '天积漏洞扫描', 8999.00, 'shipped'),
('ORD-005', '天积安全评估', 3999.00, 'pending');

-- 成员表（第二关：逗号绕过 + 布尔盲注）
DROP TABLE IF EXISTS heasec_bsiadv_members;
CREATE TABLE heasec_bsiadv_members (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL COMMENT '成员ID',
    name VARCHAR(100) NOT NULL COMMENT '成员姓名',
    role VARCHAR(50) DEFAULT 'member' COMMENT '角色',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态',
    member_key VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码（首次访问时生成）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='成员表（第二关）';

INSERT INTO heasec_bsiadv_members (member_id, name, role, status) VALUES
('M-001', '张三', '审计员', 1),
('M-002', '李四', '管理员', 1),
('M-003', '王五', '审计员', 1),
('M-004', '赵六', '观察员', 0),
('M-005', '钱七', '审计员', 1);

-- 令牌表（第三关：判断语句绕过 + 盲注双模式）
DROP TABLE IF EXISTS heasec_bsiadv_tokens;
CREATE TABLE heasec_bsiadv_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    token_id VARCHAR(50) NOT NULL COMMENT '令牌ID',
    token_name VARCHAR(100) NOT NULL COMMENT '令牌名称',
    status VARCHAR(20) DEFAULT 'active' COMMENT '状态',
    token_value VARCHAR(50) NOT NULL DEFAULT '' COMMENT '通关密码（首次访问时生成）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='令牌表（第三关）';

INSERT INTO heasec_bsiadv_tokens (token_id, token_name, status) VALUES
('TK-001', 'API访问令牌', 'active'),
('TK-002', '管理后台令牌', 'active'),
('TK-003', '审计服务令牌', 'active'),
('TK-004', '监控系统令牌', 'inactive'),
('TK-005', '日志采集令牌', 'active');
