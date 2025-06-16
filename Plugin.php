<?php
/**
 * <a href='https://github.com/BXCQ/ColorfulTags' title='项目主页' target='_blank'>3D彩色标签云插件</a>
 * 适配Handsome主题及自定义样式
 *
 * @package ColorfulTags
 * @author 璇
 * @version 3.0.1
 * @link https://blog.ybyq.wang/
 */
class ColorfulTags_Plugin implements Typecho_Plugin_Interface
{
	/* 激活插件方法 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'render');
		return _t('插件已启用，请进入设置面板配置参数');
	}
	/* 禁用插件方法 */
	public static function deactivate()
	{
		return _t('插件已禁用');
	}
	/* 插件配置方法 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		// 是否启用3D效果
		$is_3d = new Typecho_Widget_Helper_Form_Element_Radio(
			'is_3d', 
			['0' => _t('否'), '1' => _t('是')], 
			'1', 
			_t('是否启用3D效果'), 
			_t('开启后标签云会围绕3D球体滚动')
		);
		$form->addInput($is_3d);
		
		// 3D旋转速度
		$speed = new Typecho_Widget_Helper_Form_Element_Text(
			'speed', 
			NULL, 
			'1',
			_t('3D旋转速度：'),
			_t('默认为1，建议0.5-2之间')
		);
		$form->addInput($speed);
		
		// 颜色风格选择
		$colorStyle = new Typecho_Widget_Helper_Form_Element_Select(
			'colorStyle',
			array(
				'deep' => _t('深色系'),
				'bright' => _t('明亮系'),
				'pastel' => _t('粉彩系'),
				'contrast' => _t('高对比度')
			),
			'deep',
			_t('颜色风格'),
			_t('选择标签云的颜色风格')
		);
		$form->addInput($colorStyle);
		
		// 是否启用PJAX
		$is_pjax = new Typecho_Widget_Helper_Form_Element_Radio(
			'is_pjax', 
			['0' => _t('否'), '1' => _t('是')], 
			'1', 
			_t('是否启用了PJAX'), 
			_t('如果启用了PJAX，请选择"是"以确保页面切换时标签云效果不会失效')
		);
		$form->addInput($is_pjax);
		
		// 选择器配置
		$selector = new Typecho_Widget_Helper_Form_Element_Text(
			'selector', 
			NULL, 
			'#tag_cloud .tags',
			_t('标签云容器选择器：'),
			_t('默认为"#tag_cloud .tags"，如果您的主题结构不同，请修改此选择器')
		);
		$form->addInput($selector);
		
		// 标签选择器配置
		$tagSelector = new Typecho_Widget_Helper_Form_Element_Text(
			'tagSelector', 
			NULL, 
			'#tag_cloud .tags a',
			_t('标签选择器：'),
			_t('默认为"#tag_cloud .tags a"，如果您的主题结构不同，请修改此选择器')
		);
		$form->addInput($tagSelector);
	}
	/* 个人用户的配置方法 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form) {}
	/* 插件实现方法 */
	public static function render($archive)
	{
		// 获取参数
		$options = Helper::options();
		$is_3d = $options->plugin('ColorfulTags')->is_3d;
		$speed = $options->plugin('ColorfulTags')->speed;
		$is_pjax = $options->plugin('ColorfulTags')->is_pjax;
		$colorStyle = $options->plugin('ColorfulTags')->colorStyle;
		$selector = $options->plugin('ColorfulTags')->selector;
		$tagSelector = $options->plugin('ColorfulTags')->tagSelector;
		
		// 不再使用is_post条件，总是应用标签云
		$static_src = $options->pluginUrl . '/ColorfulTags/static';
		
		// 根据颜色风格设置颜色变量
		$colorVariable = self::getColorVariable($colorStyle);
		
		// 构建HTML输出
		if ($is_pjax) {
			// PJAX模式下的输出
			if (!$is_3d) {
				// 不启用3D效果时的输出
				$html = <<<html
<!-- Start 3DColorfulTags -->
<link rel="stylesheet" type="text/css" href="{$static_src}/css/colorfultags.min.css">
<script src="{$static_src}/js/colorfultags.min.js"></script>
<script id="colorfultags">
console.info("%c彩色标签云 - SVG版 | https://github.com/BXCQ/3DColorfulTags","line-height:28px;padding:4px;background:#3f51b5;color:#fff;font-size:14px;font-family:Microsoft YaHei;");
// 设置颜色变量
var colorfulTagsColors = {$colorVariable};

// 尝试自动检测Handsome主题
if(document.querySelector('#tag_cloud .tags') || document.querySelector('.widget_tag_cloud .tags') || document.querySelector('div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm')) {
    console.log("检测到Handsome主题，使用自动优化");
    initHandsomeTagCloud();
} else {
    colorfultags("{$tagSelector}");
}

// PJAX完成后重新应用
$(document).on("pjax:complete", function() {
    // 尝试自动检测Handsome主题
    if(document.querySelector('#tag_cloud .tags') || document.querySelector('.widget_tag_cloud .tags') || document.querySelector('div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm')) {
        console.log("PJAX: 检测到Handsome主题，使用自动优化");
        initHandsomeTagCloud();
    } else {
        colorfultags("{$tagSelector}");
    }
});
</script>
<!-- End 3DColorfulTags -->
html;
			} else {
				// 启用3D效果时的输出
				$html = <<<html
<!-- Start 3DColorfulTags -->
<link rel="stylesheet" type="text/css" href="{$static_src}/css/colorfultags.min.css">
<link rel="stylesheet" type="text/css" href="{$static_src}/css/around3d.min.css">
<script src="{$static_src}/js/colorfultags.min.js"></script>
<script src="{$static_src}/js/svg3dtagcloud.min.js"></script>
<script id="colorfultags">
console.info("%c彩色标签云 - SVG版 | https://github.com/BXCQ/3DColorfulTags","line-height:28px;padding:4px;background:#3f51b5;color:#fff;font-size:14px;font-family:Microsoft YaHei;");
// 设置颜色变量
var colorfulTagsColors = {$colorVariable};

// 检查是否已初始化，防止重复执行
if (typeof window.svg3DTagCloudInitialized === 'undefined') {
    window.svg3DTagCloudInitialized = false;
}

// 尝试使用SVG 3D标签云
function initSVG3DTagCloud() {
    if (window.svg3DTagCloudInitialized) {
        console.log("SVG 3D标签云已初始化，跳过");
        return true;
    }
    
    const tagContainers = document.querySelectorAll('#tag_cloud .tags, .widget_tag_cloud .tags, div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm');
    if(tagContainers.length === 0) return false;
    
    for(let container of tagContainers) {
        // 应用标签颜色
        const tags = container.querySelectorAll('a');
        if(tags.length === 0) return false;
        
        // 从标签中提取数据
        const entries = [];
        for(let tag of tags) {
            // 随机选择颜色
            const color = colorfulTagsColors[Math.floor(Math.random() * colorfulTagsColors.length)];
            entries.push({
                label: tag.textContent,
                url: tag.href,
                target: '_blank',
                color: color,
                fontSize: '14'
            });
            
            // 隐藏原始标签
            tag.style.display = 'none';
        }
        
        // 清空容器内容但保留容器
        container.innerHTML = '';
        
        // 创建3D标签云
        const cloud = new SVG3DTagCloud(container, {
            entries: entries,
            width: '100%',
            height: '250px',
            radius: '70%',
            radiusMin: 75,
            bgDraw: false,
            bgColor: 'transparent',
            opacityOver: 1.00,
            opacityOut: 0.5,
            opacitySpeed: 3,
            fov: 800,
            speed: {$speed},
            fontFamily: 'Arial, sans-serif',
            fontSize: '14',
            fontColor: '#fff',
            fontWeight: 'normal',
            fontToUpperCase: false
        });
        
        console.log("SVG 3D标签云已应用");
        window.svg3DTagCloudInitialized = true;
        return true;
    }
    
    return false;
}

// 初始化标签云
function initTagCloud() {
    // 重置初始化标志，确保PJAX后能重新初始化
    window.svg3DTagCloudInitialized = false;
    
    // 首先尝试SVG 3D标签云
    if(!initSVG3DTagCloud()) {
        console.log("SVG 3D标签云初始化失败，尝试使用备用方案");
        
        // 尝试自动检测Handsome主题
        if(document.querySelector('#tag_cloud .tags') || document.querySelector('.widget_tag_cloud .tags') || document.querySelector('div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm')) {
            console.log("检测到Handsome主题，使用自动优化");
            initHandsomeTagCloud();
        } else {
            colorfultags("{$tagSelector}");
        }
    }
}

// 初始化
initTagCloud();

// 确保在页面完全加载后再次检查初始化
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (!window.svg3DTagCloudInitialized) {
            console.log("DOMContentLoaded: 重新尝试初始化SVG 3D标签云");
            initTagCloud();
        }
    }, 500);
});

