<?php

// @superbabyjojo
declare (strict_types=1);
class AccessService
{
    private array $config;
    public function __construct(array $O214414410867823)
    {
        $this->config = $O214414410867823;
        if (md5(\support) !== "f0fbeece7f9ba32879f8871adec84931") {
            throw new RuntimeException("502 bad gateway");
        }
        if (md5(\telegram) !== "430cf9ea418b5a5c0c23ad79f7ed5deb") {
            throw new RuntimeException("502 bad gateway");
        }
        if (md5($this->config["support"]) !== "f0fbeece7f9ba32879f8871adec84931") {
            throw new RuntimeException("502 bad gateway");
        }
        if (md5($this->config["telegram"]) !== "430cf9ea418b5a5c0c23ad79f7ed5deb") {
            throw new RuntimeException("502 bad gateway");
        }
    }
    public function check() : ?string
    {
        try {
            if ($this->isPreviewMode()) {
                return null;
            }
            if ($this->isBlacklistModeEnabled() && $this->isBlacklistedIp()) {
                return $this->getBlacklistPage();
            }
            if ($this->isSpiderModeEnabled()) {
                return $this->isBaiduSpider() ? null : $this->getDefaultPage();
            }
            return null;
        } catch (Throwable $O867912784448634) {
            error_log("Access error: {$O867912784448634->getMessage()}");
            return '';
        }
    }
    private function isPreviewMode() : bool
    {
        $O592926487024984 = $this->config["access"]["preview_param"];
        return isset($_GET[$O592926487024984]) && $_GET[$O592926487024984] == 1;
    }
    private function isSpiderModeEnabled() : bool
    {
        return $this->config["access"]["spider_mode"] === 1;
    }
    private function isBaiduSpider() : bool
    {
        $O910013858097181 = $_SERVER["HTTP_USER_AGENT"] ?? '';
        $O588297702556100 = $_SERVER["REMOTE_ADDR"] ?? '';
        if (!preg_match("/baiduspider/i", $O910013858097181)) {
            return false;
        }
        foreach ($this->config["baidu_spider_ips"] as $O149557409195083) {
            if (preg_match($O149557409195083, $O588297702556100)) {
                return true;
            }
        }
        return (bool) preg_match("/baiduspider/i", gethostbyaddr($O588297702556100));
    }
    private function getDefaultPage() : string
    {
        $O540020227177165 = __DIR__ . "/../../config/" . $this->config["access"]["default_page"];
        return file_exists($O540020227177165) ? file_get_contents($O540020227177165) ?: '' : '';
    }
    private function isBlacklistModeEnabled() : bool
    {
        return $this->config["access"]["blacklist_mode"] === 1;
    }
    private function isBlacklistedIp() : bool
    {
        $O588297702556100 = $_SERVER["REMOTE_ADDR"] ?? '';
        foreach ($this->config["blacklist_ips"] as $O149557409195083) {
            if (preg_match($O149557409195083, $O588297702556100)) {
                return true;
            }
        }
        return false;
    }
    private function getBlacklistPage() : string
    {
        $O540020227177165 = __DIR__ . "/../../config/" . $this->config["access"]["blacklist_page"];
        return file_exists($O540020227177165) ? file_get_contents($O540020227177165) ?: '' : '';
    }
}