/**
 * HeaSec天积安全团队 - 科技蓝UI组件库配置管理
 * Tech Blue UI Component Library Configuration Manager
 * 版本: v1.0.0
 * 创建日期: 2025-11-11
 * 团队: 天积安全 (HeavenlySecret)
 * 描述: 科技蓝UI组件库的配置管理和预设主题
 */

// 创建全局配置对象
window.HeaSecTechBlueConfig = {
    
    /**
     * 预设主题配置
     */
    themes: {
        // 默认科技蓝主题
        default: {
            name: 'default',
            displayName: '科技蓝',
            colors: {
                primary: '#007BFF',
                secondary: '#0066CC',
                accent: '#00CCFF',
                success: '#28A745',
                warning: '#FFC107',
                danger: '#DC3545',
                info: '#17A2B8'
            },
            animations: {
                enabled: true,
                pageLoad: true,
                hoverEffects: true,
                transitions: true
            },
            borderRadius: {
                small: '6px',
                medium: '10px',
                large: '16px',
                xlarge: '24px'
            }
        },
        
        // 深度科技主题
        darkTech: {
            name: 'darkTech',
            displayName: '深空科技',
            colors: {
                primary: '#0066CC',
                secondary: '#004499',
                accent: '#0099FF',
                success: '#2ECC71',
                warning: '#F39C12',
                danger: '#E74C3C',
                info: '#3498DB'
            },
            animations: {
                enabled: true,
                pageLoad: false,
                hoverEffects: true,
                transitions: true
            },
            borderRadius: {
                small: '4px',
                medium: '8px',
                large: '12px',
                xlarge: '16px'
            }
        },
        
        // 极简主题
        minimal: {
            name: 'minimal',
            displayName: '极简科技',
            colors: {
                primary: '#2196F3',
                secondary: '#1976D2',
                accent: '#03A9F4',
                success: '#4CAF50',
                warning: '#FF9800',
                danger: '#F44336',
                info: '#2196F3'
            },
            animations: {
                enabled: false,
                pageLoad: false,
                hoverEffects: true,
                transitions: true
            },
            borderRadius: {
                small: '2px',
                medium: '4px',
                large: '6px',
                xlarge: '8px'
            }
        },
        
        // 高性能主题
        performance: {
            name: 'performance',
            displayName: '性能优化',
            colors: {
                primary: '#007BFF',
                secondary: '#0066CC',
                accent: '#00CCFF',
                success: '#28A745',
                warning: '#FFC107',
                danger: '#DC3545',
                info: '#17A2B8'
            },
            animations: {
                enabled: false,
                pageLoad: false,
                hoverEffects: false,
                transitions: false
            },
            borderRadius: {
                small: '4px',
                medium: '6px',
                large: '8px',
                xlarge: '10px'
            }
        }
    },
    
    /**
     * 组件预设配置
     */
    componentPresets: {
        // 卡片预设
        card: {
            basic: {
                hover: true,
                shadow: true,
                border: true,
                animation: 'fadeIn'
            },
            elevated: {
                hover: true,
                shadow: 'large',
                border: false,
                animation: 'scaleIn'
            },
            flat: {
                hover: false,
                shadow: false,
                border: true,
                animation: 'none'
            },
            glass: {
                hover: true,
                shadow: true,
                border: false,
                animation: 'fadeIn',
                backdrop: true
            }
        },
        
        // 按钮预设
        button: {
            primary: {
                size: 'medium',
                variant: 'primary',
                outline: false,
                rounded: true,
                ripple: true
            },
            secondary: {
                size: 'medium',
                variant: 'secondary',
                outline: false,
                rounded: true,
                ripple: false
            },
            ghost: {
                size: 'medium',
                variant: 'ghost',
                outline: true,
                rounded: false,
                ripple: true
            },
            minimal: {
                size: 'small',
                variant: 'minimal',
                outline: false,
                rounded: false,
                ripple: false
            }
        },
        
        // 提示框预设
        alert: {
            info: {
                type: 'info',
                dismissible: true,
                icon: true,
                autoClose: 0,
                position: 'top-right'
            },
            success: {
                type: 'success',
                dismissible: true,
                icon: true,
                autoClose: 5000,
                position: 'top-right'
            },
            warning: {
                type: 'warning',
                dismissible: true,
                icon: true,
                autoClose: 8000,
                position: 'top-center'
            },
            error: {
                type: 'danger',
                dismissible: true,
                icon: true,
                autoClose: 0,
                position: 'top-right'
            }
        },
        
        // 模态框预设
        modal: {
            small: {
                size: 'small',
                centered: true,
                backdrop: true,
                closeOnEscape: true,
                animation: 'scaleIn'
            },
            medium: {
                size: 'medium',
                centered: true,
                backdrop: true,
                closeOnEscape: true,
                animation: 'fadeIn'
            },
            large: {
                size: 'large',
                centered: false,
                backdrop: true,
                closeOnEscape: true,
                animation: 'slideIn'
            },
            fullscreen: {
                size: 'fullscreen',
                centered: true,
                backdrop: false,
                closeOnEscape: true,
                animation: 'fadeIn'
            }
        }
    },
    
    /**
     * 响应式断点配置
     */
    breakpoints: {
        xs: '0px',
        sm: '576px',
        md: '768px',
        lg: '992px',
        xl: '1200px',
        xxl: '1400px'
    },
    
    /**
     * 动画配置
     */
    animations: {
        durations: {
            fast: '0.2s',
            normal: '0.3s',
            slow: '0.5s'
        },
        easings: {
            ease: 'ease',
            easeIn: 'ease-in',
            easeOut: 'ease-out',
            easeInOut: 'ease-in-out',
            cubicBezier: 'cubic-bezier(0.4, 0, 0.2, 1)'
        },
        effects: {
            fadeIn: ['opacity'],
            slideIn: ['transform', 'opacity'],
            scaleIn: ['transform', 'opacity'],
            rotateIn: ['transform'],
            glow: ['box-shadow']
        }
    },
    
    /**
     * 当前活动配置
     */
    current: {
        theme: 'default',
        customSettings: {},
        componentPresets: {}
    },
    
    /**
     * 应用主题
     */
    applyTheme: function(themeName, customSettings = {}) {
        const theme = this.themes[themeName];
        
        if (!theme) {
            // [HeaSec Log Cleanup - 2025-11-22]
            // console.warn(`[HeaSecTechBlueConfig] 主题 "${themeName}" 不存在`);
            return false;
        }
        
        // 应用CSS变量
        this.applyThemeVariables(theme.colors);
        
        // 应用动画设置
        this.applyAnimationSettings(theme.animations);
        
        // 应用圆角设置
        this.applyBorderRadius(theme.borderRadius);
        
        // 保存当前主题
        this.current.theme = themeName;
        this.current.customSettings = customSettings;
        
        // 应用自定义设置
        if (Object.keys(customSettings).length > 0) {
            this.applyCustomSettings(customSettings);
        }
        
        // 触发主题变更事件
        this.dispatchEvent('theme:changed', {
            themeName: themeName,
            theme: theme,
            customSettings: customSettings
        });
        
        // [HeaSec Log Cleanup - 2025-11-22]
        // console.log(`[HeaSecTechBlueConfig] 已应用主题: ${theme.displayName}`);
        
        return true;
    },
    
    /**
     * 应用主题变量
     */
    applyThemeVariables: function(colors) {
        const root = document.documentElement;
        
        Object.entries(colors).forEach(([key, value]) => {
            const cssVar = `--heasec-tech-${this.camelToKebab(key)}`;
            root.style.setProperty(cssVar, value);
        });
    },
    
    /**
     * 应用动画设置
     */
    applyAnimationSettings: function(animations) {
        const root = document.documentElement;
        
        root.style.setProperty('--heasec-tech-animations-enabled', animations.enabled ? '1' : '0');
        root.style.setProperty('--heasec-tech-hover-animations-enabled', animations.hoverEffects ? '1' : '0');
        root.style.setProperty('--heasec-tech-page-load-animations-enabled', animations.pageLoad ? '1' : '0');
        root.style.setProperty('--heasec-tech-transition-animations-enabled', animations.transitions ? '1' : '0');
        
        // 更新HeaSec.TechBlue配置
        if (window.HeaSec && window.HeaSec.TechBlue) {
            window.HeaSec.TechBlue.config.animations = animations;
        }
    },
    
    /**
     * 应用圆角设置
     */
    applyBorderRadius: function(borderRadius) {
        const root = document.documentElement;
        
        Object.entries(borderRadius).forEach(([key, value]) => {
            const cssVar = `--heasec-tech-radius-${key}`;
            root.style.setProperty(cssVar, value);
        });
    },
    
    /**
     * 应用自定义设置
     */
    applyCustomSettings: function(settings) {
        const root = document.documentElement;
        
        Object.entries(settings).forEach(([key, value]) => {
            const cssVar = `--heasec-tech-${this.camelToKebab(key)}`;
            root.style.setProperty(cssVar, value);
        });
    },
    
    /**
     * 获取组件预设
     */
    getComponentPreset: function(componentType, presetName) {
        const component = this.componentPresets[componentType];
        
        if (!component) {
            console.warn(`[HeaSecTechBlueConfig] 组件类型 "${componentType}" 不存在`);
            return null;
        }
        
        const preset = component[presetName];
        
        if (!preset) {
            console.warn(`[HeaSecTechBlueConfig] 预设 "${presetName}" 在组件 "${componentType}" 中不存在`);
            return null;
        }
        
        return preset;
    },
    
    /**
     * 保存组件预设
     */
    saveComponentPreset: function(componentType, presetName, preset) {
        if (!this.componentPresets[componentType]) {
            this.componentPresets[componentType] = {};
        }
        
        this.componentPresets[componentType][presetName] = preset;
        
        // 保存到当前配置
        if (!this.current.componentPresets[componentType]) {
            this.current.componentPresets[componentType] = {};
        }
        
        this.current.componentPresets[componentType][presetName] = preset;
        
        // 触发预设保存事件
        this.dispatchEvent('preset:saved', {
            componentType: componentType,
            presetName: presetName,
            preset: preset
        });
        
        console.log(`[HeaSecTechBlueConfig] 已保存预设: ${componentType}.${presetName}`);
        
        return true;
    },
    
    /**
     * 导出配置
     */
    exportConfig: function() {
        const config = {
            current: this.current,
            themes: this.themes,
            componentPresets: this.componentPresets,
            breakpoints: this.breakpoints,
            animations: this.animations,
            version: '1.0.0',
            exportDate: new Date().toISOString()
        };
        
        return JSON.stringify(config, null, 2);
    },
    
    /**
     * 导入配置
     */
    importConfig: function(configJson) {
        try {
            const config = JSON.parse(configJson);
            
            // 验证配置结构
            if (!config.version || !config.current) {
                throw new Error('无效的配置文件格式');
            }
            
            // 导入配置
            if (config.themes) {
                Object.assign(this.themes, config.themes);
            }
            
            if (config.componentPresets) {
                Object.assign(this.componentPresets, config.componentPresets);
            }
            
            if (config.breakpoints) {
                Object.assign(this.breakpoints, config.breakpoints);
            }
            
            if (config.animations) {
                Object.assign(this.animations, config.animations);
            }
            
            // 应用当前配置
            if (config.current.theme) {
                this.applyTheme(config.current.theme, config.current.customSettings);
            }
            
            // 触发配置导入事件
            this.dispatchEvent('config:imported', {
                version: config.version,
                importDate: new Date().toISOString()
            });
            
            console.log('[HeaSecTechBlueConfig] 配置导入成功');
            
            return true;
        } catch (error) {
            console.error('[HeaSecTechBlueConfig] 配置导入失败:', error);
            return false;
        }
    },
    
    /**
     * 重置配置
     */
    resetConfig: function() {
        // 应用默认主题
        this.applyTheme('default');
        
        // 清除自定义设置
        this.current.customSettings = {};
        this.current.componentPresets = {};
        
        // 触发重置事件
        this.dispatchEvent('config:reset', {
            timestamp: Date.now()
        });
        
        console.log('[HeaSecTechBlueConfig] 配置已重置');
    },
    
    /**
     * 获取媒体查询
     */
    getMediaQuery: function(breakpoint) {
        const value = this.breakpoints[breakpoint];
        
        if (!value) {
            console.warn(`[HeaSecTechBlueConfig] 断点 "${breakpoint}" 不存在`);
            return null;
        }
        
        return window.matchMedia(`(min-width: ${value})`);
    },
    
    /**
     * 监听断点变化
     */
    onBreakpointChange: function(breakpoint, callback) {
        const mediaQuery = this.getMediaQuery(breakpoint);
        
        if (!mediaQuery) {
            return null;
        }
        
        const handler = (e) => {
            callback({
                breakpoint: breakpoint,
                matches: e.matches,
                mediaQuery: mediaQuery
            });
        };
        
        mediaQuery.addEventListener('change', handler);
        
        // 立即执行一次
        handler({ matches: mediaQuery.matches });
        
        return {
            mediaQuery: mediaQuery,
            handler: handler,
            destroy: () => {
                mediaQuery.removeEventListener('change', handler);
            }
        };
    },
    
    /**
     * 工具函数：驼峰转短横线
     */
    camelToKebab: function(str) {
        return str.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase();
    },
    
    /**
     * 工具函数：短横线转驼峰
     */
    kebabToCamel: function(str) {
        return str.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    },
    
    /**
     * 触发自定义事件
     */
    dispatchEvent: function(eventName, detail = {}) {
        const event = new CustomEvent(`heasec:techblue:${eventName}`, {
            detail: detail,
            bubbles: true,
            cancelable: true
        });
        
        document.dispatchEvent(event);
        
        return event;
    },
    
    /**
     * 监听配置事件
     */
    on: function(eventName, callback) {
        const fullEventName = `heasec:techblue:${eventName}`;
        
        document.addEventListener(fullEventName, callback);
        
        return {
            destroy: () => {
                document.removeEventListener(fullEventName, callback);
            }
        };
    },
    
    /**
     * 移除事件监听
     */
    off: function(eventName, callback) {
        const fullEventName = `heasec:techblue:${eventName}`;
        document.removeEventListener(fullEventName, callback);
    }
};

// 初始化默认主题
window.HeaSecTechBlueConfig.applyTheme('default');

// 监听系统主题变化
if (window.matchMedia) {
    const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    darkModeQuery.addEventListener('change', (e) => {
        if (e.matches) {
            window.HeaSecTechBlueConfig.applyTheme('darkTech');
        } else {
            window.HeaSecTechBlueConfig.applyTheme('default');
        }
    });
    
    // 初始检查
    if (darkModeQuery.matches) {
        window.HeaSecTechBlueConfig.applyTheme('darkTech');
    }
}

// 暴露到全局作用域
window.HeaSecTechBlueConfig = window.HeaSecTechBlueConfig;