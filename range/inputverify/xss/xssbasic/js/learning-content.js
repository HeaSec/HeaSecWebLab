/**
 * HeaSec天积安全团队 - XSS基础靶场学习内容配置
 * 版本: v1.0.0
 * 创建日期: 2025-12-28
 * 团队: 天积安全 (HeavenlySecret)
 */

// XSS学习内容配置
var XSSLearningContent = {
    // 反射型XSS学习内容
    reflected: {
        title: '反射型 XSS 学习内容',
        sections: [
            {
                heading: '漏洞原理',
                content: `
                    <p>反射型XSS（Reflected XSS）是最常见的XSS漏洞类型。攻击者构造包含恶意代码的URL，诱导用户点击。服务器接收到请求后，将用户输入的内容原样返回到页面中，导致恶意脚本在用户浏览器中执行。</p>
                    <p><strong>攻击流程：</strong></p>
                    <ol>
                        <li>攻击者构造恶意URL（例如：<code>?search=&lt;script&gt;alert(heasec)&lt;/script&gt;</code>）</li>
                        <li>诱导受害者点击该链接</li>
                        <li>服务器接收请求，将参数值直接输出到页面</li>
                        <li>浏览器解析HTML，执行其中的恶意脚本</li>
                    </ol>
                `
            },
            {
                heading: '代码分析',
                content: `
                    <p>在本靶场中，反射型XSS的漏洞代码位于 <code>index.php</code> 第251-262行：</p>
                    <pre><code>&lt;?php if ($searchResult !== ''): ?&gt;
    &lt;div class="search-result"&gt;
        &lt;div class="search-result-title"&gt;
            &lt;i class="fa fa-search"&gt;&lt;/i&gt;
            您搜索了：
        &lt;/div&gt;
        &lt;div class="result-content" id="xss-test-area" style="margin-top: 15px;"&gt;
            &lt;!-- （输出未过滤的代码） --&gt;
            &lt;?php echo $searchResult; ?&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;?php endif; ?&gt;</code></pre>
                    <p><strong>问题分析：</strong></p>
                    <ul>
                        <li>用户输入 <code>$_POST['search']</code> 被直接赋值给 <code>$searchResult</code></li>
                        <li>输出时未使用 <code>htmlspecialchars()</code> 进行HTML转义</li>
                        <li>如果输入 <code>&lt;script&gt;alert(heasec)&lt;/script&gt;</code>，会直接被解析为HTML标签并执行</li>
                    </ul>
                `
            },
            {
                heading: '拼接方式详解',
                content: `
                    <p>本靶场中，用户输入是通过以下方式"拼接"到页面中的：</p>
                    <pre><code>// 第1步：接收用户输入（未过滤）
$searchResult = $_POST['search'];

// 第2步：直接拼接到HTML中输出
&lt;div class="result-content"&gt;
    &lt;?php echo $searchResult; ?&gt;
&lt;/div&gt;</code></pre>
                    <p>当用户输入 <code>&lt;script&gt;alert(heasec)&lt;/script&gt;</code> 时，实际生成的HTML为：</p>
                    <pre><code>&lt;div class="result-content"&gt;
    &lt;script&gt;alert(heasec)&lt;/script&gt;
&lt;/div&gt;</code></pre>
                    <p>浏览器会识别 <code>&lt;script&gt;</code> 标签并执行其中的JavaScript代码。</p>
                `
            },
            {
                heading: '防御建议',
                content: `
                    <p><strong>1. HTML实体编码（推荐）</strong></p>
                    <pre><code>// 使用 htmlspecialchars() 函数进行转义
$searchOutput = htmlspecialchars($_POST['search'], ENT_QUOTES, 'UTF-8');
echo $searchOutput;</code></pre>
                    <p><strong>2. 框架提供的自动转义</strong></p>
                    <ul>
                        <li>现代PHP框架（如Laravel）会自动转义输出</li>
                        <li>使用模板引擎（如Twig、Smarty）的自动转义功能</li>
                    </ul>
                    <p><strong>3. 内容安全策略（CSP）</strong></p>
                    <pre><code>// 设置响应头
header("Content-Security-Policy: default-src 'self'; script-src 'self'");</code></pre>
                `
            }
        ]
    },

    // 存储型XSS学习内容
    stored: {
        title: '存储型 XSS 学习内容',
        sections: [
            {
                heading: '漏洞原理',
                content: `
                    <p>存储型XSS（Stored XSS）是最危险的XSS类型。攻击者提交的恶意代码被服务器持久化存储到数据库中。每当有用户访问包含该恶意数据的页面时，恶意脚本都会在用户浏览器中执行。</p>
                    <p><strong>攻击流程：</strong></p>
                    <ol>
                        <li>攻击者在留言板输入恶意脚本并提交</li>
                        <li>服务器将恶意内容存储到数据库</li>
                        <li>其他用户访问留言板页面</li>
                        <li>服务器从数据库读取留言并输出到页面</li>
                        <li>受害者浏览器执行恶意脚本</li>
                    </ol>
                    <p><strong>危害特点：</strong></p>
                    <ul>
                        <li>攻击载荷持久化存储，可反复触发</li>
                        <li>可攻击所有访问该页面的用户</li>
                        <li>常用于窃取cookie、会话劫持</li>
                    </ul>
                `
            },
            {
                heading: '代码分析',
                content: `
                    <p>在本靶场中，存储型XSS的漏洞代码位于 <code>level2.php</code> 第301-311行：</p>
                    <pre><code>&lt;?php foreach ($messages as $message): ?&gt;
    &lt;div class="message-item" style="background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:15px;"&gt;
        &lt;div class="message-content" style="word-break:break-all;"&gt;
            &lt;?php echo $message['content']; ?&gt;  &lt;!-- 漏洞点 --&gt;
        &lt;/div&gt;
        &lt;div class="message-time" style="color:#999; font-size:12px; margin-top:10px; text-align:right;"&gt;
            &lt;?php echo date('Y-m-d H:i:s', strtotime($message['created_at'])); ?&gt;
        &lt;/div&gt;
    &lt;/div&gt;
&lt;?php endforeach; ?&gt;</code></pre>
                    <p><strong>问题分析：</strong></p>
                    <ul>
                        <li>留言内容在存储时未过滤（第52-53行）</li>
                        <li>输出时同样未使用 <code>htmlspecialchars()</code> 转义</li>
                        <li>数据库中的恶意代码会被完整还原到HTML中</li>
                    </ul>
                    <pre><code>// 存储时的漏洞代码（第52-53行）
$stmt = $db-&gt;prepare("INSERT INTO heasec_xssbasic_messages (content) VALUES (?)");
$stmt-&gt;execute([$_POST['message']]);  // 未过滤直接存储</code></pre>
                `
            },
            {
                heading: '拼接方式详解',
                content: `
                    <p>存储型XSS的数据流转过程：</p>
                    <pre><code>// 第1步：用户提交留言（未过滤）
$_POST['message'] = "&lt;script&gt;steal()&lt;/script&gt;"

// 第2步：存储到数据库（未转义）
$stmt->execute([$_POST['message']]);

// 第3步：从数据库读取
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 第4步：输出到页面（未转义）
echo $message['content'];</code></pre>
                    <p>最终生成的HTML结构：</p>
                    <pre><code>&lt;div class="message-item"&gt;
    &lt;div class="message-content"&gt;
        &lt;script&gt;steal()&lt;/script&gt;  &lt;!-- 恶意代码被执行 --&gt;
    &lt;/div&gt;
&lt;/div&gt;</code></pre>
                `
            },
            {
                heading: '防御建议',
                content: `
                    <p><strong>1. 输出时转义（最有效）</strong></p>
                    <pre><code>// 使用 htmlspecialchars() 转义输出
echo htmlspecialchars($message['content'], ENT_QUOTES, 'UTF-8');</code></pre>
                    <p><strong>2. 输入时验证（辅助防御）</strong></p>
                    <pre><code>// 白名单过滤
$cleanMessage = strip_tags($_POST['message']);
// 或使用 HTML Purifier 等库进行严格过滤</code></pre>
                    <p><strong>3. 数据库层面防护</strong></p>
                    <ul>
                        <li>使用预处理语句防止SQL注入（注意：这不能防止XSS）</li>
                        <li>设置字段长度限制</li>
                    </ul>
                    <p><strong>4. HttpOnly Cookie</strong></p>
                    <pre><code>// 设置Cookie为HttpOnly，防止JavaScript窃取
session_set_cookie_params(['httponly' => true]);</code></pre>
                `
            }
        ]
    },

    // DOM型XSS学习内容
    dom: {
        title: 'DOM 型 XSS 学习内容',
        sections: [
            {
                heading: '漏洞原理',
                content: `
                    <p>DOM型XSS（DOM-based XSS）是一种特殊的XSS类型，其攻击载荷不需要经过服务器。漏洞完全由客户端JavaScript代码引起，通过操作DOM将用户输入插入到页面中。</p>
                    <p><strong>攻击流程：</strong></p>
                    <ol>
                        <li>攻击者构造包含恶意参数的URL（例如：<code>?username=&lt;img src=x onerror=alert(heasec)&gt;</code>）</li>
                        <li>受害者点击链接访问页面</li>
                        <li>页面JavaScript读取URL参数</li>
                        <li>使用 innerHTML 等方式将参数值插入DOM</li>
                        <li>浏览器解析HTML并执行恶意脚本</li>
                    </ol>
                    <p><strong>与反射型XSS的区别：</strong></p>
                    <ul>
                        <li>攻击载荷不会出现在服务器响应中</li>
                        <li>完全由客户端JavaScript引起</li>
                        <li>传统的服务器端检测无法发现</li>
                    </ul>
                `
            },
            {
                heading: '代码分析',
                content: `
                    <p>在本靶场中，DOM型XSS的漏洞代码位于 <code>level3.php</code> 第286-306行左右的主逻辑中：</p>
                    <pre><code>document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    var username = params.get('username');
    var welcomeContent = document.getElementById('welcome-content');
    
    if (username) {
        // 漏洞点：使用 fragment 直接插入用户输入，允许执行 script
        welcomeContent.innerHTML = '';
        var htmlString = '&lt;div&gt;欢迎使用，&lt;span style="color: #007bff; font-weight: bold;"&gt;' + username + '&lt;/span&gt;！&lt;/div&gt;';
        var fragment = document.createRange().createContextualFragment(htmlString);
        welcomeContent.appendChild(fragment);
    }
});</code></pre>
                    <p><strong>问题分析：</strong></p>
                    <ul>
                        <li>使用 <code>document.createRange().createContextualFragment()</code> 直接解析和插入带有用户输入的HTML</li>
                        <li>这是一种允许脚本执行的 DOM 插入方式</li>
                        <li>当参数包含 <code>&lt;script&gt;</code> 或事件处理器（如 <code>onerror</code>）时，会触发执行</li>
                    </ul>
                `
            },
            {
                heading: '拼接方式详解',
                content: `
                    <p>DOM型XSS的JavaScript拼接过程：</p>
                    <pre><code>// 第1步：从URL获取用户输入
var username = urlParams.get('username');

// 第2步：直接拼接到 innerHTML
welcomeContent.innerHTML = '欢迎你，' + username + '！';</code></pre>
                    <p>当URL为 <code>?username=&lt;img src=x onerror=alert(heasec)&gt;</code> 时：</p>
                    <pre><code>// 拼接后的HTML
&lt;div id="welcome-content"&gt;
    欢迎你，&lt;img src=x onerror=alert(heasec)&gt;！
&lt;/div&gt;</code></pre>
                    <p>浏览器解析时：</p>
                    <ol>
                        <li>尝试加载图片 <code>src="x"</code></li>
                        <li>加载失败触发 <code>onerror</code> 事件</li>
                        <li>执行 <code>alert(heasec)</code></li>
                    </ol>
                `
            },
            {
                heading: '防御建议',
                content: `
                    <p><strong>1. 使用 textContent 代替 innerHTML</strong></p>
                    <pre><code>// 安全方法：使用 textContent
welcomeContent.textContent = '欢迎你，' + username + '！';

// 或使用 createTextNode
var textNode = document.createTextNode('欢迎你，' + username + '！');
welcomeContent.appendChild(textNode);</code></pre>
                    <p><strong>2. 输入验证</strong></p>
                    <pre><code>// 白名单验证
if (/^[a-zA-Z0-9_]+$/.test(username)) {
    welcomeContent.textContent = '欢迎你，' + username + '！';
} else {
    welcomeContent.textContent = '欢迎你，访客！';
}</code></pre>
                    <p><strong>3. 使用现代框架</strong></p>
                    <ul>
                        <li>React、Vue等框架默认会对内容进行转义</li>
                        <li>避免使用 <code>v-html</code> 或 <code>dangerouslySetInnerHTML</code></li>
                    </ul>
                    <p><strong>4. URL编码</strong></p>
                    <pre><code>// 对URL参数进行解码后再验证
var username = decodeURIComponent(urlParams.get('username'));
username = username.replace(/[<>]/g, '');  // 移除危险字符</code></pre>
                `
            }
        ]
    }
};
