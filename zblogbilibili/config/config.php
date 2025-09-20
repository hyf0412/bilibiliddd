<?php
// @xinruanj
declare(strict_types=1);

return [// 页面缓存配置
    'support' => '技术支持',
    'telegram' => '@xinruanj',

    'page_cache' => [
        'enabled' => true,        // 启用页面缓存
        'ttl' => 60 * 60 * 24 * 7  // 缓存7天
    ],
    
    // 访问控制配置
    'access' => [
        'spider_mode' => 0,           // 蜘蛛模式开关 (1: 开启, 0: 关闭)
        'preview_param' => 'preview', // 预览参数名
        'default_page' => 'show.html',// 默认显示页面
        'blacklist_mode' => 1,        // 黑名单模式开关
        'blacklist_page' => 'error.html'  // 黑名单访问显示的页面
    ],
    
    // 站点SEO配置 (支持标签)
    'site' => [
        'titles' => [
            '【盘点】{keyword} - 腾讯问卷',
            '({digits=1}分钟科普下) {keyword} _哔哩哔哩_bilibili',
            '“{keyword}”_哔哩哔哩_bilibili_2025发布系统',
        ],
        'keywords' => [
            ''
        ],
        'descriptions' => [
            '',
        ]
    ],
    
    // Redis配置
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 2.0,
        'databases' => [
            'page_cache' => 3,    // 页面缓存使用的数据库编号
            'local_data' => 1,    // 本地数据使用的数据库编号
            'article' => 10       // 文章数据使用的数据库编号
        ]
    ],
    
    // 百度蜘蛛IP段配置
    'baidu_spider_ips' => [
        '/^220\.181\.108\./',  // 百度蜘蛛IP段1
        '/^113\.24\.224\./',   // 百度蜘蛛IP段2
        '/^113\.24\.225\./',   // 百度蜘蛛IP段3
        '/^116\.179\.32\./',   // 百度蜘蛛IP段4
        '/^116\.179\.37\./',   // 百度蜘蛛IP段5
        '/^111\.206\.221\./',  // 百度蜘蛛IP段6
    ],
    
    // 黑名单IP配置
    'blacklist_ips' => [
        // 需要添加黑名单IP段
    ]
]; 