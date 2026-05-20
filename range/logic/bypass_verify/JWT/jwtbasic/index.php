<?php
/**
 * HeaSec天积安全团队 - JWT基础靶场
 * 版本: v1.0.0
 * 创建日期: 2026-03-02
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JWT基础靶场';
$rangeName = 'JWT基础';
$showVersion = false;
$showResetButton = false;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 定义常量允许访问公共组件
define('HEASEC_RANGE_ACCESS', true);

// 引入星星系统组件
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入自定义样式 -->
<link rel="stylesheet" href="./css/style.css">

<!-- 引入星星系统组件的CSS样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css">

<!-- 靶场主要内容 -->
<div class="heasec-container">

    <!-- 第一章节：JWT简介 -->
    <div class="collapsible-section expanded" id="section1">
        <div class="collapsible-header" onclick="toggleSection('section1')">
            <span class="toggle-text">
                <i class="fa fa-info-circle"></i> JWT简介
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-book"></i> 什么是JWT
            </div>
            <div class="chapter-description">
                JWT（JSON Web Token）是一种开放标准（RFC 7519），用于在各方之间安全传输信息的紧凑、URL安全的方式。让我们一起来了解JWT的基本概念。
            </div>

            <div class="code-display-section">
                <h4><i class="fa fa-lightbulb-o"></i> JWT的定义</h4>
                <div class="composition-explanation">
                    <ul>
                        <li><strong>JWT（JSON Web Token）</strong>是一种开放标准（RFC 7519）</li>
                        <li>用于在各方之间<strong>安全传输信息</strong>的紧凑、URL安全的方式</li>
                        <li>JWT是一个<strong>数字令牌</strong>，包含一组"声明"（Claims）</li>
                        <li>JWT是<strong>自包含的</strong>，携带了验证自身所需的所有信息</li>
                    </ul>
                </div>

                <h4 style="margin-top: 25px;"><i class="fa fa-cogs"></i> JWT工作原理解析</h4>
                <div class="jwt-workflow-container">
                    <div class="jwt-actor">
                        <i class="fa fa-laptop"></i>
                        <span>客户端<br>(Browser/App)</span>
                    </div>

                    <div class="jwt-processes">
                        <!-- 步骤1：发送凭证 -->
                        <div class="jwt-step forward">
                            <div class="jwt-step-number">1</div>
                            <div class="jwt-step-content">
                                <div class="jwt-step-header">
                                    <div class="jwt-step-title">发送认证凭证</div>
                                    <button class="jwt-step-toggle-btn" onclick="toggleJwtStep(this)" title="查看请求示例"><i class="fa fa-code"></i> 查看请求示例</button>
                                </div>
                                <p class="jwt-step-desc">发送账号和密码等信息到服务端</p>
                                <div class="jwt-step-detail">
                                    <pre><code>POST /api/login HTTP/1.1
Host: api.example.com
Content-Type: application/json

{"username": "test", "password": "123"}</code></pre>
                                </div>
                            </div>
                            <i class="fa fa-long-arrow-right jwt-arrow"></i>
                        </div>

                        <!-- 步骤2：签发JWT -->
                        <div class="jwt-step backward">
                            <i class="fa fa-long-arrow-left jwt-arrow"></i>
                            <div class="jwt-step-content">
                                <div class="jwt-step-header">
                                    <div class="jwt-step-title">验证成功并签发 JWT</div>
                                    <button class="jwt-step-toggle-btn" onclick="toggleJwtStep(this)" title="查看响应示例"><i class="fa fa-code"></i> 查看响应示例</button>
                                </div>
                                <p class="jwt-step-desc">生成包含用户信息的JWT（token）并返回</p>
                                <div class="jwt-step-detail">
                                    <pre><code>HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJoZWFzZWMuY29tIiwic3ViIjoidXNlcjEyMyIsInVzZXJuYW1lIjoiYWRtaW4iLCJyb2xlIjoiYWRtaW5pc3RyYXRvciIsImlhdCI6MTczNTYwMzIwMH0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c"
}</code></pre>
                                </div>
                            </div>
                            <div class="jwt-step-number">2</div>
                        </div>

                        <!-- 步骤3：携带JWT请求 -->
                        <div class="jwt-step forward" style="margin-top: 15px;">
                            <div class="jwt-step-number">3</div>
                            <div class="jwt-step-content">
                                <div class="jwt-step-header">
                                    <div class="jwt-step-title">携带 JWT 请求资源</div>
                                    <button class="jwt-step-toggle-btn" onclick="toggleJwtStep(this)" title="查看请求示例"><i class="fa fa-code"></i> 查看请求示例</button>
                                </div>
                                <p class="jwt-step-desc">在HTTP头 (Authorization: Bearer) 附带JWT</p>
                                <div class="jwt-step-detail">
                                    <pre><code>GET /api/profile HTTP/1.1
Host: api.example.com
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJoZWFzZWMuY29tIiwic3ViIjoidXNlcjEyMyIsInVzZXJuYW1lIjoiYWRtaW4iLCJyb2xlIjoiYWRtaW5pc3RyYXRvciIsImlhdCI6MTczNTYwMzIwMH0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c</code></pre>
                                </div>
                            </div>
                            <i class="fa fa-long-arrow-right jwt-arrow"></i>
                        </div>

                        <!-- 步骤4：验证并响应 -->
                        <div class="jwt-step backward">
                            <i class="fa fa-long-arrow-left jwt-arrow"></i>
                            <div class="jwt-step-content">
                                <div class="jwt-step-header">
                                    <div class="jwt-step-title">验证 JWT 响应内容</div>
                                    <button class="jwt-step-toggle-btn" onclick="toggleJwtStep(this)" title="查看响应示例"><i class="fa fa-code"></i> 查看响应示例</button>
                                </div>
                                <p class="jwt-step-desc">校验签名与有效期，返回受保护资源</p>
                                <div class="jwt-step-detail">
                                    <pre><code>HTTP/1.1 200 OK
Content-Type: application/json

{
  "username": "test",
  "role": "user"
}</code></pre>
                                </div>
                            </div>
                            <div class="jwt-step-number">4</div>
                        </div>
                    </div>

                    <div class="jwt-actor">
                        <i class="fa fa-server"></i>
                        <span>服务端<br>(Server)</span>
                    </div>
                </div>

                <h4 style="margin-top: 25px;"><i class="fa fa-users"></i> JWT的应用场景</h4>
                <div class="composition-explanation">
                    <ul>
                        <li><strong>认证授权</strong>：用户登录后服务器签发JWT，客户端后续请求携带JWT进行身份验证</li>
                        <li><strong>信息交换</strong>：在各方之间安全传输信息，可验证发送方身份</li>
                        <li><strong>SSO单点登录</strong>：多个系统共享一个JWT实现单点登录</li>
                        <li><strong>无状态API</strong>：RESTful API的身份认证</li>
                    </ul>
                </div>

                <h4><i class="fa fa-balance-scale"></i> JWT与传统Session认证对比</h4>
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>特性</th>
                            <th>JWT认证</th>
                            <th>Session认证</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>存储位置</td>
                            <td>客户端</td>
                            <td>服务器端</td>
                        </tr>
                        <tr>
                            <td>状态</td>
                            <td>无状态</td>
                            <td>有状态</td>
                        </tr>
                        <tr>
                            <td>可扩展性</td>
                            <td>易于水平扩展</td>
                            <td>需要Session共享</td>
                        </tr>
                        <tr>
                            <td>跨域支持</td>
                            <td>天然支持</td>
                            <td>需要额外配置</td>
                        </tr>
                        <tr>
                            <td>服务器压力</td>
                            <td>低</td>
                            <td>高（需存储Session）</td>
                        </tr>
                        <tr>
                            <td>安全性</td>
                            <td>无法主动失效</td>
                            <td>可随时销毁</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 第二章节：JWT三段式结构 -->
    <div class="collapsible-section" id="section2">
        <div class="collapsible-header" onclick="toggleSection('section2')">
            <span class="toggle-text">
                <i class="fa fa-puzzle-piece"></i> JWT三段式结构
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-sitemap"></i> Header.Payload.Signature
            </div>
            <div class="chapter-description">
                JWT由三部分组成，用点号（.）分隔。每一部分都有特定的作用和格式。
            </div>

            <div class="code-display-section">
                <!-- Header部分 -->
                <h4><i class="fa fa-file-code-o"></i> Header部分</h4>
                <pre class="static-code-block"><code>{
    <span class="attr-name">"alg"</span>: <span class="attr-value">"HS256"</span>,
    <span class="attr-name">"typ"</span>: <span class="attr-value">"JWT"</span>
}</code></pre>

                <table class="info-table">
                    <thead>
                        <tr>
                            <th>字段</th>
                            <th>含义</th>
                            <th>可选值</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>alg</code></td>
                            <td>签名算法</td>
                            <td>none、HS256、HS384、HS512、RS256、RS384、RS512、ES256等</td>
                        </tr>
                        <tr>
                            <td><code>typ</code></td>
                            <td>令牌类型</td>
                            <td>通常为JWT</td>
                        </tr>
                    </tbody>
                </table>

                <div class="example-explanation">
                    <h5><i class="fa fa-arrow-right"></i> Base64URL编码结果：</h5>
                    <pre class="static-code-block"
                        style="background: #2d3748;"><code><span class="jwt-header-part">eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9</span></code></pre>
                </div>

                <!-- Payload部分 -->
                <h4 style="margin-top: 30px;"><i class="fa fa-file-code-o"></i> Payload部分</h4>
                <pre class="static-code-block"><code>{
    <span class="attr-name">"iss"</span>: <span class="attr-value">"heasec.com"</span>,
    <span class="attr-name">"sub"</span>: <span class="attr-value">"user123"</span>,
    <span class="attr-name">"aud"</span>: <span class="attr-value">"client.heasec.com"</span>,
    <span class="attr-name">"exp"</span>: <span class="number">1735689600</span>,
    <span class="attr-name">"nbf"</span>: <span class="number">1735603200</span>,
    <span class="attr-name">"iat"</span>: <span class="number">1735603200</span>,
    <span class="attr-name">"jti"</span>: <span class="attr-value">"unique-token-id-123"</span>,
    <span class="attr-name">"username"</span>: <span class="attr-value">"admin"</span>,
    <span class="attr-name">"role"</span>: <span class="attr-value">"administrator"</span>
}</code></pre>

                <table class="info-table">
                    <thead>
                        <tr>
                            <th>字段</th>
                            <th>含义</th>
                            <th>示例值</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>iss</code></td>
                            <td>签发者（Issuer）</td>
                            <td>heasec.com</td>
                        </tr>
                        <tr>
                            <td><code>sub</code></td>
                            <td>主题（Subject）</td>
                            <td>user123</td>
                        </tr>
                        <tr>
                            <td><code>aud</code></td>
                            <td>受众（Audience）</td>
                            <td>client.heasec.com</td>
                        </tr>
                        <tr>
                            <td><code>exp</code></td>
                            <td>过期时间（Expiration）</td>
                            <td>Unix时间戳</td>
                        </tr>
                        <tr>
                            <td><code>nbf</code></td>
                            <td>生效时间（Not Before）</td>
                            <td>Unix时间戳</td>
                        </tr>
                        <tr>
                            <td><code>iat</code></td>
                            <td>签发时间（Issued At）</td>
                            <td>Unix时间戳</td>
                        </tr>
                        <tr>
                            <td><code>jti</code></td>
                            <td>JWT编号（JWT ID）</td>
                            <td>unique-token-id-123</td>
                        </tr>
                    </tbody>
                </table>

                <div class="security-warning">
                    <h5><i class="fa fa-exclamation-triangle"></i> 安全提示</h5>
                    <p>除了标准字段外，可以添加任意自定义字段（如<code>username</code>、<code>role</code>）。但<strong>不要在Payload中存储敏感信息</strong>，因为Base64URL可被解码！
                    </p>
                </div>

                <div class="example-explanation">
                    <h5><i class="fa fa-arrow-right"></i> Base64URL编码结果：</h5>
                    <pre class="static-code-block"
                        style="background: #2d3748;"><code><span class="jwt-payload-part">eyJpc3MiOiJoZWFzZWMuY29tIiwic3ViIjoidXNlcjEyMyIsImF1ZCI6ImNsaWVudC5oZWFzZWMuY29tIiwiZXhwIjoxNzM1Njg5NjAwLCJuYmYiOjE3MzU2MDMyMDAsImlhdCI6MTczNTYwMzIwMCwianRpIjoidW5pcXVlLXRva2VuLWlkLTEyMyIsInVzZXJuYW1lIjoiYWRtaW4iLCJyb2xlIjoiYWRtaW5pc3RyYXRvciJ9</span></code></pre>
                </div>

                <!-- Signature部分 -->
                <h4 style="margin-top: 30px;"><i class="fa fa-file-code-o"></i> Signature部分</h4>
                <div class="composition-explanation">
                    <h5><i class="fa fa-calculator"></i> 签名计算公式：</h5>
                    <pre class="static-code-block"><code>HMACSHA256(
    base64UrlEncode(header) + <span class="string">"."</span> + base64UrlEncode(payload),
    secret
)</code></pre>
                </div>

                <table class="info-table">
                    <thead>
                        <tr>
                            <th>算法类型</th>
                            <th>算法示例</th>
                            <th>签名方式</th>
                            <th>本靶场支持</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>none</td>
                            <td>none</td>
                            <td>无签名，不安全</td>
                            <td>✅ 支持</td>
                        </tr>
                        <tr>
                            <td>HMAC</td>
                            <td>HS256、HS384、HS512</td>
                            <td>使用共享密钥（对称加密）</td>
                            <td>✅ 支持</td>
                        </tr>
                        <tr>
                            <td>RSA</td>
                            <td>RS256、RS384、RS512</td>
                            <td>使用私钥签名，公钥验证</td>
                            <td>✅ 支持RS256</td>
                        </tr>
                        <tr>
                            <td>ECDSA</td>
                            <td>ES256、ES384、ES512</td>
                            <td>使用椭圆曲线密钥</td>
                            <td>❌ 暂不支持</td>
                        </tr>
                    </tbody>
                </table>

                <div class="security-warning">
                    <h5><i class="fa fa-shield"></i> 安全说明</h5>
                    <p>
                        • 签名用于验证Token未被篡改<br>
                        • 使用<code>none</code>算法的JWT不安全，可能被攻击者利用<br>
                        • HS256算法的密钥必须保密
                    </p>
                </div>

                <div class="example-explanation">
                    <h5><i class="fa fa-arrow-right"></i> Base64URL编码结果：</h5>
                    <pre class="static-code-block"
                        style="background: #2d3748;"><code><span class="jwt-signature-part">SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c</span></code></pre>
                </div>

                <!-- 完整JWT示例 -->
                <h4 style="margin-top: 30px;"><i class="fa fa-puzzle-piece"></i> 完整JWT示例</h4>
                <div class="jwt-token-display"
                    style="background: #2d3748; border-radius: 8px; padding: 15px; font-family: 'Consolas', monospace; font-size: 12px; overflow-x: auto; word-break: break-all;">
                    <span class="jwt-token-segment header-segment"
                        style="background: rgba(231, 76, 60, 0.3); color: #e74c3c; padding: 2px 4px; border-radius: 3px;">eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9</span><span
                        style="color: #95a5a6;">.</span><span class="jwt-token-segment payload-segment"
                        style="background: rgba(155, 89, 182, 0.3); color: #9b59b6; padding: 2px 4px; border-radius: 3px;">eyJpc3MiOiJoZWFzZWMuY29tIiwic3ViIjoidXNlcjEyMyJ9</span><span
                        style="color: #95a5a6;">.</span><span class="jwt-token-segment signature-segment"
                        style="background: rgba(52, 152, 219, 0.3); color: #3498db; padding: 2px 4px; border-radius: 3px;">SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c</span>
                </div>
                <div style="margin-top: 10px; font-size: 12px; color: #718096;">
                    <span style="margin-right: 20px;"><span
                            style="display: inline-block; width: 12px; height: 12px; background: #e74c3c; border-radius: 2px; margin-right: 4px;"></span>Header（红色）</span>
                    <span style="margin-right: 20px;"><span
                            style="display: inline-block; width: 12px; height: 12px; background: #9b59b6; border-radius: 2px; margin-right: 4px;"></span>Payload（紫色）</span>
                    <span><span
                            style="display: inline-block; width: 12px; height: 12px; background: #3498db; border-radius: 2px; margin-right: 4px;"></span>Signature（蓝色）</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 第三章节：Base64URL编码 -->
    <div class="collapsible-section" id="section3">
        <div class="collapsible-header" onclick="toggleSection('section3')">
            <span class="toggle-text">
                <i class="fa fa-code"></i> Base64URL编码
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-exchange"></i> Base64URL与标准Base64的区别
            </div>
            <div class="chapter-description">
                JWT使用Base64URL编码而不是标准Base64，以确保编码结果可以安全地在URL中使用。
            </div>

            <div class="code-display-section">
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>改造项</th>
                            <th>标准Base64</th>
                            <th>Base64URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>字符62</td>
                            <td><code>+</code></td>
                            <td><code>-</code></td>
                        </tr>
                        <tr>
                            <td>字符63</td>
                            <td><code>/</code></td>
                            <td><code>_</code></td>
                        </tr>
                        <tr>
                            <td>填充字符</td>
                            <td><code>=</code></td>
                            <td>移除</td>
                        </tr>
                    </tbody>
                </table>

                <h4><i class="fa fa-file-code-o"></i> 对比示例</h4>
                <pre class="static-code-block"><code><span class="comment">// 标准Base64:</span>
<span class="string">aHR0cDovL2V4YW1wbGUuY29tL3BhdGg/YT0xJmI9Mg==</span>

<span class="comment">// Base64URL:</span>
<span class="string">aHR0cDovL2V4YW1wbGUuY29tL3BhdGg_YT0xJmI9Mg</span></code></pre>

                <div class="example-explanation">
                    <h5><i class="fa fa-search"></i> 差异高亮说明：</h5>
                    <ul>
                        <li><code>+</code> → <code>-</code> <span
                                style="background: rgba(255, 193, 7, 0.3); padding: 1px 3px; border-radius: 2px;">（黄色高亮）</span>
                        </li>
                        <li><code>/</code> → <code>_</code> <span
                                style="background: rgba(40, 167, 69, 0.3); padding: 1px 3px; border-radius: 2px;">（绿色高亮）</span>
                        </li>
                        <li><code>=</code> 移除 <span
                                style="background: rgba(220, 53, 69, 0.3); text-decoration: line-through; padding: 1px 3px; border-radius: 2px;">（红色高亮删除线）</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="interactive-area">
                <h4><i class="fa fa-keyboard"></i> 编码实践区域</h4>
                <div class="form-group">
                    <label>输入文本</label>
                    <textarea id="base64UrlInput" class="form-control" placeholder="输入任意文本进行Base64URL编码练习"
                        style="height: 100px;"></textarea>
                </div>

                <div class="dual-column-layout">
                    <div class="column">
                        <h5><i class="fa fa-file"></i> 标准Base64</h5>
                        <div id="standardBase64Output" class="output-box" style="min-height: 60px;"><span
                                class="text-muted">等待输入...</span></div>
                    </div>
                    <div class="column">
                        <h5><i class="fa fa-link"></i> Base64URL</h5>
                        <div id="base64UrlOutput" class="output-box" style="min-height: 60px;"><span
                                class="text-muted">等待输入...</span></div>
                    </div>
                </div>

                <div class="learning-tip">
                    <i class="fa fa-lightbulb-o"></i>
                    <span>尝试输入包含 <code>+</code>、<code>/</code>、<code>=</code> 字符的文本，观察两种编码的差异！</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 第四章节：JWT编码器 -->
    <div class="collapsible-section" id="section4">
        <div class="collapsible-header" onclick="toggleSection('section4')">
            <span class="toggle-text">
                <i class="fa fa-lock"></i> JWT编码器
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-cogs"></i> JWT生成器
            </div>
            <div class="chapter-description">
                使用下面的工具生成JWT Token。修改Header、Payload或选择不同的签名算法，实时查看生成的JWT变化。
            </div>

            <div class="dual-column-layout">
                <!-- 左侧：编辑区域 -->
                <div class="column">
                    <div class="form-group">
                        <label>Header（JSON格式）</label>
                        <textarea id="jwtHeaderInput" class="form-control" style="height: 120px;">{
    "alg": "HS256",
    "typ": "JWT"
}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Payload（JSON格式）</label>
                        <textarea id="jwtPayloadInput" class="form-control" style="height: 180px;">{
    "iss": "heasec.com",
    "sub": "user123",
    "username": "admin",
    "role": "administrator",
    "iat": 1735603200
}</textarea>
                    </div>

                    <div class="form-group">
                        <label>签名算法</label>
                        <select id="jwtAlgorithmSelect" class="form-control">
                            <optgroup label="无签名">
                                <option value="none">none（不安全）</option>
                            </optgroup>
                            <optgroup label="HMAC对称加密">
                                <option value="HS256" selected>HS256（SHA-256）</option>
                                <option value="HS384">HS384（SHA-384）</option>
                                <option value="HS512">HS512（SHA-512）</option>
                            </optgroup>
                            <optgroup label="RSA非对称加密">
                                <option value="RS256">RS256（SHA-256）</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- HMAC配置 -->
                    <div id="hmacConfig" class="form-group">
                        <label>签名密钥（Secret）</label>
                        <input type="text" id="jwtSecretInput" class="form-control" value="secret"
                            placeholder="输入HMAC签名密钥">
                        <div class="hint">HMAC使用共享密钥进行签名和验证</div>
                    </div>

                    <!-- RSA配置 -->
                    <div id="rsaConfig" class="form-group" style="display: none;">
                        <label>私钥（PEM格式）</label>
                        <button type="button" id="generateRsaKeyBtn" class="btn btn-secondary"
                            style="margin-bottom: 10px;">
                            <i class="fa fa-key"></i> 生成密钥对
                        </button>
                        <textarea id="jwtPrivateKeyInput" class="form-control" style="height: 150px;"
                            placeholder="点击上方按钮生成密钥对，或粘贴PEM格式私钥"></textarea>

                        <label style="margin-top: 10px;">公钥（PEM格式）</label>
                        <textarea id="jwtPublicKeyInput" class="form-control" style="height: 120px;" readonly
                            placeholder="生成密钥对后显示公钥"></textarea>
                        <div class="hint">RSA使用私钥签名，公钥验证。可点击'生成密钥对'自动生成。</div>
                    </div>
                </div>

                <!-- 右侧：输出区域 -->
                <div class="column">
                    <div class="form-group">
                        <label>生成的JWT Token</label>
                        <div id="jwtTokenOutput" class="output-box" style="min-height: 100px;">
                            <span class="text-muted">生成中...</span>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="button" id="copyTokenBtn" class="btn btn-primary">
                            <i class="fa fa-copy"></i> 复制Token
                        </button>
                        <button type="button" id="sendToDecoderBtn" class="btn btn-success">
                            <i class="fa fa-arrow-right"></i> 发送到解码器
                        </button>
                    </div>

                    <div class="security-warning" style="margin-top: 20px;">
                        <h5><i class="fa fa-exclamation-triangle"></i> 注意事项</h5>
                        <p>
                            • <code>none</code>算法不进行签名，仅用于演示攻击场景<br>
                            • RSA密钥对在浏览器中生成，仅供学习使用<br>
                            • 生产环境请使用服务端生成和管理密钥
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 第五章节：JWT解码器 -->
    <div class="collapsible-section" id="section5">
        <div class="collapsible-header" onclick="toggleSection('section5')">
            <span class="toggle-text">
                <i class="fa fa-unlock"></i> JWT解码器
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-search"></i> JWT解析器
            </div>
            <div class="chapter-description">
                粘贴JWT Token进行解码，查看Header、Payload和Signature的内容。
            </div>

            <div class="interactive-area">
                <div class="form-group">
                    <label>JWT Token输入</label>
                    <textarea id="jwtDecodeInput" class="form-control tall" placeholder="粘贴JWT Token进行解码"></textarea>
                </div>

                <div class="btn-group">
                    <button type="button" id="loadExampleToken" class="btn btn-secondary">
                        <i class="fa fa-file-text"></i> 加载示例Token
                    </button>
                </div>

                <div class="triple-column-layout">
                    <div class="column">
                        <h5><i class="fa fa-file-code-o" style="color: #e74c3c;"></i> Header</h5>
                        <div id="decodedHeader" class="output-box json" style="min-height: 150px;"><span
                                class="text-muted">等待输入...</span></div>
                    </div>
                    <div class="column">
                        <h5><i class="fa fa-file-code-o" style="color: #9b59b6;"></i> Payload</h5>
                        <div id="decodedPayload" class="output-box json" style="min-height: 150px;"><span
                                class="text-muted">等待输入...</span></div>
                    </div>
                    <div class="column">
                        <h5><i class="fa fa-key" style="color: #3498db;"></i> Signature</h5>
                        <div id="decodedSignature" class="output-box" style="min-height: 150px;"><span
                                class="text-muted">等待输入...</span></div>
                    </div>
                </div>

                <div class="btn-group" style="margin-top: 20px;">
                    <button type="button" id="sendToEncoderBtn" class="btn btn-success">
                        <i class="fa fa-arrow-left"></i> 发送到编码器
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 第六章节：学习完成 -->
    <div class="collapsible-section" id="section6">
        <div class="collapsible-header" onclick="toggleSection('section6')">
            <span class="toggle-text">
                <i class="fa fa-graduation-cap"></i> 学习完成
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-check-circle"></i> 学习总结
            </div>

            <div class="composition-explanation"
                style="background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%); border-left-color: #4299e1;">
                <h5><i class="fa fa-star" style="color: #f59e0b;"></i> 恭喜你完成了JWT基础知识的学习！</h5>
                <p style="margin: 10px 0; color: #2d3748;">通过本靶场，你已经掌握了：</p>
                <ul>
                    <li>JWT的定义和应用场景</li>
                    <li>JWT的三段式结构（Header、Payload、Signature）</li>
                    <li>Base64URL编码与标准Base64的区别</li>
                    <li>JWT的编码和解码原理</li>
                    <li>使用工具进行JWT的生成和解析</li>
                </ul>
            </div>

            <div class="mastery-section">
                <div class="mastery-button-container">
                    <button type="button" class="heasec-mastery-btn" id="masteryBtn">
                        <i class="fa fa-check-circle"></i>
                        我已掌握
                    </button>
                </div>
                <p class="mastery-description">点击"我已掌握"按钮记录你的学习进度</p>
            </div>
        </div>
    </div>
</div>

<!-- 引入星星系统组件的JavaScript -->
<script src="<?php echo $commonBasePath; ?>components/star-system/js/heasec-congrats-modal.js"></script>

<!-- 配置变量 -->
<script>
    window.heasecConfig = {
        commonBasePath: '<?php echo $commonBasePath; ?>'
    };
</script>

<!-- 引入JWT功能脚本 -->
<script src="./js/jwt.js?v=v1.0.0"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>