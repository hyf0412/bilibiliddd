<?php
// @xinruanj
declare(strict_types=1);

/**
 * 模板引擎类 - 处理模板渲染和标签替换
 */
class TemplateEngine {
    // 修改常量定义，使用绝对路径
    private const TEMPLATE_DIR = __DIR__ . '/../config/templates/';
    private const DATA_DIR = __DIR__ . '/../config/data/';
    private const FILES_DIR = __DIR__ . '/../public/static/files/';  // 文件资源目录
    
    // 类属性
    private string $content = '';             // 当前处理的内容
    private array $tagProcessors = [];        // 标签处理器集合
    private string $siteId;                   // 站点唯一标识
    private Context $context;                 // 上下文对象
    
    /**
     * 构造函数 - 初始化模板引擎
     * @param Context $context 上下文对象，提供Redis等资源访问
     */
    public function __construct(Context $context) {
        $this->siteId = hash('crc32b', realpath($_SERVER['DOCUMENT_ROOT']));
        $this->context = $context;
        $this->initializeTagProcessors();
    }
    
    /**
     * 初始化标签处理器
     * 配置所有支持的标签类型及其处理方式
     */
    private function initializeTagProcessors(): void 
    {
        // 生成器标签处理器 (优先级最高)
        $this->tagProcessors['generator'] = [
            'pattern' => '/{(string|alpha_numeric|hex|digits|alpha_upper|alpha_lower)(?:=(\d+))?}/',
            'callback' => function($matches) {
                $length = isset($matches[2]) ? (int)$matches[2] : 8;
                return $this->processGeneratorTags($matches[1], $length);
            }
        ];
        
        // 随机文件标签处理器
        $this->tagProcessors['file'] = [
            'pattern' => '/{rand_file}/',
            'callback' => [$this, 'processRandFile']
        ];
        
        // 时间戳标签
        $this->tagProcessors['timestamp'] = [
            'pattern' => '/{timestamp\_(year|month|day|hour|minute|second|datetime|random)}/',
            'callback' => [$this, 'processTimestampTags']
        ];
        
        // 本地数据处理器 (优先级最低，避免误匹配其他标签)
        $this->tagProcessors['local_data'] = [
            'pattern' => '/{(rand_)?([a-zA-Z_]+)(\d+)?}/',
            'callback' => [$this, 'processLocalDataTagCallback']
        ];
    }
    
    /**
     * 处理生成器标签
     * @param string $type 标签类型
     * @param int $length 标签长度
     * @return string 生成的随机字符串
     */
    private function processGeneratorTags(string $type, int $length): string 
    {
        $charSets = [
            'alpha_numeric' => 'abcdefghijklmnopqrstuvwxyz0123456789',
            'string' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'digits' => '123456789',
            'hex' => 'abcdef0123456789',
            'alpha_upper' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'alpha_lower' => 'abcdefghijklmnopqrstuvwxyz'
        ];
        
        if (!isset($charSets[$type])) {
            return '';
        }
        
        return $this->generateRandomChars($length, $charSets[$type]);
    }
    
    /**
     * 检查标签是否需要编码
     */
    private function checkNeedEncode(string $tag): bool
    {
        $files = glob(self::DATA_DIR . "#{$tag}#[01].txt");
        if (!empty($files)) {
            if (preg_match('/#.+#([01])\.txt/', basename($files[0]), $matches)) {
                return isset($matches[1]) && $matches[1] === '1';
            }
        }
        return false;
    }
    
    /**
     * 处理本地数据标签的回调函数
     */
    private function processLocalDataTagCallback(array $matches): string 
    {
        try {
            $isRandom = !empty($matches[1]);
            $baseTag = $matches[2];
            $suffix = $matches[3] ?? '';
            
            $redis = $this->context->getRedis();
            $redis->select($this->context->getConfig()['redis']['databases']['local_data']);
            
            $redisKey = "site:{$this->siteId}:local_data:{$baseTag}";
            $needEncode = $this->checkNeedEncode($baseTag);
            
            if (!$isRandom) {
                $cacheKey = "local_data:{$baseTag}{$suffix}";
                if (!$this->context->hasCache($cacheKey)) {
                    $value = $redis->sRandMember($redisKey);
                    // 只在显示时编码
                    if ($value !== false && $needEncode) {
                        $value = mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');
                    }
                    $this->context->setCache($cacheKey, $value ?: '');
                }
                return $this->context->getCache($cacheKey);
            }
            
            $value = $redis->sRandMember($redisKey);
            // 只在显示时编码
            return ($value !== false && $needEncode) ? 
                mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8') : 
                ($value ?: '');
                
        } catch (Throwable $e) {
            error_log("Tag error: {$e->getMessage()}");
            return '';
        }
    }
    
