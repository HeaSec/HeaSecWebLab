-- ============================================================
-- HeaSec天积安全团队 - SQL注入综合实战靶场 - 数据库初始化脚本
-- 版本: v1.0.0
-- 数据库: heasec_sqli
-- 表名前缀: heasec_mixedsi_
-- ============================================================

CREATE DATABASE IF NOT EXISTS heasec_sqli DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
USE heasec_sqli;

-- 新闻表（第一关）
DROP TABLE IF EXISTS heasec_mixedsi_news;
CREATE TABLE heasec_mixedsi_news (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL COMMENT '新闻标题',
    content TEXT COMMENT '新闻内容',
    author VARCHAR(50) DEFAULT NULL COMMENT '发布者',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新闻表（第一关）';

INSERT INTO heasec_mixedsi_news (title, content, author) VALUES
('天积安全团队荣获年度最佳安全解决方案奖', '近日，天积安全团队凭借自主研发的HeaSec智能安全平台，在2024年度网络安全创新大会上荣获最佳安全解决方案奖。该平台采用了先进的威胁检测引擎和智能分析算法，能够实时识别并防御各类网络攻击。', '安全资讯部'),
('企业网络安全防护指南发布', '天积安全联合行业协会发布了《企业网络安全防护指南》，为企业提供了从基础设施安全到应用安全的全面防护框架。指南涵盖了网络隔离、访问控制、数据加密、漏洞管理等关键安全领域。', '安全研究部'),
('SQL注入攻击趋势分析报告', '天积安全威胁情报中心发布最新报告指出，SQL注入仍然是最常见的Web应用安全漏洞之一。报告分析了2024年上半年的攻击数据，发现攻击者越来越多地使用高级绕过技术和自动化工具进行攻击。', '威胁情报中心'),
('天积企业信息平台v3.0正式上线', '经过半年的研发和测试，天积企业信息平台v3.0正式上线运行。新版本优化了系统性能，增强了数据安全保护机制。平台集成了新闻中心、商品管理、订单管理等核心业务模块。', '技术研发部'),
('网络安全人才培养计划启动', '天积安全联合多所高校启动了"网络安全人才培养计划"，为在校学生提供实操培训和安全竞赛机会。计划首批覆盖10所高校，预计培养超过500名安全专业人才。', '人力资源部');

-- 用户表（第一关）
DROP TABLE IF EXISTS heasec_mixedsi_users;
CREATE TABLE heasec_mixedsi_users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    password VARCHAR(100) NOT NULL COMMENT '密码',
    role VARCHAR(20) NOT NULL DEFAULT 'user' COMMENT '角色',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表（第一关）';

INSERT INTO heasec_mixedsi_users (username, password, role) VALUES
('admin', '__PLACEHOLDER__', 'admin'),
('test', 'test123', 'user');

-- 商品表（第二关）
DROP TABLE IF EXISTS heasec_mixedsi_products;
CREATE TABLE heasec_mixedsi_products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '商品名称',
    price DECIMAL(10,2) DEFAULT NULL COMMENT '价格',
    stock INT UNSIGNED DEFAULT 0 COMMENT '库存',
    description TEXT COMMENT '商品描述',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态（1=上架，0=下架）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表（第二关）';

INSERT INTO heasec_mixedsi_products (name, price, stock, description, status) VALUES
('天积防火墙设备', 15000.00, 20, '企业级下一代防火墙，支持深度包检测和入侵防御', 1),
('天积安全网关', 8500.00, 50, '智能安全网关，集成VPN、流量审计和威胁检测功能', 1),
('天积漏洞扫描器', 12000.00, 15, '自动化漏洞扫描工具，支持Web应用和网络设备漏洞检测', 1),
('天积日志审计系统', 20000.00, 10, '集中式日志管理和审计系统，支持多数据源关联分析', 1),
('天积安全培训服务', 5000.00, 100, '企业网络安全意识培训服务，提供线上+线下混合式教学', 1),
('天积渗透测试工具包', 18000.00, 5, '专业级渗透测试工具套件，包含信息收集、漏洞利用和报告模块', 1);

-- 隐藏配置表（第二关 — 报错注入的攻击目标）
DROP TABLE IF EXISTS heasec_mixedsi_secret;
CREATE TABLE heasec_mixedsi_secret (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    secret_key VARCHAR(50) NOT NULL COMMENT '密钥名称',
    secret_value VARCHAR(100) NOT NULL COMMENT '密钥值',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='隐藏配置表（第二关）';

INSERT INTO heasec_mixedsi_secret (secret_key, secret_value) VALUES
('level2_passcode', '__PLACEHOLDER__'),
('system_config_master', 'cfg_heasec_v3_secure_key_x9mK2pL7');

-- 订单表（第三关）
DROP TABLE IF EXISTS heasec_mixedsi_orders;
CREATE TABLE heasec_mixedsi_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(50) NOT NULL COMMENT '订单号',
    status VARCHAR(20) DEFAULT 'pending' COMMENT '订单状态',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表（第三关）';

INSERT INTO heasec_mixedsi_orders (order_no, status) VALUES
('ORD-2024-0001', '已完成'),
('ORD-2024-0002', '处理中'),
('ORD-2024-0003', '已发货'),
('ORD-2024-0004', '待支付'),
('ORD-2024-0005', '已完成');