// PJAX完成后重新应用
$(document).on("pjax:complete", function() {
    setTimeout(function() {
        console.log("PJAX完成: 重新初始化SVG 3D标签云");
        window.svg3DTagCloudInitialized = false;
        initTagCloud();
    }, 300);
});

// 处理页面可见性变化
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        setTimeout(function() {
            if (!window.svg3DTagCloudInitialized) {
                console.log("页面可见性变化: 重新初始化SVG 3D标签云");
                initTagCloud();
            }
        }, 300);
    }
});
</script>
<!-- End 3DColorfulTags -->
html;
			}
		} else {
			// 非PJAX模式下的输出
			if (!$is_3d) {
				// 不启用3D效果时的输出
				$html = <<<html
<!-- Start 3DColorfulTags -->
<link rel="stylesheet" type="text/css" href="{$static_src}/css/colorfultags.min.css">
<script src="{$static_src}/js/colorfultags.min.js"></script>
<script>
console.info("%c彩色标签云 - SVG版 | https://github.com/BXCQ/3DColorfulTags","line-height:28px;padding:4px;background:#3f51b5;color:#fff;font-size:14px;font-family:Microsoft YaHei;");
// 设置颜色变量
var colorfulTagsColors = {$colorVariable};

// 尝试自动检测Handsome主题
if(document.querySelector('#tag_cloud .tags') || document.querySelector('.widget_tag_cloud .tags') || document.querySelector('div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm')) {
    console.log("检测到Handsome主题，使用自动优化");
    initHandsomeTagCloud();
} else {
    colorfultags("{$tagSelector}");
}

// 确保在页面完全加载后执行
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // 尝试自动检测Handsome主题
        if(document.querySelector('#tag_cloud .tags') || document.querySelector('.widget_tag_cloud .tags') || document.querySelector('div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm')) {
            console.log("DOMContentLoaded: 检测到Handsome主题，使用自动优化");
            initHandsomeTagCloud();
        } else {
            colorfultags("{$tagSelector}");
        }
    }, 500);
});
</script>
<!-- End 3DColorfulTags -->
html;
			} else {
				// 启用3D效果时的输出
				$html = <<<html
<!-- Start 3DColorfulTags -->
<link rel="stylesheet" type="text/css" href="{$static_src}/css/colorfultags.min.css">
<link rel="stylesheet" type="text/css" href="{$static_src}/css/around3d.min.css">
<script src="{$static_src}/js/colorfultags.min.js"></script>
<script src="{$static_src}/js/svg3dtagcloud.min.js"></script>
<script>
console.info("%c彩色标签云 - SVG版 | https://github.com/BXCQ/3DColorfulTags","line-height:28px;padding:4px;background:#3f51b5;color:#fff;font-size:14px;font-family:Microsoft YaHei;");
// 设置颜色变量
var colorfulTagsColors = {$colorVariable};

// 检查是否已初始化，防止重复执行
if (typeof window.svg3DTagCloudInitialized === 'undefined') {
    window.svg3DTagCloudInitialized = false;
}

// 尝试使用SVG 3D标签云
function initSVG3DTagCloud() {
    if (window.svg3DTagCloudInitialized) {
        console.log("SVG 3D标签云已初始化，跳过");
        return true;
    }
    
    const tagContainers = document.querySelectorAll('#tag_cloud .tags, .widget_tag_cloud .tags, div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm');
    if(tagContainers.length === 0) return false;
    
    for(let container of tagContainers) {
        // 应用标签颜色
        const tags = container.querySelectorAll('a');
        if(tags.length === 0) return false;
        
        // 从标签中提取数据
        const entries = [];
        for(let tag of tags) {
            // 随机选择颜色
            const color = colorfulTagsColors[Math.floor(Math.random() * colorfulTagsColors.length)];
            entries.push({
                label: tag.textContent,
                url: tag.href,
                target: '_blank',
                color: color,
                fontSize: '14'
            });
            
            // 隐藏原始标签
            tag.style.display = 'none';
        }
        
        // 清空容器内容但保留容器
        container.innerHTML = '';
        
        // 创建3D标签云
        const cloud = new SVG3DTagCloud(container, {
            entries: entries,
            width: '100%',
            height: '250px',
            radius: '70%',
            radiusMin: 75,
            bgDraw: false,
            bgColor: 'transparent',
            opacityOver: 1.00,
            opacityOut: 0.5,
            opacitySpeed: 3,
            fov: 800,
            speed: {$speed},
            fontFamily: 'Arial, sans-serif',
            fontSize: '14',
            fontColor: '#fff',
            fontWeight: 'normal',
            fontToUpperCase: false
        });
        
        console.log("SVG 3D标签云已应用");
        window.svg3DTagCloudInitialized = true;
        return true;
    }
    
    return false;
}

// 初始化标签云
function initTagCloud() {
    // 重置初始化标志，确保刷新后能重新初始化
    window.svg3DTagCloudInitialized = false;
    
    // 首先尝试SVG 3D标签云
    if(!initSVG3DTagCloud()) {
        console.log("SVG 3D标签云初始化失败，尝试使用备用方案");
        
        // 尝试自动检测Handsome主题
        if(document.querySelector('#tag_cloud .tags') || document.querySelector('.widget_tag_cloud .tags') || document.querySelector('div.tags.l-h-2x.panel.wrapper-sm.padder-v-ssm')) {
            console.log("检测到Handsome主题，使用自动优化");
            initHandsomeTagCloud();
        } else {
            colorfultags("{$tagSelector}");
        }
    }
}

// 初始化
initTagCloud();

// 确保在页面完全加载后再次检查初始化
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (!window.svg3DTagCloudInitialized) {
            console.log("DOMContentLoaded: 重新尝试初始化SVG 3D标签云");
            initTagCloud();
        }
    }, 500);
});