    /**
     * 同步本地数据到Redis
     */
    private function syncLocalDataToRedis(): self {
        try {
            $redis = $this->context->getRedis();
            $redis->select($this->context->getConfig()['redis']['databases']['local_data']);
            
            foreach (glob(self::DATA_DIR . '#*#[01].txt') as $file) {
                if (!preg_match('/#(.+)#[01]\.txt/', basename($file), $matches)) continue;
                
                $tag = $matches[1];
                $key = "site:{$this->siteId}:local_data:{$tag}";
                $timeKey = "{$key}:updated_at";
                
                $fileTime = filemtime($file);
                if (!$redis->exists($key) || 
                    !($redisTime = $redis->get($timeKey)) || 
                    $fileTime > (int)$redisTime) {
                    
                    $lines = array_filter(explode("\n", file_get_contents($file) ?: ''));
                    if (empty($lines)) continue;
                    
                    $redis->exists($key) && $redis->del($key);
                    $redis->sAddArray($key, $lines);
                    $redis->set($timeKey, $fileTime);
                }
            }
        } catch (Throwable $e) {
            error_log("Sync error: {$e->getMessage()}");
        }
        return $this;
    }

    /**
     * 渲染模板
     */
    public function render(): string {
        try {
            $this->content = $this->loadTemplate();
            return $this->processTags();
        } catch (Throwable $e) {
            error_log("Render error: {$e->getMessage()}");
            throw $e;
        }
    }
    
    private function processTags(): string {
        // 先处理基础标签
        $this->syncLocalDataToRedis()
             ->processSiteTags()
             ->processArticleContent();
        
        // 按顺序处理所有标签（包括本地数据标签）
        foreach ($this->tagProcessors as $processor) {
            $this->content = preg_replace_callback(
                $processor['pattern'],
                $processor['callback'],
                $this->content
            );
        }
        
        return $this->content;
    }
    
    /**
     * 处理站点基础标签
     */
    private function processSiteTags(): self {
        $replacements = [
            '{site_title}' => $this->context->getSiteConfig('titles'),
            '{site_keywords}' => $this->context->getSiteConfig('keywords'),
            '{site_description}' => $this->context->getSiteConfig('descriptions')
        ];
        
        foreach ($replacements as $tag => $value) {
            $this->content = str_replace($tag, $value, $this->content);
        }
        return $this;
    }
    
    /**
     * 加载随机模板
     * @return string 模板内容
     * @throws RuntimeException 当没有找到模板时
     */
    private function loadTemplate(): string {
        $templates = glob(self::TEMPLATE_DIR . '*.html');
        if (empty($templates)) {
            throw new RuntimeException("No templates");
        }
        $template = $templates[array_rand($templates)];
        return file_get_contents($template) ?: '';
    }
    
    /**
     * 生成指定长度的随机字符串
     * @param int $count 字符串长度
     * @param string $chars 字符集
     * @return string 生成的随机字符串
     */
    private function generateRandomChars(int $count, string $chars): string 
    {
        return substr(str_shuffle(str_repeat($chars, (int)($count / strlen($chars)) + 1)), 0, $count);
    }

    /**
     * 处理文章内容标签
     * @return self 链式调用
     */
    private function processArticleContent(): self {
        try {
            $redis = $this->context->getRedis();
            $redis->select($this->context->getConfig()['redis']['databases']['article']);
            
            if (strpos($this->content, '{article_title}') !== false || 
                strpos($this->content, '{article_content}') !== false) {
                
                $articleNum = (int)$redis->get('article_num');
                if ($articleNum > 0) {
                    $articleId = mt_rand(1, $articleNum);
                    $articleData = $redis->hmget("article:$articleId", ['title', 'content']);
                    $this->content = str_replace(
                        ['{article_title}', '{article_content}'],
                        [$articleData[0] ?? '', $articleData[1] ?? ''],
                        $this->content
                    );
                }
            }
            
            if (strpos($this->content, '{rand_article_title}') !== false) {
                $this->content = preg_replace_callback(
                    '/{rand_article_title}/',
                    fn() => $redis->sRandMember('article_title_set') ?? '',
                    $this->content
                );
            }
        } catch (Throwable $e) {
            error_log("Article error: {$e->getMessage()}");
        }
        return $this;
    }

    private function processRandFile(): string {
        try {
            $cacheKey = 'files_list';
            $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
            
            if (!$this->context->hasCache($cacheKey)) {
                // 确保目录存在
                $filesDir = realpath(self::FILES_DIR);
                if (!$filesDir) {
                    return '';
                }
                
                // 获取文件列表
                $files = glob($filesDir . '/*.*');
                if (empty($files)) {
                    return '';
                }
                
                $this->context->setCache($cacheKey, $files);
            }
            
            $files = $this->context->getCache($cacheKey);
            if (empty($files)) {
                return '';
            }
            
            // 选择随机文件并生成URL路径
            $file = $files[array_rand($files)];
            return str_replace($docRoot, '', $file);
        } catch (Throwable $e) {
            return '';
        }
    }

    private function processTimestampTags(array $matches): string {
        $type = $matches[1];
        switch ($type) {
            case 'year':    return date('Y');
            case 'month':   return date('m');
            case 'day':     return date('d');
            case 'hour':    return date('H');
            case 'minute':  return date('i');
            case 'second':  return date('s');
            case 'random':  
                $cacheKey = "timestamp_random";
                if (!$this->context->hasCache($cacheKey)) {
                    $randomTime = time() - mt_rand(0, 3600);
                    $this->context->setCache($cacheKey, date('Y-m-d H:i:s', $randomTime));
                }
                return $this->context->getCache($cacheKey);
            case 'datetime':
            default:        return date('Y-m-d H:i:s');
        }
    }
} 