<?php
/**
 * HeaSec天积安全团队 - 文件相关XSS靶场 - 第二关（PDF文件XSS）
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件相关XSS Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件相关XSS靶场 - 第二关';
$rangeName = '文件相关XSS';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入公共组件
require_once $commonBasePath . 'includes/database.php';
require_once __DIR__ . '/includes/HeaSec_SessionManager.php';

// 初始化数据库连接
try {
    $db = heasec_db('heasec_inputverify');

    // 初始化会话管理器
    HeaSec_SessionManager::init($db);

    // 处理文件上传
    $hasUpload = false;
    $uploadSuccess = false;
    $uploadMessage = '';
    $pdfPath = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['pdf_file'])) {
            $hasUpload = true;

            // 调用上传API进行验证
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/upload.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => new CURLFile($_FILES['pdf_file']['tmp_name'], $_FILES['pdf_file']['type'], $_FILES['pdf_file']['name']),
                'type' => 'pdf'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($result && $result['success']) {
                $uploadSuccess = true;
                $uploadMessage = '文件上传成功';
                $pdfPath = $result['file_path'];
            } else {
                $uploadSuccess = false;
                $uploadMessage = $result['message'] ?? '文件上传失败';
            }
        } elseif (isset($_POST['preview_existing'])) {
            $hasUpload = true;
            $uploadSuccess = true;
            $uploadMessage = '已加载现有文件进行预览';
            $pdfPath = trim($_POST['preview_existing']);
        }
    }

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $starCount = 0;
}

// 引入星星系统组件资源
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['js' => false]);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式 -->
<link rel="stylesheet" href="css/style.css">

<!-- 引入PDF.js库（版本4.1.392，存在字体加载代码注入漏洞） -->
<script src="js/lib/pdf.mjs" type="module"></script>

<!-- XSS弹窗检测系统 -->
<script>
    (function () {
        'use strict';

        // 保存原始弹窗函数
        var originalAlert = window.alert;

        var currentLevel = 2;
        var hasPassed = false;

        console.log('[HeaSec FileXSS] 第二关弹窗检测系统已初始化');

        // 自动通关
        function autoCompleteLevel() {
            if (hasPassed) return;
            hasPassed = true;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/complete_level.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            console.log('[HeaSec FileXSS] 通关成功');

                            // 更新星星数量
                            if (window.updateStarCount) {
                                window.updateStarCount(response.star_count);
                            }

                            // 显示下一关按钮，但不覆盖原有的上传按钮
                            var formActions = document.querySelector('.form-actions');
                            if (formActions && !formActions.querySelector('a[href="level3.php"]')) {
                                formActions.innerHTML += ' <a href="level3.php" class="tech-btn tech-btn-success" style="margin-left: 10px;"><i class="fa fa-arrow-right"></i> 下一关</a>';
                            }
                        }
                    } catch (e) {
                        console.log('[HeaSec FileXSS] 通关响应解析失败:', e);
                    }
                }
            };
            xhr.send('level=' + currentLevel);
        }

        // 添加提示消息到页面
        function addPageMessage(message, isSuccess) {
            var uploadForm = document.getElementById('uploadForm');
            if (!uploadForm) return;

            // 移除旧的提示消息
            var oldMsg = document.getElementById('xss-detection-message');
            if (oldMsg) {
                oldMsg.remove();
            }

            // 创建新的提示消息
            var msgDiv = document.createElement('div');
            msgDiv.id = 'xss-detection-message';
            msgDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
            msgDiv.style.marginTop = '15px';
            msgDiv.innerHTML = '<div><i class="fa fa-' + (isSuccess ? 'check-circle' : 'exclamation-triangle') + '"></i><strong>' + message + '</strong></div>';

            // 插入到表单后面
            uploadForm.parentNode.insertBefore(msgDiv, uploadForm.nextSibling);
        }

        // 重写alert函数
        window.alert = function (message) {
            console.log('[HeaSec FileXSS] 拦截到alert:', message);

            // PDF.js内部执行的JavaScript较难精确检测来源，只要触发alert即判定成功
            var successMsg = '成功实现了XSS注入攻击！';
            console.log('[HeaSec FileXSS] ' + successMsg);

            if (!hasPassed) {
                addPageMessage(successMsg, true);
                autoCompleteLevel();
            }

            // 调用原始弹窗
            return originalAlert.apply(this, arguments);
        };

        console.log('[HeaSec FileXSS] 弹窗拦截函数已设置');
    })();
</script>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 关卡区域 -->
    <div class="level-section">
        <div class="tech-container">
            <div class="tech-card">
                <div class="tech-card-header">
                    <h3>
                        <i class="fa fa-file-pdf-o"></i>
                        第二关 · 文档审核员
                    </h3>
                    <p class="level-description">"PDF文档预览功能已上线，支持各种版本的PDF文件。"</p>
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>触发alert弹窗
                    </div>

                    <!-- 文件上传表单 -->
                    <form method="POST" action="" enctype="multipart/form-data" class="tech-form" id="uploadForm">
                        <div class="form-group">
                            <label class="form-label">选择PDF文件</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required>
                                <div class="file-upload-info">
                                    <i class="fa fa-info-circle"></i>
                                    仅支持 .pdf 文件，最大 5MB
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="tech-btn tech-btn-primary">
                                <i class="fa fa-upload"></i> 上传并预览
                            </button>
                        </div>
                    </form>

                    <!-- 上传结果区域 -->
                    <?php if ($hasUpload): ?>
                        <div class="search-result">
                            <div class="search-result-title">
                                <i class="fa fa-file-pdf-o"></i>
                                上传结果：
                            </div>
                            <?php if ($uploadSuccess): ?>
                                <!-- 上传成功提示 -->
                                <div class="alert alert-success" style="margin-top: 15px;">
                                    <div>
                                        <i class="fa fa-check-circle"></i>
                                        <strong><?php echo htmlspecialchars($uploadMessage); ?></strong>
                                    </div>
                                </div>
                                <!-- PDF渲染区域 -->
                                <div id="xss-test-area" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                                    <div class="pdf-viewer-info">
                                        <i class="fa fa-info-circle"></i>
                                        正在使用PDF.js渲染文档...
                                    </div>
                                    <canvas id="pdf-canvas" style="max-width: 100%; border: 1px solid #ccc;"></canvas>
                                </div>
                            <?php else: ?>
                                <!-- 上传失败提示 -->
                                <div class="alert alert-danger" style="margin-top: 15px;">
                                    <div>
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong><?php echo htmlspecialchars($uploadMessage); ?></strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 文件列表管理区域 -->
                    <div class="file-manager-section" style="margin-top: 30px;">
                        <div class="search-result-title" style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <i class="fa fa-folder-open"></i>
                                已上传的文件
                            </div>
                            <button id="clearFilesBtn" class="tech-btn tech-btn-danger tech-btn-sm" style="padding: 5px 10px; font-size: 12px; display: none;">
                                <i class="fa fa-trash"></i> 清空全部
                            </button>
                        </div>
                        <div id="fileListContainer" style="margin-top: 15px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; display: none;">
                            <table class="tech-table" style="width: 100%; border-collapse: collapse; text-align: left; margin: 0;">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd;">文件名</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 100px;">大小</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 150px;">上传时间</th>
                                        <th style="padding: 10px; border-bottom: 1px solid #ddd; width: 160px; text-align: center;">操作</th>
                                    </tr>
                                </thead>
                                <tbody id="fileListBody">
                                    <!-- 动态加载的文件列表 -->
                                </tbody>
                            </table>
                        </div>
                        <div id="noFilesMsg" style="margin-top: 15px; padding: 20px; text-align: center; color: #888; background: #fdfdfd; border: 1px dashed #ddd; border-radius: 4px;">
                            暂无已上传的文件
                        </div>
                    </div>

                    <script>
                    (function() {
                        'use strict';
                        
                        function fetchFiles() {
                            var xhr = new XMLHttpRequest();
                            xhr.open('GET', 'api/list_files.php?t=' + Date.now(), true);
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4 && xhr.status === 200) {
                                    try {
                                        var res = JSON.parse(xhr.responseText);
                                        if (res.success) {
                                            renderFiles(res.files);
                                        }
                                    } catch(e) {
                                        console.error('Failed to parse file list', e);
                                    }
                                }
                            };
                            xhr.send();
                        }

                        function renderFiles(files) {
                            var container = document.getElementById('fileListContainer');
                            var noMsg = document.getElementById('noFilesMsg');
                            var tbody = document.getElementById('fileListBody');
                            var clearBtn = document.getElementById('clearFilesBtn');
                            
                            if (!files || files.length === 0) {
                                container.style.display = 'none';
                                clearBtn.style.display = 'none';
                                noMsg.style.display = 'block';
                                return;
                            }
                            
                            noMsg.style.display = 'none';
                            container.style.display = 'block';
                            clearBtn.style.display = 'inline-block';
                            tbody.innerHTML = '';
                            
                            files.forEach(function(f) {
                                var tr = document.createElement('tr');
                                tr.style.borderBottom = '1px solid #eee';
                                
                                var sizeKb = (f.size / 1024).toFixed(1) + ' KB';
                                var escapeHtml = function(s) {
                                    var div = document.createElement('div');
                                    div.innerText = s;
                                    return div.innerHTML;
                                };
                                var fileNameSafe = escapeHtml(f.name);
                                
                                var html = '<td style="padding: 10px; word-break: break-all;" title="' + fileNameSafe + '">' + fileNameSafe + '</td>';
                                html += '<td style="padding: 10px; color: #666;">' + sizeKb + '</td>';
                                html += '<td style="padding: 10px; color: #666; font-size: 13px;">' + f.time + '</td>';
                                html += '<td style="padding: 10px; width: 160px;">';
                                html += '  <div style="display: flex; justify-content: center; gap: 8px; align-items: center; width: 100%;">';
                                html += '    <button class="tech-btn btn-preview" data-path="' + f.path + '" style="margin: 0; padding: 6px 14px; font-size: 13px; font-weight: 500; background-color: #f8f9fa; color: #2c3e50; border: 1px solid #dcdfe6; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 4px;" onmouseover="this.style.color=\'#3498db\';this.style.borderColor=\'#c6e2ff\';this.style.backgroundColor=\'#ecf5ff\'" onmouseout="this.style.color=\'#2c3e50\';this.style.borderColor=\'#dcdfe6\';this.style.backgroundColor=\'#f8f9fa\'"><i class="fa fa-eye"></i> 预览</button>';
                                html += '    <button class="tech-btn btn-delete" data-name="' + escapeHtml(f.name) + '" style="margin: 0; padding: 6px 14px; font-size: 13px; font-weight: 500; background-color: #fff1f0; color: #f5222d; border: 1px solid #ffa39e; border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 4px;" onmouseover="this.style.backgroundColor=\'#ff4d4f\';this.style.color=\'#fff\';this.style.borderColor=\'#ff4d4f\'" onmouseout="this.style.backgroundColor=\'#fff1f0\';this.style.color=\'#f5222d\';this.style.borderColor=\'#ffa39e\'"><i class="fa fa-trash-o"></i> 删除</button>';
                                html += '  </div>';
                                html += '</td>';
                                
                                tr.innerHTML = html;
                                tbody.appendChild(tr);
                            });

                            // 绑定事件
                            var delBtns = tbody.querySelectorAll('.btn-delete');
                            delBtns.forEach(function(btn) {
                                btn.addEventListener('click', function() {
                                    var name = this.getAttribute('data-name');
                                    // 还原转义回原始文件名提交给API
                                    var textarea = document.createElement('textarea');
                                    textarea.innerHTML = name;
                                    name = textarea.value;

                                    if(confirm('确定删除此文件吗？')) {
                                        deleteFile(name);
                                    }
                                });
                            });

                            var prevBtns = tbody.querySelectorAll('.btn-preview');
                            prevBtns.forEach(function(btn) {
                                btn.addEventListener('click', function() {
                                    // 模拟重新上传当前文件触发PDF预览
                                    var path = this.getAttribute('data-path');
                                    if(window.pdfjsLib && typeof createAcrobatSandbox === 'function') {
                                        alert('因同页面安全限制导致 PDF 运行时隔离，不刷新执行会存在上下文污染。系统即将刷新本页面并加载选中文件...');
                                        // 修改当前页面的请求
                                        var form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = 'level2.php';
                                        
                                        var input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = 'preview_existing';
                                        input.value = path;
                                        form.appendChild(input);
                                        
                                        document.body.appendChild(form);
                                        form.submit();
                                    } else {
                                        var form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = 'level2.php';
                                        var input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = 'preview_existing';
                                        input.value = path;
                                        form.appendChild(input);
                                        document.body.appendChild(form);
                                        form.submit();
                                    }
                                });
                            });
                        }

                        function deleteFile(name) {
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', 'api/delete_file.php', true);
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4 && xhr.status === 200) {
                                    var res = JSON.parse(xhr.responseText);
                                    if(res.success){
                                        fetchFiles();
                                    } else {
                                        alert(res.message);
                                    }
                                }
                            };
                            xhr.send('filename=' + encodeURIComponent(name));
                        }

                        document.getElementById('clearFilesBtn').addEventListener('click', function() {
                            if(confirm('确定要清空所有已上传的文件吗？此操作不可逆！')) {
                                var xhr = new XMLHttpRequest();
                                xhr.open('POST', 'api/clear_files.php', true);
                                xhr.onreadystatechange = function() {
                                    if (xhr.readyState === 4 && xhr.status === 200) {
                                        fetchFiles();
                                        // 如果页面上有预览，可以刷新
                                        if(document.getElementById('pdf-canvas')) {
                                            window.location.href = 'level2.php';
                                        }
                                    }
                                };
                                xhr.send();
                            }
                        });

                        document.addEventListener('DOMContentLoaded', fetchFiles);
                        // 对有页面刷新的POST的情况，立即发起拉取
                        fetchFiles();

                    })();
                    </script>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- 引入模态框组件脚本 -->
<script src="js/modal.js?v=<?php echo $version; ?>"></script>

<!-- PDF.js渲染脚本 -->
<?php if ($hasUpload && $uploadSuccess && !empty($pdfPath)): ?>
<script type="module">
    import * as pdfjsLib from './js/lib/pdf.mjs';

    // 设置PDF.js worker路径
    pdfjsLib.GlobalWorkerOptions.workerSrc = './js/lib/pdf.worker.mjs';

    var pdfPath = '<?php echo $pdfPath; ?>';

    /**
     * 创建完整的Adobe Acrobat JavaScript API模拟环境
     * 这个模拟环境将PDF中的Acrobat JS API调用转换为浏览器原生调用
     */
    function createAcrobatSandbox() {
        // 创建app对象 - 模拟Acrobat应用程序对象
        var app = {
            // 弹窗方法
            alert: function(message, iconType, title) {
                console.log('[HeaSec FileXSS] app.alert:', String(message));
                return window.alert(String(message));
            },
            confirm: function(message) {
                return window.confirm(String(message));
            },
            prompt: function(message, defaultText) {
                return window.prompt(String(message), defaultText || '');
            },
            beep: function(type) {
                console.log('[HeaSec FileXSS] app.beep:', type);
            },

            // 导航方法
            launchURL: function(url, newFrame) {
                window.open(String(url), newFrame ? '_blank' : '_self');
            },
            execDialog: function(dialog) {
                console.log('[HeaSec FileXSS] app.execDialog');
                return 'ok';
            },
            execMenuItem: function(item) {
                console.log('[HeaSec FileXSS] app.execMenuItem:', item);
            },

            // 定时器方法
            setTimeOut: function(expr, milliseconds) {
                return setTimeout(typeof expr === 'string' ? function() { eval(expr); } : expr, milliseconds || 0);
            },
            clearTimeOut: function(id) {
                clearTimeout(id);
            },
            setInterval: function(expr, milliseconds) {
                return setInterval(typeof expr === 'string' ? function() { eval(expr); } : expr, milliseconds || 0);
            },
            clearInterval: function(id) {
                clearInterval(id);
            },

            // 邮件方法
            mailMsg: function(params) {
                console.log('[HeaSec FileXSS] app.mailMsg');
            },

            // 属性
            viewerVersion: 10.0,
            viewerType: 'PDF.js',
            platform: navigator.platform,
            language: navigator.language,
            runtimeHighlight: false,

            // 其他常用方法
            response: function(question, title, defaultValue, label, password) {
                return window.prompt(question, defaultValue || '');
            },
            browseForDoc: function(params) {
                console.log('[HeaSec FileXSS] app.browseForDoc');
                return null;
            },

            // 通用方法调用处理
            __noSuchMethod__: function(method, args) {
                console.log('[HeaSec FileXSS] app.' + method + ' called with:', args);
            }
        };

        // 创建util对象 - 工具函数
        var util = {
            printd: function(format, date) {
                return date instanceof Date ? date.toLocaleString() : String(date || new Date());
            },
            scand: function(format, dateStr) {
                return new Date(dateStr);
            },
            printf: function() {
                var args = Array.from(arguments);
                return args.shift().replace(/%[dsf]/g, function(m) {
                    return args.shift() || '';
                });
            },
            printx: function(mask, value) {
                return String(value || '');
            },
            iconStreamFromIcon: function(icon) {
                return null;
            },
            stringFromStream: function(stream) {
                return stream || '';
            },
            streamFromString: function(str) {
                return str || '';
            }
        };

        // 创建console对象模拟PDF环境下专有的控制台
        var acrobatConsole = {
            println: function(msg) {
                window.console.log('[PDF]', msg);
            },
            show: function() {},
            hide: function() {},
            clear: function() {}
        };

        // 创建event对象
        var event = {
            target: null,
            name: '',
            type: '',
            value: '',
            rc: true
        };

        return { app: app, util: util, console: acrobatConsole, event: event };
    }

    /**
     * 创建文档对象模拟
     */
    function createDocObject(pdf) {
        return {
            // 文档信息
            info: {
                title: 'PDF Document',
                author: 'Unknown',
                subject: '',
                keywords: '',
                creator: '',
                producer: 'PDF.js',
                creationDate: new Date(),
                modDate: new Date()
            },

            // 页面相关
            numPages: pdf.numPages,
            pageNum: 0,
            pageSize: function() { return [595, 842]; },

            // 页面方法
            getPageNumWords: function(nPage) { return 0; },
            getPageNthWord: function(nPage, nWord) { return ''; },
            getPageNthWordQuads: function(nPage, nWord) { return []; },

            // 字段方法
            getField: function(cName) {
                console.log('[HeaSec FileXSS] doc.getField:', cName);
                return null;
            },
            getNthFieldName: function(nIndex) { return ''; },
            numFields: 0,

            // 打印方法
            print: function(params) {
                console.log('[HeaSec FileXSS] doc.print');
            },

            // 导航方法
            gotoNamedDest: function(name) {
                console.log('[HeaSec FileXSS] doc.gotoNamedDest:', name);
            },

            // 其他方法
            calculate: true,
            delay: false,
            dirty: false,
            resetForm: function(aFields) {
                console.log('[HeaSec FileXSS] doc.resetForm');
            },
            submitForm: function(params) {
                console.log('[HeaSec FileXSS] doc.submitForm');
            },
            mailDoc: function(params) {
                console.log('[HeaSec FileXSS] doc.mailDoc');
            },
            saveAs: function(params) {
                console.log('[HeaSec FileXSS] doc.saveAs');
            },
            exportAsFDF: function(params) {
                console.log('[HeaSec FileXSS] doc.exportAsFDF');
            },
            importAnFDF: function(cPath) {
                console.log('[HeaSec FileXSS] doc.importAnFDF');
            },
            addWatermarkFromFile: function(params) {
                console.log('[HeaSec FileXSS] doc.addWatermarkFromFile');
            },

            // 书签方法
            bookmarkRoot: null,

            // JavaScript方法
            addScript: function(cName, cScript) {
                console.log('[HeaSec FileXSS] doc.addScript:', cName);
            }
        };
    }

    /**
     * 执行PDF JavaScript代码
     */
    function executePdfJS(jsCode, source, sandbox, doc) {
        if (!jsCode) return;
        console.log('[HeaSec FileXSS] 执行PDF JavaScript (' + source + '):', jsCode);

        try {
            // 创建执行上下文
            var fn = new Function(
                'app', 'util', 'console', 'event', 'doc',
                'Color', 'global', 'spell',
                jsCode
            );

            // 创建简单的全局对象
            var Color = {
                black: ['G', 0],
                white: ['G', 1],
                red: ['RGB', 1, 0, 0],
                green: ['RGB', 0, 1, 0],
                blue: ['RGB', 0, 0, 1],
                transparent: ['T']
            };

            var global = {};
            var spell = {
                check: function() { return true; },
                dictionaryNames: []
            };

            // 执行
            fn.call(doc,
                sandbox.app,
                sandbox.util,
                sandbox.console,
                sandbox.event,
                doc,
                Color,
                global,
                spell
            );
        } catch (e) {
            console.error('[HeaSec FileXSS] JavaScript执行错误:', e);
        }
    }

    // 创建沙箱环境
    var sandbox = createAcrobatSandbox();

    // 加载PDF文件 - 启用JavaScript解析
    var loadingTask = pdfjsLib.getDocument({
        url: pdfPath,
        enableScripting: true,
        isEvalSupported: true,
        useWorkerFetch: false,
        useSystemFonts: true
    });

    loadingTask.promise.then(function(pdf) {
        console.log('[HeaSec FileXSS] PDF加载成功，共 ' + pdf.numPages + ' 页');

        // 创建文档对象
        var doc = createDocObject(pdf);
        doc.numPages = pdf.numPages;

        // 获取并执行PDF的OpenAction
        pdf.getOpenAction().then(function(openAction) {
            console.log('[HeaSec FileXSS] OpenAction:', openAction);
            if (openAction) {
                if (Array.isArray(openAction)) {
                    openAction.forEach(function(action) {
                        var js = action.jsCode || action.action || (typeof action === 'string' ? action : null);
                        if (js) {
                            executePdfJS(js, 'OpenAction', sandbox, doc);
                        } else {
                            console.log('[HeaSec FileXSS] 未识别的OpenAction数据结构:', action);
                        }
                    });
                } else {
                    var js = openAction.jsCode || openAction.action || (typeof openAction === 'string' ? openAction : null);
                    if (js) {
                        executePdfJS(js, 'OpenAction', sandbox, doc);
                    } else {
                        console.log('[HeaSec FileXSS] 未识别的OpenAction数据结构:', openAction);
                    }
                }
            }
        }).catch(function(e) {
            console.log('[HeaSec FileXSS] 获取OpenAction失败:', e);
        });

        // 获取文档级JavaScript动作
        pdf.getJSActions().then(function(jsActions) {
            console.log('[HeaSec FileXSS] JSActions:', jsActions);
            if (jsActions) {
                Object.keys(jsActions).forEach(function(key) {
                    var action = jsActions[key];
                    if (Array.isArray(action)) {
                        action.forEach(function(item) {
                            var js = item.jsCode || item.action || (typeof item === 'string' ? item : null);
                            if (js) executePdfJS(js, 'DocJS-' + key, sandbox, doc);
                        });
                    } else {
                        var js = action.jsCode || action.action || (typeof action === 'string' ? action : null);
                        if (js) executePdfJS(js, 'DocJS-' + key, sandbox, doc);
                    }
                });
            }
        }).catch(function(e) {
            console.log('[HeaSec FileXSS] 获取JSActions失败:', e);
        });

        // 获取第一页
        return pdf.getPage(1);
    }).then(function(page) {
        var scale = 1.5;
        var viewport = page.getViewport({ scale: scale });

        // 准备canvas
        var canvas = document.getElementById('pdf-canvas');
        var context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        // 渲染PDF页面
        var renderContext = {
            canvasContext: context,
            viewport: viewport
        };

        return page.render(renderContext).promise;
    }).then(function() {
        console.log('[HeaSec FileXSS] PDF渲染完成');
    }).catch(function(error) {
        console.error('[HeaSec FileXSS] PDF加载/渲染错误:', error);
    });


</script>
<?php endif; ?>

<!-- 前端验证脚本 -->
<script>
    (function () {
        'use strict';

        var uploadForm = document.getElementById('uploadForm');
        var fileInput = document.getElementById('pdf_file');

        if (!uploadForm) return;

        // 表单提交验证
        uploadForm.addEventListener('submit', function (e) {
            var file = fileInput.files[0];

            if (!file) {
                e.preventDefault();
                HeaSecModal.showError('上传错误', '请选择PDF文件');
                return false;
            }

            // 验证文件类型
            var fileName = file.name.toLowerCase();
            if (!fileName.endsWith('.pdf')) {
                e.preventDefault();
                HeaSecModal.showError('上传错误', '请选择有效的PDF文件');
                return false;
            }

            // 显示提交中状态
            var submitBtn = uploadForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                var originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 上传中...';

                // 10秒后恢复按钮状态
                setTimeout(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    })();
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
