/**
 * HeaSec天积安全团队 - 在线代码编辑器公共组件脚本
 * Code Editor Common Component JavaScript
 * 版本: v1.2.6
 * 创建日期: 2025-11-30
 * 更新日期: 2025-12-30
 * 团队: 天积安全 (HeavenlySecret)
 * 修复：修复预览窗口跳转到第三方页面后，点击运行代码需要点击两次的问题
 *       改用iframe的load事件确保完全加载后再写入内容
 */

(function(window) {
    'use strict';

    // HeaSecCodeEditor 对象
    var HeaSecCodeEditor = {
        // 存储编辑器实例
        editors: {},
        
        // 默认配置
        defaultConfig: {
            height: '400px',
            fontSize: '14px',
            theme: 'light',
            syntaxHighlighting: true,
            languages: ['html', 'css', 'javascript'],
            defaultLanguage: 'html',
            defaultCode: {},
            layout: 'horizontal',
            splitRatio: '50:50',
            autoHeight: true,
            minHeight: 200,
            maxHeight: 800
        },

        /**
         * 防抖函数 - 新增工具函数
         */
        _debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * 初始化代码编辑器
         */
        init: function(componentId, config) {
            var self = this;
            
            // 合并配置
            var editorConfig = Object.assign({}, this.defaultConfig, config);
            
            // 存储配置
            this.editors[componentId] = {
                config: editorConfig,
                isInitialized: false,
                contentHeight: 200,
                lineCount: 1  // 存储当前行数
            };
            
            // 等待DOM完全加载
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    self._initializeEditor(componentId);
                });
            } else {
                this._initializeEditor(componentId);
            }
        },
        
        /**
         * 初始化编辑器
         */
        _initializeEditor: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor || editor.isInitialized) return;

            var textarea = document.getElementById(editor.config.editorId);
            if (!textarea) return;

            // 设置默认代码（混合HTML/CSS/JavaScript）
            this._setMixedDefaultCode(componentId);

            // 设置事件监听器
            this._setupEventListeners(componentId);

            // 初始化语法高亮
            if (editor.config.syntaxHighlighting) {
                this._updateSyntaxHighlighting(componentId);
            }

            // 初始化自动高度
            if (editor.config.autoHeight) {
                var self = this;
                // 使用setTimeout确保DOM完全渲染后再计算高度
                setTimeout(function() {
                    self._adjustHeight(componentId);
                }, 50);
            }

            // 初始化行号
            this._updateLineNumbers(componentId);

            // 初始化预览
            this._initializePreview(componentId);

            // 延迟检测垂直滚动条状态，确保DOM完全渲染
            var self = this;
            setTimeout(function() {
                self._detectVerticalScrollbar(componentId);
            }, 100);

            editor.isInitialized = true;

            // 触发初始化完成事件
            this._triggerEvent(componentId, 'initialized');
        },

        /**
         * 设置混合默认代码
         */
        _setMixedDefaultCode: function(componentId) {
            var editor = this.editors[componentId];
            var textarea = document.getElementById(editor.config.editorId);
            if (!editor || !textarea) return;

            // 如果textarea已经有内容（由PHP设置），则不需要覆盖
            if (textarea.value.trim() !== '') {
                return;
            }

            // 如果配置中有默认代码，使用混合代码
            if (editor.config.defaultCode && typeof editor.config.defaultCode === 'string') {
                textarea.value = editor.config.defaultCode;
            } else if (editor.config.defaultCode && editor.config.defaultCode.html) {
                // 如果是分离的语言代码，构建混合代码
                var htmlCode = editor.config.defaultCode.html || '';
                var cssCode = editor.config.defaultCode.css || '';
                var jsCode = editor.config.defaultCode.javascript || '';
                textarea.value = this._buildMixedCode(htmlCode, cssCode, jsCode);
            } else {
                // 默认混合代码示例
                textarea.value = '<!DOCTYPE html>\n<html>\n<head>\n    <title>示例页面</title>\n    <style>\n        body {\n            font-family: Arial, sans-serif;\n            margin: 20px;\n            background: linear-gradient(135deg, #667eea, #764ba2);\n            color: white;\n        }\n    </style>\n</head>\n<body>\n    <h1>欢迎使用混合语法编辑器</h1>\n    <p>支持HTML、CSS和JavaScript同时高亮</p>\n    <script>\n        console.log("Hello, World!");\n    </script>\n</body>\n</html>';
            }
        },

        /**
         * 构建混合代码
         */
        _buildMixedCode: function(htmlCode, cssCode, jsCode) {
            var mixedCode = '';

            if (cssCode) {
                mixedCode += '<style>\n' + cssCode + '\n</style>\n';
            }

            if (htmlCode) {
                mixedCode += htmlCode + '\n';
            }

            if (jsCode) {
                mixedCode += '<script>\n' + jsCode + '\n</script>\n';
            }

            return mixedCode;
        },
        
        /**
         * 设置事件监听器 - 增强版本
         */
        _setupEventListeners: function(componentId) {
            var self = this;
            var editor = this.editors[componentId];
            var textarea = document.getElementById(editor.config.editorId);

            if (!textarea) return;

            // 添加防抖的行号更新函数
            var debouncedUpdateLineNumbers = this._debounce(function() {
                self._updateLineNumbers(componentId);
            }, 50);

            // 输入事件
            textarea.addEventListener('input', function() {
                if (editor.config.syntaxHighlighting) {
                    self._updateSyntaxHighlighting(componentId);
                }
                if (editor.config.autoHeight) {
                    self._adjustHeight(componentId);
                }
                // 使用防抖的行号更新
                debouncedUpdateLineNumbers();

                // 防抖检测垂直滚动条状态
                setTimeout(function() {
                    self._detectVerticalScrollbar(componentId);
                }, 50);
            });
            
            // 滚动事件 - 同步滚动（增强防抖机制）
            var scrollTimeout = null;
            textarea.addEventListener('scroll', function() {
                // 立即同步滚动
                self._syncScroll(componentId);

                // 防抖处理 - 避免过度频繁调用
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(function() {
                    self._syncScroll(componentId);
                }, 16); // 约60fps
            });
            
            // 键盘事件
            textarea.addEventListener('keydown', function(e) {
                self._handleKeyDown(componentId, e);
            });
            
            // 窗口大小改变事件（增强防抖）
            var resizeTimeout = null;
            window.addEventListener('resize', function() {
                if (resizeTimeout) {
                    clearTimeout(resizeTimeout);
                }
                resizeTimeout = setTimeout(function() {
                    self._handleResize(componentId);
                }, 100);
            });
            
            // 粘贴事件
            textarea.addEventListener('paste', function(e) {
                setTimeout(function() {
                    if (editor.config.autoHeight) {
                        self._adjustHeight(componentId);
                    }
                    // 更新行号（使用防抖）
                    debouncedUpdateLineNumbers();
                }, 10);
            });
        },

        /**
         * 检测编辑器是否有垂直滚动条（基于明确条件）
         */
        _detectVerticalScrollbar: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return false;

            var textarea = document.getElementById(editor.config.editorId);
            if (!textarea) return false;

            // 明确的垂直滚动条判断条件
            var hasVerticalScrollbar = false;

            // 方法1：检查内容高度
            if (textarea.scrollHeight > textarea.clientHeight) {
                hasVerticalScrollbar = true;
            }

            // 方法2：基于行数判断（每行21px高度，编辑器最小高度400px）
            var code = textarea.value;
            var lineCount = code.split('\n').length;
            var maxLinesInEditor = Math.floor(400 / 21); // 400px高度，每行21px，约19行

            if (lineCount > maxLinesInEditor) {
                hasVerticalScrollbar = true;
            }

            // 获取编辑器主容器元素
            var editorElement = document.getElementById(componentId);
            if (!editorElement) return hasVerticalScrollbar;

            // 根据滚动条状态添加或移除CSS类
            if (hasVerticalScrollbar) {
                editorElement.classList.remove('no-vertical-scroll');
            } else {
                editorElement.classList.add('no-vertical-scroll');
            }

            return hasVerticalScrollbar;
        },

        /**
         * 调整编辑器高度以适应内容
         */
        _adjustHeight: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return;

            var textarea = document.getElementById(editor.config.editorId);
            var highlightElement = document.getElementById(editor.config.editorId + '_highlight');
            var lineNumbersElement = document.getElementById(editor.config.editorId + '_linenumbers');
            var container = document.querySelector('#' + componentId + ' .heasec-code-editor-container');
            var wrapper = document.querySelector('#' + componentId + ' .heasec-code-editor-wrapper');
            var previewContent = document.querySelector('#' + componentId + ' .heasec-preview-content');

            if (!textarea || !highlightElement || !container || !wrapper) return;

            // 只有启用自动高度时才调整高度
            if (editor.config.autoHeight) {
                // 临时重置高度以计算实际内容高度
                var originalHeight = textarea.style.height;
                textarea.style.height = 'auto';
                textarea.style.overflowY = 'hidden';
                textarea.style.overflowX = 'auto';

                // 计算滚动高度，限制最大高度
                var scrollHeight = textarea.scrollHeight;
                var newHeight = Math.max(editor.config.minHeight, scrollHeight);
                newHeight = Math.min(editor.config.maxHeight, newHeight);

                // 设置新高度
                var finalHeight = newHeight + 'px';
                textarea.style.height = finalHeight;
                highlightElement.style.height = finalHeight;

                // 同步行号区域高度 - 简化版本：使用CSS处理对齐
                if (lineNumbersElement) {
                    lineNumbersElement.style.height = finalHeight;
                }

                // 同步预览内容区域高度，确保在expanded状态下正常显示
                if (previewContent) {
                    previewContent.style.height = finalHeight;
                    // 强制设置minHeight，避免被CSS覆盖
                    previewContent.style.minHeight = finalHeight;
                }

                // 设置容器高度为auto，让其自适应内容
                container.style.height = 'auto';
                wrapper.style.height = finalHeight;

                // 保存当前高度
                editor.contentHeight = newHeight;

                // 如果内容超出高度，启用滚动
                if (scrollHeight > newHeight) {
                    textarea.style.overflowY = 'auto';
                    textarea.style.overflowX = 'auto';
                    highlightElement.style.overflowY = 'auto';
                    highlightElement.style.overflowX = 'auto';
                    if (lineNumbersElement) {
                        lineNumbersElement.style.overflowY = 'auto';
                    }
                } else {
                    textarea.style.overflowY = 'hidden';
                    textarea.style.overflowX = 'auto';
                    highlightElement.style.overflowY = 'hidden';
                    highlightElement.style.overflowX = 'auto';
                    if (lineNumbersElement) {
                        lineNumbersElement.style.overflowY = 'auto'; // 行号始终允许滚动
                    }
                }
            } else {
                // 固定高度模式：使用配置的固定高度
                var fixedHeight = editor.config.height || '400px';
                var heightValue = typeof fixedHeight === 'number' ? fixedHeight + 'px' : fixedHeight;

                // 确保固定高度不被覆盖
                textarea.style.height = heightValue;
                highlightElement.style.height = heightValue;

                // 同步行号区域高度 - 简化版本：使用CSS处理对齐
                if (lineNumbersElement) {
                    lineNumbersElement.style.height = heightValue;
                }

                // 同步预览内容区域高度，确保在expanded状态下正常显示
                if (previewContent) {
                    previewContent.style.height = heightValue;
                    // 强制设置minHeight，避免被CSS覆盖
                    previewContent.style.minHeight = heightValue;
                }

                // 启用滚动
                textarea.style.overflowY = 'auto';
                textarea.style.overflowX = 'auto';
                highlightElement.style.overflowY = 'auto';
                highlightElement.style.overflowX = 'auto';
                if (lineNumbersElement) {
                    lineNumbersElement.style.overflowY = 'auto';
                }

                // 更新容器高度
                container.style.height = heightValue;
                wrapper.style.height = heightValue;

                // 保存当前高度
                editor.contentHeight = parseInt(heightValue, 10);
            }

            // 检测并处理垂直滚动条状态
            this._detectVerticalScrollbar(componentId);

            // 同步高亮元素滚动位置
            this._syncScroll(componentId);
        },
        
        /**
         * 更新语法高亮
         */
        _updateSyntaxHighlighting: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor || !editor.config.syntaxHighlighting) return;

            var textarea = document.getElementById(editor.config.editorId);
            var highlightElement = document.getElementById(editor.config.editorId + '_highlight');

            if (!textarea || !highlightElement) return;

            var code = textarea.value;
            // 使用正确的语法高亮函数（先转义HTML字符，再应用高亮）
            var highlightedCode = this._syntaxHighlightMixed(code);

            highlightElement.querySelector('code').innerHTML = highlightedCode;
        },
        
        /**
         * 混合语法高亮处理 - 同时支持HTML/CSS/JavaScript
         */
        _syntaxHighlightMixed: function(code) {
            // 先转义HTML特殊字符
            var escaped = code
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            // 然后应用混合语法高亮（处理已转义的代码）
            var highlighted = this._highlightMixedSyntax(escaped);

            // 按行分割，保留换行符，但因为使用pre-wrap，换行会被自动处理
            var lines = highlighted.split('\n');
            var highlightedLines = lines.map(function(line) {
                return '<div class="line">' + (line || '&nbsp;') + '</div>';
            });

            return highlightedLines.join('');
        },

        /**
         * 混合语法高亮 - 识别并高亮HTML/CSS/JavaScript
         */
        _highlightMixedSyntax: function(code) {
            var self = this;

            // 首先识别并保护JavaScript代码块 - 匹配已转义的HTML实体
            var jsBlocks = [];
            var jsPlaceholder = '__JS_BLOCK_' + Math.random().toString(36).substr(2, 9) + '__';

            // 识别<script>标签内的JavaScript代码 - 匹配已转义的HTML实体
            code = code.replace(/&lt;script([^&]*)&gt;([\s\S]*?)&lt;\/script&gt;/gi, function(match, attrs, jsCode) {
                var blockId = jsBlocks.length;
                jsBlocks.push({
                    type: 'javascript',
                    code: jsCode,
                    fullMatch: match
                });
                return jsPlaceholder + blockId;
            });

            // 识别内联JavaScript (onclick, onload等属性)
            code = code.replace(/(on[a-zA-Z]+)(\s*=\s*)(["'])([\s\S]*?)\3/gi, function(match, eventName, eq, quote, jsCode) {
                var blockId = jsBlocks.length;
                jsBlocks.push({
                    type: 'javascript-inline',
                    code: jsCode,
                    fullMatch: match
                });
                return eventName + eq + quote + jsPlaceholder + blockId + quote;
            });

            // 识别JavaScript事件处理器属性 (data-*事件)
            code = code.replace(/(data-on[a-zA-Z-]+)(\s*=\s*)(["'])([\s\S]*?)\3/gi, function(match, eventName, eq, quote, jsCode) {
                var blockId = jsBlocks.length;
                jsBlocks.push({
                    type: 'javascript-inline',
                    code: jsCode,
                    fullMatch: match
                });
                return eventName + eq + quote + jsPlaceholder + blockId + quote;
            });

            // 识别并保护CSS代码块 - 匹配已转义的HTML实体
            var cssBlocks = [];
            var cssPlaceholder = '__CSS_BLOCK_' + Math.random().toString(36).substr(2, 9) + '__';

            // 识别<style>标签内的CSS代码 - 匹配已转义的HTML实体
            code = code.replace(/&lt;style([^&]*)&gt;([\s\S]*?)&lt;\/style&gt;/gi, function(match, attrs, cssCode) {
                var blockId = cssBlocks.length;
                cssBlocks.push({
                    type: 'css',
                    code: cssCode,
                    fullMatch: match
                });
                return cssPlaceholder + blockId;
            });

            // 识别内联CSS (style属性)
            code = code.replace(/(style)(\s*=\s*)(["'])([\s\S]*?)\3/gi, function(match, attrName, eq, quote, cssCode) {
                var blockId = cssBlocks.length;
                cssBlocks.push({
                    type: 'css-inline',
                    code: cssCode,
                    fullMatch: match
                });
                return attrName + eq + quote + cssPlaceholder + blockId + quote;
            });

            // 首先应用HTML高亮（处理已转义的代码）
            code = self._highlightHtml(code);

            // 恢复并高亮CSS块
            cssBlocks.forEach(function(block, index) {
                var cssCode = block.code;
                if (block.type === 'css') {
                    cssCode = self._highlightCss(cssCode);
                    var highlightedTag = block.fullMatch.replace(/&lt;style([^&]*)&gt;/gi, '<span class="tag">&lt;style$1&gt;</span>');
                    highlightedTag = highlightedTag.replace(/&lt;\/style&gt;/gi, '<span class="tag">&lt;/style&gt;</span>');
                    // 正确构造高亮后的完整代码块
                    var startTagMatch = highlightedTag.match(/^&lt;style[^&]*&gt;/i);
                    var endTagMatch = highlightedTag.match(/&lt;\/style&gt;$/i);
                    var startTag = startTagMatch ? startTagMatch[0] : '<span class="tag">&lt;style&gt;</span>';
                    var endTag = endTagMatch ? endTagMatch[0] : '<span class="tag">&lt;/style&gt;</span>';
                    var finalCode = startTag + cssCode + endTag;
                    code = code.replace(cssPlaceholder + index, finalCode);
                } else if (block.type === 'css-inline') {
                    cssCode = self._highlightCssInline(cssCode);
                    code = code.replace(cssPlaceholder + index, cssCode);
                }
            });

            // 恢复并高亮JavaScript块
            jsBlocks.forEach(function(block, index) {
                var jsCode = block.code;
                if (block.type === 'javascript') {
                    jsCode = self._highlightJavaScript(jsCode);
                    var highlightedTag = block.fullMatch.replace(/&lt;script([^&]*)&gt;/gi, '<span class="tag">&lt;script$1&gt;</span>');
                    highlightedTag = highlightedTag.replace(/&lt;\/script&gt;/gi, '<span class="tag">&lt;/script&gt;</span>');
                    // 正确构造高亮后的完整代码块
                    var startTagMatch = highlightedTag.match(/^&lt;script[^&]*&gt;/i);
                    var endTagMatch = highlightedTag.match(/&lt;\/script&gt;$/i);
                    var startTag = startTagMatch ? startTagMatch[0] : '<span class="tag">&lt;script&gt;</span>';
                    var endTag = endTagMatch ? endTagMatch[0] : '<span class="tag">&lt;/script&gt;</span>';
                    var finalCode = startTag + jsCode + endTag;
                    code = code.replace(jsPlaceholder + index, finalCode);
                } else if (block.type === 'javascript-inline') {
                    jsCode = self._highlightJavaScript(jsCode);
                    code = code.replace(jsPlaceholder + index, jsCode);
                }
            });

            return code;
        },

        /**
         * 内联CSS语法高亮
         */
        _highlightCssInline: function(css) {
            // 先处理已经存在的HTML标签，防止被破坏
            css = css.replace(/&lt;/g, '__TEMP_LT__').replace(/&gt;/g, '__TEMP_GT__').replace(/&quot;/g, '__TEMP_QUOTE__').replace(/&#39;/g, '__TEMP_SINGLE__');

            // 移除可能的&编码
            css = css.replace(/&amp;/g, '&');

            // 属性和值
            css = css.replace(/([a-zA-Z-]+)(\s*:\s*)([^;]*)(;?)/g, '<span class="property">$1</span>$2<span class="value">$3</span>$4');

            // 重新编码HTML实体
            css = css.replace(/&/g, '&amp;');
            css = css.replace(/__TEMP_LT__/g, '&lt;').replace(/__TEMP_GT__/g, '&gt;').replace(/__TEMP_QUOTE__/g, '&quot;').replace(/__TEMP_SINGLE__/g, '&#39;');

            return css;
        },
        
        /**
         * HTML语法高亮
         */
        _highlightHtml: function(html) {
            // 首先保护占位符，防止被HTML高亮处理
            var placeholders = [];
            var protectedHtml = html.replace(/(__[A-Z]+_BLOCK_[a-f0-9]+__)/g, function(match) {
                var index = placeholders.length;
                placeholders[index] = match;
                return '__PLACEHOLDER_' + index + '__';
            });

            // HTML标签 - 匹配已转义的HTML实体
            protectedHtml = protectedHtml.replace(/&lt;(\/?)([a-zA-Z][a-zA-Z0-9]*)(.*?)&gt;/g, function(match, slash, tagName, attrs) {
                var highlightedTag = '<span class="tag">&lt;' + slash + tagName + '</span>';

                // 属性高亮
                var highlightedAttrs = attrs.replace(/([a-zA-Z-]+)(\s*=\s*)(["'])(.*?)\3/g, function(attrMatch, attrName, eq, quote, attrValue) {
                    return '<span class="attr-name">' + attrName + '</span>' + eq + quote + '<span class="attr-value">' + attrValue + '</span>' + quote;
                });

                return highlightedTag + highlightedAttrs + '<span class="tag">&gt;</span>';
            });

            // 恢复占位符
            placeholders.forEach(function(placeholder, index) {
                protectedHtml = protectedHtml.replace('__PLACEHOLDER_' + index + '__', placeholder);
            });

            return protectedHtml;
        },
        
        /**
         * CSS语法高亮
         */
        _highlightCss: function(css) {
            // 先处理已经存在的HTML标签，防止被破坏
            css = css.replace(/&lt;/g, '__TEMP_LT__').replace(/&gt;/g, '__TEMP_GT__').replace(/&quot;/g, '__TEMP_QUOTE__').replace(/&#39;/g, '__TEMP_SINGLE__');

            // 移除可能的&编码
            css = css.replace(/&amp;/g, '&');

            // 选择器
            css = css.replace(/([.#]?[a-zA-Z][a-zA-Z0-9-]*)\s*{/g, '<span class="selector">$1</span> {');

            // 属性和值 - 特别处理包含引号的值（如font-family）
            css = css.replace(/([a-zA-Z-]+)(\s*:\s*)([^;]+)(;)/g, function(match, property, colon, value, semicolon) {
                // 保护引号中的内容不被HTML编码
                value = value.replace(/"([^"]*?)"/g, '__PROTECTED_QUOTE__$1__PROTECTED_QUOTE__');
                value = value.replace(/'([^']*?)'/g, '__PROTECTED_SINGLE__$1__PROTECTED_SINGLE__');

                var highlighted = '<span class="property">' + property + '</span>' + colon + '<span class="value">' + value + '</span>' + semicolon;

                return highlighted;
            });

            // 重新编码HTML实体
            css = css.replace(/&/g, '&amp;');

            // 恢复保护的引号内容
            css = css.replace(/__PROTECTED_QUOTE__/g, '&quot;').replace(/__PROTECTED_SINGLE__/g, '&#39;');
            css = css.replace(/__TEMP_LT__/g, '&lt;').replace(/__TEMP_GT__/g, '&gt;').replace(/__TEMP_QUOTE__/g, '&quot;').replace(/__TEMP_SINGLE__/g, '&#39;');

            return css;
        },
        
        /**
         * JavaScript语法高亮
         */
        _highlightJavaScript: function(js) {
            // 首先完全解码HTML实体
            js = js.replace(/&amp;/g, '&');
            js = js.replace(/&lt;/g, '<');
            js = js.replace(/&gt;/g, '>');
            js = js.replace(/&quot;/g, '"');
            js = js.replace(/&#39;/g, "'");

            // 使用临时占位符来避免与最终HTML输出冲突
            var tempMarkers = {
                keyword: '__JS_KEYWORD_',
                function: '__JS_FUNCTION_',
                string: '__JS_STRING_',
                comment: '__JS_COMMENT_',
                number: '__JS_NUMBER_',
                operator: '__JS_OPERATOR_',
                property: '__JS_PROPERTY_',
                regex: '__JS_REGEX_'
            };

            // 关键字 - 增强版本
            var keywords = [
                // 基础关键字
                'var', 'let', 'const', 'function', 'if', 'else', 'for', 'while', 'do',
                'switch', 'case', 'break', 'continue', 'return', 'try', 'catch', 'finally',
                'throw', 'new', 'typeof', 'instanceof', 'in', 'of',
                // ES6+ 关键字
                'class', 'extends', 'super', 'static', 'async', 'await', 'yield',
                'import', 'export', 'from', 'default', 'as',
                // 逻辑关键字
                'true', 'false', 'null', 'undefined', 'this', 'self',
                // 控制流程
                'with', 'debugger', 'delete', 'void'
            ];

            // 第一步：高亮注释（最优先，防止其他规则处理注释内容）
            // 单行注释 //...
            js = js.replace(/(\/\/.*$)/gm, function(match) {
                // 将整个注释中的操作符字符替换为占位符
                var escaped = match.replace(/\*/g, '__STAR__');
                return tempMarkers.comment + escaped + tempMarkers.comment;
            });
            // 多行注释 /*...*/
            js = js.replace(/(\/\*[\s\S]*?\*\/)/g, function(match) {
                // 将整个注释中的操作符字符（包括分隔符中的*和/）替换为占位符
                // 这样后续的操作符正则就不会匹配到注释中的字符
                var escaped = match.replace(/\*/g, '__STAR__').replace(/\//g, '__SLASH__');
                return tempMarkers.comment + escaped + tempMarkers.comment;
            });

            // 第二步：高亮字符串（防止字符串中的关键字被高亮）
            js = js.replace(/(["'])((?:\\.|(?!\1)[^\\])*?)\1/g, function(match) {
                return tempMarkers.string + match + tempMarkers.string;
            });

            // 第三步：高亮正则表达式（避免与注释冲突，正则以/开头但不包含//）
            // 正则表达式不会紧跟在=、(、[、,、:、;等后面，且不以//开头
            js = js.replace(/(^|[^=\(\[\{:,;\s\/])(\/(?![\*\/])([^\/\n]*?)\/([gimuy]*))/g, function(_match, before, _slash, regex, _flags) {
                return before + tempMarkers.regex + '/' + regex + '/' + _flags + tempMarkers.regex;
            });

            // 第四步：高亮关键字
            keywords.forEach(function(keyword) {
                var regex = new RegExp('(^|[^\\w$])' + keyword + '([^\\w$]|$)', 'g');
                js = js.replace(regex, function(match, before, after) {
                    return before + tempMarkers.keyword + keyword + tempMarkers.keyword + after;
                });
            });

            // 第五步：高亮内置对象和函数
            var builtins = [
                'console', 'document', 'window', 'Array', 'Object', 'String', 'Number',
                'Boolean', 'Date', 'RegExp', 'Math', 'JSON', 'setTimeout', 'setInterval',
                'clearTimeout', 'clearInterval', 'parseInt', 'parseFloat', 'isNaN', 'isFinite',
                'eval', 'alert', 'confirm', 'prompt'
            ];

            builtins.forEach(function(builtin) {
                var regex = new RegExp('(^|[^\\w$])' + builtin + '([^\\w$]|$)', 'g');
                js = js.replace(regex, function(match, before, after) {
                    return before + tempMarkers.function + builtin + tempMarkers.function + after;
                });
            });

            // 第六步：高亮函数名（自定义函数）
            js = js.replace(/\b([a-zA-Z_$][a-zA-Z0-9_$]*)(\s*)(?=\s*\()/g, function(match, name, spaces) {
                return tempMarkers.function + name + tempMarkers.function + (spaces || '');
            });

            // 第七步：高亮数字
            js = js.replace(/\b(\d+\.?\d*([eE][+-]?\d+)?|0[xX][0-9a-fA-F]+|0[bB][01]+|0[oO][0-7]+)\b/g, function(match) {
                return tempMarkers.number + match + tempMarkers.number;
            });

            // 第八步：高亮操作符
            // 操作符：+ - * / = < > ! & | ~ %
            // 排除注释中的/*和*/以及已标记区域
            js = js.replace(/(?!\s*\/\*\/|\*\/|\*\/|\/\*)\s*([+\-*=<>!&|~%^]+)(?!\*\/|\*\/|\/\*|\/\*\/)/g, function(match) {
                // 确保不是/*或*/的一部分
                if (match.indexOf('/*') !== -1 || match.indexOf('*/') !== -1) {
                    return match;
                }
                return tempMarkers.operator + match + tempMarkers.operator;
            });

            // 第九步：高亮属性访问
            js = js.replace(/(\.[a-zA-Z_$][a-zA-Z0-9_$]*)/g, function(match) {
                return tempMarkers.property + match + tempMarkers.property;
            });

            // 将临时标记转换为最终的HTML标签
            // 使用正则表达式进行替换，确保正确匹配成对的标记
            function replaceMarkers(code, marker, className) {
                var result = code;
                var escapedMarker = marker.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'); // 转义特殊字符

                // 使用正则表达式匹配成对的标记
                var regex = new RegExp(escapedMarker + '([\\s\\S]*?)' + escapedMarker, 'g');
                result = result.replace(regex, '<span class="' + className + '">$1</span>');

                return result;
            }

            js = replaceMarkers(js, tempMarkers.keyword, 'keyword');
            js = replaceMarkers(js, tempMarkers.function, 'function');
            js = replaceMarkers(js, tempMarkers.string, 'string');
            js = replaceMarkers(js, tempMarkers.comment, 'comment');
            js = replaceMarkers(js, tempMarkers.number, 'number');
            js = replaceMarkers(js, tempMarkers.operator, 'operator');
            js = replaceMarkers(js, tempMarkers.property, 'property');
            js = replaceMarkers(js, tempMarkers.regex, 'string'); // 正则表达式使用字符串样式

            // 恢复注释中的占位符为原始字符
            js = js.replace(/__STAR__/g, '*').replace(/__SLASH__/g, '/');

            return js;
        },

        /**
         * 更新行号显示 - 增强版本，包含错误处理和性能优化
         */
        _updateLineNumbers: function(componentId) {
            try {
                var editor = this.editors[componentId];
                if (!editor) {
                    console.warn('[HeaSec] Editor not found for componentId:', componentId);
                    return;
                }

                var textarea = document.getElementById(editor.config.editorId);
                var lineNumbersContainer = document.getElementById(editor.config.editorId + '_linenumbers');

                if (!textarea || !lineNumbersContainer) {
                    console.warn('[HeaSec] Required elements not found for line numbers');
                    return;
                }

                // 计算当前行数
                var currentCode = textarea.value;
                var lines = currentCode.split('\n');
                var currentLineCount = lines.length;
                var previousLineCount = editor.lineCount || 1;

                // 如果行数没有变化，无需更新
                if (currentLineCount === previousLineCount) {
                    return;
                }

                // 使用requestAnimationFrame优化性能
                var self = this;
                requestAnimationFrame(function() {
                    // 优化的增量更新：只添加或删除必要的行号
                    if (currentLineCount > previousLineCount) {
                        // 添加新行号
                        var fragment = document.createDocumentFragment();
                        for (var i = previousLineCount + 1; i <= currentLineCount; i++) {
                            var lineNumberDiv = document.createElement('div');
                            lineNumberDiv.className = 'heasec-line-number';
                            lineNumberDiv.textContent = i;
                            lineNumberDiv.style.height = '21px';
                            lineNumberDiv.style.lineHeight = '21px';
                            fragment.appendChild(lineNumberDiv);
                        }
                        lineNumbersContainer.appendChild(fragment);
                    } else if (currentLineCount < previousLineCount) {
                        // 删除多余行号
                        while (lineNumbersContainer.children.length > currentLineCount) {
                            lineNumbersContainer.removeChild(lineNumbersContainer.lastChild);
                        }
                    }

                    // 更新存储的行数
                    editor.lineCount = currentLineCount;

                    // 确保滚动同步
                    self._syncScroll(componentId);
                });

            } catch (error) {
                console.error('[HeaSec] Error updating line numbers:', error);
            }
        },


        /**
         * 同步滚动 - 修复版本，支持行号同步和高度同步
         */
        _syncScroll: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return;

            var textarea = document.getElementById(editor.config.editorId);
            var highlightElement = document.getElementById(editor.config.editorId + '_highlight');
            var lineNumbersElement = document.getElementById(editor.config.editorId + '_linenumbers');

            if (!textarea) return;

            // 直接同步scrollTop和scrollLeft
            if (highlightElement) {
                highlightElement.scrollTop = textarea.scrollTop;
                highlightElement.scrollLeft = textarea.scrollLeft;
            }

            // 同步行号滚动 - 简化处理
            if (lineNumbersElement) {
                try {
                    // 同步滚动位置
                    lineNumbersElement.scrollTop = textarea.scrollTop;

                    // 不动态修改padding，保持CSS中定义的样式
                } catch (e) {
                    console.warn('[HeaSec] Line number scroll sync failed:', e);
                }
            }
        },
        
        /**
         * 处理键盘事件
         */
        _handleKeyDown: function(componentId, e) {
            var editor = this.editors[componentId];
            var textarea = document.getElementById(editor.config.editorId);
            
            if (!textarea) return;
            
            // Tab键处理
            if (e.key === 'Tab') {
                e.preventDefault();
                
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var value = textarea.value;
                
                // 插入4个空格
                textarea.value = value.substring(0, start) + '    ' + value.substring(end);
                
                // 恢复光标位置
                textarea.selectionStart = textarea.selectionEnd = start + 4;
                
                // 更新高亮和高度
                if (editor.config.syntaxHighlighting) {
                    this._updateSyntaxHighlighting(componentId);
                }
                if (editor.config.autoHeight) {
                    this._adjustHeight(componentId);
                }
            }
        },
        
        /**
         * 处理窗口大小改变
         */
        _handleResize: function(componentId) {
            // 响应式布局处理
            var editorElement = document.getElementById(componentId);
            if (!editorElement) return;
            
            var isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                editorElement.classList.add('mobile-layout');
            } else {
                editorElement.classList.remove('mobile-layout');
            }
            
            // 重新调整编辑器以适应新的宽度
            var editor = this.editors[componentId];
            if (editor) {
                if (editor.config.autoHeight) {
                    this._adjustHeight(componentId);
                }
            }
        },
        
        /**
         * 初始化预览
         */
        _initializePreview: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return;
            
            var previewFrame = document.getElementById(editor.config.previewId);
            if (!previewFrame) return;
            
            // 设置初始空白内容
            var previewDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
            previewDoc.open();
            previewDoc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Preview</title></head><body style="margin:0;padding:20px;font-family:Arial,sans-serif;"><p style="color:#666;text-align:center;margin-top:50px;">点击"运行代码"按钮查看效果</p></body></html>');
            previewDoc.close();
        },
        
        /**
         * 切换编程语言
         */
        // switchLanguage: function(componentId, language) { // 功能已移除 - 使用混合语法高亮
        //     var editor = this.editors[componentId];
        //     if (!editor) return;
        //
        //     // 保存当前语言的代码
        //     var textarea = document.getElementById(editor.config.editorId);
        //     if (!textarea) return;
        //
        //     editor.currentCode = textarea.value;
        //
        //     // 切换标签状态
        //     var container = document.getElementById(componentId);
        //     var tabs = container.querySelectorAll('.heasec-tab-button');
        //     tabs.forEach(function(tab) {
        //         tab.classList.remove('active');
        //         if (tab.getAttribute('data-language') === language) {
        //             tab.classList.add('active');
        //         }
        //     });
        //
        //     // 更新编辑器语言
        //     textarea.setAttribute('data-language', language);
        //
        //     // 恢复对应语言的代码
        //     var defaultCode = editor.config.defaultCode[language] || '';
        //     var savedCode = editor[language + 'Code'];
        //     textarea.value = savedCode || defaultCode;
        //
        //     // 更新高亮、行号和高度
        //     if (editor.config.syntaxHighlighting) {
        //         this._updateSyntaxHighlighting(componentId);
        //     }
                //     if (editor.config.autoHeight) {
        //         this._adjustHeight(componentId);
        //     }
        //
        //     editor.currentLanguage = language;
        //
        //     // 触发语言切换事件
        //     this._triggerEvent(componentId, 'languageChanged', { language: language });
        // },
        
        /**
         * 运行代码
         */
        runCode: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return;

            var textarea = document.getElementById(editor.config.editorId);
            var previewFrame = document.getElementById(editor.config.previewId);

            if (!textarea || !previewFrame) return;

            // 获取当前代码（包含HTML/CSS/JavaScript混合代码）
            var mixedCode = textarea.value;

            var self = this;

            // 尝试直接写入iframe内容
            var tryWriteCode = function() {
                try {
                    var previewDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
                    previewDoc.open();
                    previewDoc.write(mixedCode);
                    previewDoc.close();

                    // 触发代码运行事件
                    self._triggerEvent(componentId, 'codeRun', {
                        code: mixedCode
                    });
                } catch (e) {
                    // 如果直接写入失败（跨域等原因），重置iframe后重试
                    resetAndWrite();
                }
            };

            // 重置iframe并写入内容
            var resetAndWrite = function() {
                // 移除之前的load事件监听器（如果有）
                previewFrame.onload = null;

                // 设置新的load事件监听器
                previewFrame.onload = function() {
                    // iframe加载完成后写入内容
                    try {
                        var previewDoc = previewFrame.contentDocument || previewFrame.contentWindow.document;
                        previewDoc.open();
                        previewDoc.write(mixedCode);
                        previewDoc.close();

                        // 清除onload，避免影响后续操作
                        previewFrame.onload = null;
                    } catch (e) {
                        console.error('[HeaSec] 写入iframe内容失败:', e);
                    }

                    // 触发代码运行事件
                    self._triggerEvent(componentId, 'codeRun', {
                        code: mixedCode
                    });
                };

                // 重置iframe的src属性，确保iframe回到可控状态
                // 这解决了iframe跳转到第三方页面后无法写入新内容的问题
                previewFrame.src = 'about:blank';
            };

            // 先尝试直接写入，如果失败则重置iframe
            tryWriteCode();
        },
        
        /**
         * 构建HTML文档
         */
        _buildHtmlDocument: function(htmlCode, cssCode, jsCode) {
            // 如果用户只提供了CSS或JS，创建包装的HTML
            if (!htmlCode || htmlCode.trim() === '') {
                var hasCss = cssCode && cssCode.trim() !== '';
                var hasJs = jsCode && jsCode.trim() !== '';
                
                if (hasCss || hasJs) {
                    htmlCode = '<!DOCTYPE html>\n<html>\n<head>\n    <meta charset="utf-8">\n    <title>Code Preview</title>\n';
                    
                    if (hasCss) {
                        htmlCode += '    <style>\n' + cssCode + '\n    </style>\n';
                    }
                    
                    htmlCode += '</head>\n<body>\n    <div id="app"></div>\n';
                    
                    if (hasJs) {
                        htmlCode += '    <script>\n' + jsCode + '\n    </script>\n';
                    }
                    
                    htmlCode += '</body>\n</html>';
                } else {
                    htmlCode = '<!DOCTYPE html>\n<html>\n<head>\n    <meta charset="utf-8">\n    <title>Empty Preview</title>\n</head>\n<body>\n    <p style="color:#666;text-align:center;margin-top:50px;">请输入代码</p>\n</body>\n</html>';
                }
            } else {
                // 如果有HTML代码，将CSS和JS整合进去
                if (cssCode && cssCode.trim() !== '') {
                    // 查找是否已有style标签
                    if (htmlCode.indexOf('<style') === -1) {
                        htmlCode = htmlCode.replace('</head>', '    <style>\n' + cssCode + '\n    </style>\n</head>');
                    } else {
                        htmlCode = htmlCode.replace(/(<style[^>]*>)/, '$1\n' + cssCode + '\n');
                    }
                }
                
                if (jsCode && jsCode.trim() !== '') {
                    // 查找是否已有script标签
                    if (htmlCode.indexOf('<script') === -1) {
                        htmlCode = htmlCode.replace('</body>', '    <script>\n' + jsCode + '\n    </script>\n</body>');
                    } else {
                        htmlCode = htmlCode.replace(/(<script[^>]*>)/, '$1\n' + jsCode + '\n');
                    }
                }
            }
            
            return htmlCode;
        },
        
        /**
         * 清空代码
         */
        clearCode: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return;

            var textarea = document.getElementById(editor.config.editorId);
            if (!textarea) return;

            textarea.value = '';

            // 更新高亮、行号和高度
            if (editor.config.syntaxHighlighting) {
                this._updateSyntaxHighlighting(componentId);
            }
                        if (editor.config.autoHeight) {
                this._adjustHeight(componentId);
            }
            // 更新行号
            this._updateLineNumbers(componentId);

            // 触发清空事件
            this._triggerEvent(componentId, 'codeCleared');
        },
        
        /**
         * 重置代码
         */
        resetCode: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return;

            var textarea = document.getElementById(editor.config.editorId);
            if (!textarea) return;

            // 重新设置混合默认代码
            this._setMixedDefaultCode(componentId);

            // 更新高亮、行号和高度
            if (editor.config.syntaxHighlighting) {
                this._updateSyntaxHighlighting(componentId);
            }
                        if (editor.config.autoHeight) {
                this._adjustHeight(componentId);
            }
            // 更新行号
            this._updateLineNumbers(componentId);

            // 触发重置事件
            this._triggerEvent(componentId, 'codeReset', {
                code: textarea.value
            });
        },
        
        /**
         * 刷新预览
         */
        refreshPreview: function(componentId) {
            this.runCode(componentId);
        },

        /**
         * 切换扩展/收缩模式
         */
        toggleFullscreen: function(componentId) {
            var editorElement = document.getElementById(componentId);
            if (!editorElement) return;

            var isExpanded = editorElement.classList.contains('expanded');

            if (isExpanded) {
                // 收缩编辑器
                editorElement.classList.remove('expanded');
                // 触发收缩事件
                this._triggerEvent(componentId, 'editorCollapsed');
            } else {
                // 扩展编辑器
                editorElement.classList.add('expanded');
                // 触发扩展事件
                this._triggerEvent(componentId, 'editorExpanded');
            }

            // 重新调整编辑器高度
            setTimeout((function() {
                if (this.editors[componentId] && this.editors[componentId].config.autoHeight) {
                    this._adjustHeight(componentId);
                }
            }).bind(this), 100);
        },

        /**
         * 获取编辑器内容
         */
        getCode: function(componentId) {
            var editor = this.editors[componentId];
            if (!editor) return null;

            var textarea = document.getElementById(editor.config.editorId);
            return textarea ? textarea.value : '';
        },
        
        /**
         * 设置编辑器内容
         */
        setCode: function(componentId, code) {
            var editor = this.editors[componentId];
            if (!editor) return;

            var textarea = document.getElementById(editor.config.editorId);
            if (textarea) {
                textarea.value = code;

                // 更新高亮和高度
                if (editor.config.syntaxHighlighting) {
                    this._updateSyntaxHighlighting(componentId);
                }
                if (editor.config.autoHeight) {
                    this._adjustHeight(componentId);
                }
            }
        },
        
        /**
         * 手动调整编辑器高度
         */
        adjustHeight: function(componentId) {
            this._adjustHeight(componentId);
        },
        
        /**
         * 设置编辑器高度
         */
        setHeight: function(componentId, height) {
            var editor = this.editors[componentId];
            if (!editor) return;

            var textarea = document.getElementById(editor.config.editorId);
            var highlightElement = document.getElementById(editor.config.editorId + '_highlight');
            var lineNumbersElement = document.getElementById(editor.config.editorId + '_linenumbers');
            var container = document.querySelector('#' + componentId + ' .heasec-code-editor-container');
            var wrapper = document.querySelector('#' + componentId + ' .heasec-code-editor-wrapper');

            var heightStr = typeof height === 'number' ? height + 'px' : height;

            if (textarea) textarea.style.height = heightStr;
            if (highlightElement) highlightElement.style.height = heightStr;
            if (lineNumbersElement) {
        lineNumbersElement.style.height = heightStr; // 简化版本：使用CSS处理对齐
    }
            if (container) container.style.height = heightStr;
            if (wrapper) wrapper.style.height = heightStr;

            editor.contentHeight = typeof height === 'number' ? height : parseInt(height, 10);
        },
        
        /**
         * 获取编辑器当前高度
         */
        getHeight: function(componentId) {
            var editor = this.editors[componentId];
            return editor ? editor.contentHeight : null;
        },
        
        /**
         * 启用/禁用自动高度调整
         */
        setAutoHeight: function(componentId, enabled) {
            var editor = this.editors[componentId];
            if (editor) {
                editor.config.autoHeight = enabled;
                if (enabled) {
                    this._adjustHeight(componentId);
                }
            }
        },

        /**
         * 根据文件扩展名获取语言类型
         */
        getLanguageFromExtension: function(extension) {
            var extensionMap = {
                'php': 'php',
                'js': 'javascript',
                'javascript': 'javascript',
                'css': 'css',
                'html': 'html',
                'htm': 'html',
                'json': 'json',
                'xml': 'xml',
                'sql': 'sql',
                'python': 'python',
                'py': 'python',
                'java': 'java',
                'cpp': 'cpp',
                'c': 'c',
                'c++': 'cpp',
                'csharp': 'csharp',
                'cs': 'csharp',
                'go': 'go',
                'rust': 'rust',
                'rs': 'rust',
                'ruby': 'ruby',
                'rb': 'ruby',
                'swift': 'swift',
                'kotlin': 'kotlin',
                'kt': 'kotlin',
                'typescript': 'typescript',
                'ts': 'typescript'
            };

            extension = extension.toLowerCase();
            return extensionMap[extension] || 'plaintext';
        },

        /**
         * 语法高亮主函数
         */
        highlightSyntax: function(code, language) {
            if (!code || !language) {
                return code || '';
            }

            // 转义HTML特殊字符
            code = code.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#39;');

            switch (language.toLowerCase()) {
                case 'php':
                    return this._highlightPhp(code);
                case 'javascript':
                case 'js':
                    return this._highlightJavaScript(code);
                case 'css':
                    return this._highlightCss(code);
                case 'html':
                case 'htm':
                    return this._highlightHtml(code);
                case 'json':
                    return this._highlightJson(code);
                case 'sql':
                    return this._highlightSql(code);
                case 'python':
                case 'py':
                    return this._highlightPython(code);
                case 'java':
                    return this._highlightJava(code);
                case 'cpp':
                case 'c':
                case 'c++':
                    return this._highlightCpp(code);
                case 'csharp':
                case 'cs':
                    return this._highlightCsharp(code);
                case 'go':
                    return this._highlightGo(code);
                case 'rust':
                case 'rs':
                    return this._highlightRust(code);
                case 'ruby':
                case 'rb':
                    return this._highlightRuby(code);
                case 'swift':
                    return this._highlightSwift(code);
                case 'kotlin':
                case 'kt':
                    return this._highlightKotlin(code);
                case 'typescript':
                case 'ts':
                    return this._highlightTypeScript(code);
                default:
                    return code;
            }
        },

        /**
         * PHP语法高亮
         */
        _highlightPhp: function(code) {
            // PHP标签
            code = code.replace(/(&lt;\?php|&lt;\?=|&lt;\?)/g, '<span class="tag">$1</span>');
            code = code.replace(/\?&gt;/g, '<span class="tag">?&gt;</span>');

            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');
            code = code.replace(/(#.*$)/gm, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1"</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');
            code = code.replace(/(`([^`\\]|\\.)*`)/g, '<span class="string">$1</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // 关键字
            var keywords = ['abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'readonly', 'require', 'require_once', 'return', 'self', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield', 'yield from'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            // 内置函数
            var builtins = ['isset', 'unset', 'empty', 'count', 'sizeof', 'strlen', 'strpos', 'str_replace', 'preg_match', 'preg_replace', 'explode', 'implode', 'array_merge', 'array_push', 'array_pop', 'in_array', 'array_key_exists', 'is_array', 'is_string', 'is_numeric', 'intval', 'floatval', 'strval', 'boolval', 'date', 'time', 'mktime', 'strtotime', 'file_get_contents', 'file_put_contents', 'json_encode', 'json_decode', 'mysqli_connect', 'mysqli_query', 'mysqli_fetch_assoc', 'mysqli_num_rows'];
            var builtinRegex = new RegExp('\\b(' + builtins.join('|') + ')\\s*(?=\\()', 'gi');
            code = code.replace(builtinRegex, '<span class="function">$1</span>');

            // 变量
            code = code.replace(/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/g, '<span class="variable">$$1</span>');

            // 常量
            code = code.replace(/\b([A-Z_][A-Z0-9_]*)\b/g, '<span class="property">$1</span>');

            return code;
        },

        /**
         * JSON语法高亮
         */
        _highlightJson: function(code) {
            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');

            // 数字
            code = code.replace(/\b(-?\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // 布尔值和null
            code = code.replace(/\b(true|false|null)\b/g, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * SQL语法高亮
         */
        _highlightSql: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');
            code = code.replace(/(--.*$)/gm, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // SQL关键字
            var keywords = ['SELECT', 'FROM', 'WHERE', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'ALTER', 'TABLE', 'INDEX', 'DATABASE', 'SCHEMA', 'PRIMARY', 'KEY', 'FOREIGN', 'REFERENCES', 'NOT', 'NULL', 'DEFAULT', 'AUTO_INCREMENT', 'INT', 'INTEGER', 'VARCHAR', 'CHAR', 'TEXT', 'BLOB', 'DATE', 'DATETIME', 'TIMESTAMP', 'BOOLEAN', 'BOOL', 'DECIMAL', 'FLOAT', 'DOUBLE', 'UNION', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'OUTER', 'ON', 'AS', 'AND', 'OR', 'NOT', 'IN', 'EXISTS', 'BETWEEN', 'LIKE', 'REGEXP', 'IS', 'DISTINCT', 'ALL', 'ANY', 'SOME', 'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'GROUP', 'BY', 'HAVING', 'ORDER', 'LIMIT', 'OFFSET'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * Python语法高亮
         */
        _highlightPython: function(code) {
            // 注释
            code = code.replace(/(#.*$)/gm, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"""([\s\S]*?)"""/g, '<span class="string">"""$1"""</span>');
            code = code.replace(/'''([\s\S]*?)'''/g, '<span class="string">\'\'\'$1\'\'\'</span>');
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1"</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // Python关键字
            var keywords = ['and', 'as', 'assert', 'break', 'class', 'continue', 'def', 'del', 'elif', 'else', 'except', 'finally', 'for', 'from', 'global', 'if', 'import', 'in', 'is', 'lambda', 'nonlocal', 'not', 'or', 'pass', 'raise', 'return', 'try', 'while', 'with', 'yield', 'async', 'await'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            // 内置函数
            var builtins = ['print', 'len', 'range', 'list', 'dict', 'tuple', 'set', 'str', 'int', 'float', 'bool', 'type', 'isinstance', 'hasattr', 'getattr', 'setattr', 'delattr', 'open', 'read', 'write', 'close', 'append', 'extend', 'insert', 'remove', 'pop', 'clear', 'copy', 'keys', 'values', 'items', 'update', 'get', 'popitem', 'setdefault', 'sorted', 'reversed', 'enumerate', 'zip', 'map', 'filter', 'reduce', 'sum', 'max', 'min', 'any', 'all', 'abs', 'round', 'pow'];
            var builtinRegex = new RegExp('\\b(' + builtins.join('|') + ')\\s*(?=\\()', 'gi');
            code = code.replace(builtinRegex, '<span class="function">$1</span>');

            return code;
        },

        /**
         * Java语法高亮
         */
        _highlightJava: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*[lLfFdD]?)\b/g, '<span class="number">$1</span>');

            // Java关键字
            var keywords = ['abstract', 'assert', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'class', 'const', 'continue', 'default', 'do', 'double', 'else', 'enum', 'extends', 'final', 'finally', 'float', 'for', 'goto', 'if', 'implements', 'import', 'instanceof', 'int', 'interface', 'long', 'native', 'new', 'package', 'private', 'protected', 'public', 'return', 'short', 'static', 'strictfp', 'super', 'switch', 'synchronized', 'this', 'throw', 'throws', 'transient', 'try', 'void', 'volatile', 'while'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            // 函数调用
            code = code.replace(/\b([a-zA-Z_$][a-zA-Z0-9_$]*)\s*(?=\s*\()/g, '<span class="function">$1</span>');

            return code;
        },

        /**
         * C++语法高亮
         */
        _highlightCpp: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*[fFlL]?)\b/g, '<span class="number">$1</span>');

            // C++关键字
            var keywords = ['alignas', 'alignof', 'and', 'and_eq', 'asm', 'auto', 'bitand', 'bitor', 'bool', 'break', 'case', 'catch', 'char', 'char8_t', 'char16_t', 'char32_t', 'class', 'compl', 'concept', 'const', 'consteval', 'constexpr', 'const_cast', 'continue', 'co_await', 'co_return', 'co_yield', 'decltype', 'default', 'delete', 'do', 'double', 'dynamic_cast', 'else', 'enum', 'explicit', 'export', 'extern', 'false', 'float', 'for', 'friend', 'goto', 'if', 'inline', 'int', 'long', 'mutable', 'namespace', 'new', 'noexcept', 'not', 'not_eq', 'nullptr', 'operator', 'or', 'or_eq', 'private', 'protected', 'public', 'register', 'reinterpret_cast', 'requires', 'return', 'short', 'signed', 'sizeof', 'static', 'static_assert', 'static_cast', 'struct', 'switch', 'template', 'this', 'thread_local', 'throw', 'true', 'try', 'typedef', 'typeid', 'typename', 'union', 'unsigned', 'using', 'virtual', 'void', 'volatile', 'wchar_t', 'while', 'xor', 'xor_eq'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * C#语法高亮
         */
        _highlightCsharp: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/@?"([^"\\]|\\.)*"/g, '<span class="string">$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*[fFdDmMlLuUL]?)\b/g, '<span class="number">$1</span>');

            // C#关键字
            var keywords = ['abstract', 'as', 'base', 'bool', 'break', 'byte', 'case', 'catch', 'char', 'checked', 'class', 'const', 'continue', 'decimal', 'default', 'delegate', 'do', 'double', 'else', 'enum', 'event', 'explicit', 'extern', 'false', 'finally', 'fixed', 'float', 'for', 'foreach', 'goto', 'if', 'implicit', 'in', 'int', 'interface', 'internal', 'is', 'lock', 'long', 'namespace', 'new', 'null', 'object', 'operator', 'out', 'override', 'params', 'private', 'protected', 'public', 'readonly', 'ref', 'return', 'sbyte', 'sealed', 'short', 'sizeof', 'stackalloc', 'static', 'string', 'struct', 'switch', 'this', 'throw', 'true', 'try', 'typeof', 'uint', 'ulong', 'unchecked', 'unsafe', 'ushort', 'using', 'virtual', 'void', 'volatile', 'while'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * Go语法高亮
         */
        _highlightGo: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/`([^`\\]|\\.)*`/g, '<span class="string">$1</span>');
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // Go关键字
            var keywords = ['break', 'case', 'chan', 'const', 'continue', 'default', 'defer', 'else', 'fallthrough', 'for', 'func', 'go', 'goto', 'if', 'import', 'interface', 'map', 'package', 'range', 'return', 'select', 'struct', 'switch', 'type', 'var'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * Rust语法高亮
         */
        _highlightRust: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');
            code = code.replace(/b"([^"\\]|\\.)*"/g, '<span class="string">b"$1"</span>');
            code = code.replace(/b'([^'\\]|\\.)*'/g, '<span class="string">b\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // Rust关键字
            var keywords = ['as', 'async', 'await', 'break', 'const', 'continue', 'crate', 'dyn', 'else', 'enum', 'extern', 'false', 'fn', 'for', 'if', 'impl', 'in', 'let', 'loop', 'match', 'mod', 'move', 'mut', 'pub', 'ref', 'return', 'self', 'Self', 'static', 'struct', 'super', 'trait', 'true', 'type', 'union', 'unsafe', 'use', 'where', 'while'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * Ruby语法高亮
         */
        _highlightRuby: function(code) {
            // 注释
            code = code.replace(/(#.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(=begin[\s\S]*?=end)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');
            code = code.replace(/`([^`\\]|\\.)*`/g, '<span class="string">$1</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // Ruby关键字
            var keywords = ['alias', 'and', 'BEGIN', 'begin', 'break', 'case', 'class', 'def', 'defined?', 'do', 'else', 'elsif', 'END', 'end', 'ensure', 'false', 'for', 'if', 'in', 'module', 'next', 'nil', 'not', 'or', 'redo', 'rescue', 'retry', 'return', 'self', 'super', 'then', 'true', 'undef', 'unless', 'until', 'when', 'while', 'yield'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * Swift语法高亮
         */
        _highlightSwift: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // Swift关键字
            var keywords = ['associatedtype', 'class', 'deinit', 'enum', 'extension', 'fileprivate', 'func', 'import', 'init', 'inout', 'internal', 'let', 'open', 'operator', 'private', 'protocol', 'public', 'rethrows', 'static', 'struct', 'subscript', 'typealias', 'var', 'break', 'case', 'continue', 'default', 'defer', 'do', 'else', 'fallthrough', 'for', 'guard', 'if', 'in', 'repeat', 'return', 'switch', 'where', 'while', 'as', 'catch', 'throw', 'throws', 'try', 'nil'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * Kotlin语法高亮
         */
        _highlightKotlin: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"""([\s\S]*?)"""/g, '<span class="string">"""$1"""</span>');
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*[fFlL]?)\b/g, '<span class="number">$1</span>');

            // Kotlin关键字
            var keywords = ['as', 'as?', 'break', 'class', 'continue', 'do', 'else', 'false', 'for', 'fun', 'if', 'in', 'in!', 'interface', 'is', 'is!', 'null', 'object', 'package', 'return', 'super', 'this', 'throw', 'true', 'try', 'typealias', 'val', 'var', 'when', 'while', 'by', 'catch', 'constructor', 'delegate', 'dynamic', 'field', 'finally', 'get', 'import', 'init', 'init', 'param', 'property', 'receiver', 'set', 'setparam', 'where', 'actual', 'abstract', 'annotation', 'companion', 'const', 'crossinline', 'data', 'enum', 'expect', 'external', 'final', 'infix', 'inline', 'inner', 'internal', 'lateinit', 'noinline', 'open', 'operator', 'out', 'override', 'private', 'protected', 'public', 'reified', 'sealed', 'suspend', 'tailrec', 'vararg'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            return code;
        },

        /**
         * TypeScript语法高亮
         */
        _highlightTypeScript: function(code) {
            // 注释
            code = code.replace(/(\/\/.*$)/gm, '<span class="comment">$1</span>');
            code = code.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="comment">$1</span>');

            // 字符串
            code = code.replace(/"([^"\\]|\\.)*"/g, '<span class="string">"$1</span>');
            code = code.replace(/'([^'\\]|\\.)*'/g, '<span class="string">\'$1\'</span>');
            code = code.replace(/`([^`\\]|\\.)*`/g, '<span class="string">$1</span>');

            // 数字
            code = code.replace(/\b(\d+\.?\d*)\b/g, '<span class="number">$1</span>');

            // JavaScript关键字 + TypeScript额外关键字
            var keywords = ['break', 'case', 'catch', 'class', 'const', 'continue', 'debugger', 'default', 'delete', 'do', 'else', 'export', 'extends', 'finally', 'for', 'function', 'if', 'import', 'in', 'instanceof', 'let', 'new', 'return', 'super', 'switch', 'this', 'throw', 'try', 'typeof', 'var', 'void', 'while', 'with', 'yield', 'async', 'await', 'as', 'implements', 'interface', 'package', 'private', 'protected', 'public', 'static', 'abstract', 'declare', 'enum', 'module', 'namespace', 'type', 'readonly', 'keyof', 'unknown', 'never', 'any', 'boolean', 'number', 'string', 'symbol'];
            var keywordRegex = new RegExp('\\b(' + keywords.join('|') + ')\\b', 'gi');
            code = code.replace(keywordRegex, '<span class="keyword">$1</span>');

            // 内置对象和函数
            var builtins = ['console', 'document', 'window', 'Array', 'Object', 'String', 'Number', 'Boolean', 'Date', 'RegExp', 'Math', 'JSON', 'parseInt', 'parseFloat', 'isNaN', 'isFinite', 'setTimeout', 'setInterval', 'clearTimeout', 'clearInterval', 'Promise', 'fetch', 'localStorage', 'sessionStorage', 'addEventListener', 'removeEventListener'];
            var builtinRegex = new RegExp('\\b(' + builtins.join('|') + ')\\s*(?=\\()', 'gi');
            code = code.replace(builtinRegex, '<span class="function">$1</span>');

            return code;
        },

        /**
         * 触发自定义事件
         */
        _triggerEvent: function(componentId, eventName, data) {
            var editorElement = document.getElementById(componentId);
            if (!editorElement) return;

            var event = new CustomEvent('heasecCodeEditor:' + eventName, {
                detail: Object.assign({
                    componentId: componentId,
                    timestamp: new Date().toISOString()
                }, data || {})
            });

            editorElement.dispatchEvent(event);
        }
    };

    // 导出到全局对象
    if (typeof window.HeaSec === 'undefined') {
        window.HeaSec = {};
    }
    window.HeaSec.CodeEditor = HeaSecCodeEditor;

    // 为了向后兼容，也暴露为全局对象
    window.HeaSecCodeEditor = HeaSecCodeEditor;

    // 暴露常用函数到全局作用域
    window.getLanguageFromExtension = function(extension) {
        return HeaSecCodeEditor.getLanguageFromExtension(extension);
    };

    window.highlightSyntax = function(code, language) {
        return HeaSecCodeEditor.highlightSyntax(code, language);
    };

})(window);