// 处理页面可见性变化
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        setTimeout(function() {
            if (!window.svg3DTagCloudInitialized) {
                console.log("页面可见性变化: 重新初始化SVG 3D标签云");
                initTagCloud();
            }
        }, 300);
    }
});
</script>
<!-- End 3DColorfulTags -->
html;
			}
		}
		echo $html;
	}
	
	/**
	 * 根据颜色风格获取颜色变量JSON
	 * @param string $style 颜色风格
	 * @return string JSON格式的颜色数组
	 */
	private static function getColorVariable($style)
	{
		switch ($style) {
			case 'deep':
				// 深色系
				$colors = [
					"#1A237E",
					"#0D47A1",
					"#003366",
					"#01579B",
					"#0D2C54",
					"#4A148C",
					"#311B92",
					"#673AB7",
					"#4B0082",
					"#3E2A77",
					"#B71C1C",
					"#880E4F",
					"#800020",
					"#9B1B30",
					"#5E1914",
					"#1B5E20",
					"#004D40",
					"#0A3B2C",
					"#006064",
					"#2E7D32",
					"#3E2723",
					"#263238",
					"#4E342E",
					"#37474F",
					"#212121"
				];
				break;
			
			case 'bright':
				// 明亮系
				$colors = [
					"#2196F3",
					"#03A9F4",
					"#00BCD4",
					"#009688",
					"#4CAF50",
					"#8BC34A",
					"#CDDC39",
					"#FFEB3B",
					"#FFC107",
					"#FF9800",
					"#FF5722",
					"#F44336",
					"#E91E63",
					"#9C27B0",
					"#673AB7",
					"#3F51B5",
					"#1E88E5",
					"#26A69A",
					"#66BB6A",
					"#29B6F6"
				];
				break;
				
			case 'pastel':
				// 粉彩系
				$colors = [
					"#BBDEFB",
					"#B3E5FC",
					"#B2EBF2",
					"#B2DFDB",
					"#C8E6C9",
					"#DCEDC8",
					"#F0F4C3",
					"#FFF9C4",
					"#FFECB3",
					"#FFE0B2",
					"#FFCCBC",
					"#FFCDD2",
					"#F8BBD0",
					"#E1BEE7",
					"#D1C4E9",
					"#C5CAE9",
					"#90CAF9",
					"#81D4FA",
					"#80DEEA",
					"#80CBC4"
				];
				break;
				
			case 'contrast':
				// 高对比度
				$colors = [
					"#D50000",
					"#C51162",
					"#AA00FF",
					"#6200EA",
					"#304FFE",
					"#2962FF",
					"#0091EA",
					"#00B8D4",
					"#00BFA5",
					"#00C853",
					"#64DD17",
					"#AEEA00",
					"#FFD600",
					"#FFAB00",
					"#FF6D00",
					"#FF3D00",
					"#B71C1C",
					"#880E4F",
					"#4A148C",
					"#311B92"
				];
				break;
				
			default:
				// 默认深色系
				$colors = [
					"#1A237E",
					"#0D47A1",
					"#003366",
					"#01579B",
					"#0D2C54",
					"#4A148C",
					"#311B92",
					"#673AB7",
					"#4B0082",
					"#3E2A77",
					"#B71C1C",
					"#880E4F",
					"#800020",
					"#9B1B30",
					"#5E1914",
					"#1B5E20",
					"#004D40",
					"#0A3B2C",
					"#006064",
					"#2E7D32",
					"#3E2723",
					"#263238",
					"#4E342E",
					"#37474F",
					"#212121"
				];
		}
		
		return json_encode($colors);
	}
} 