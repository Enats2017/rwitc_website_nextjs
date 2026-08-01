<?php
/**
 * ApiSecurity.php
 * ─────────────────────────────────────────────────────────────────
 * Drop-in security middleware for your public PHP APIs.
 *
 * Features:
 *   1. IP + API-based rate limiting  (per-API limits, file-backed)
 *   2. File-based response caching (configurable TTL)
 *   3. Standard JSON: {success, data, error}
 *   4. Input validation helpers
 *
 * Works WITH your existing CommonClass, db_query(), escape(), logLine().
 * Does NOT replace CommonClass — wraps around it.
 * ─────────────────────────────────────────────────────────────────
 */

class ApiSecurity
{
    private $handle;
    private int    $rateLimit;
    private int    $rateWindow;
    private int    $cacheTtl;
    private string $cacheDir;
    private string $rateLimitDir;
    private string $apiTag;

    public function __construct($handle, array $opts = [])
    {
        $this->handle       = $handle;
        $this->rateLimit    = (int) ($opts['rate_limit']     ?? 60);
        $this->rateWindow   = (int) ($opts['rate_window']    ?? 60);
        $this->cacheTtl     = (int) ($opts['cache_ttl']      ?? 45);
        $this->cacheDir     = rtrim($opts['cache_dir']       ?? dirname(__FILE__) . '/cache', '/');
        $this->rateLimitDir = rtrim($opts['rate_limit_dir']  ?? dirname(__FILE__) . '/rate_limits', '/');
        $this->apiTag       = $opts['api_tag'] ?? basename($_SERVER['SCRIPT_FILENAME'] ?? 'unknown', '.php');

        foreach ([$this->cacheDir, $this->rateLimitDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0750, true);
            }
        }

        header('Content-Type: application/json; charset=utf-8');
    }

    /* ══════════════════════════════════════════════════════════════════════
     *  1. IP + API-BASED RATE LIMITING  (sliding window, file-backed)
     * ══════════════════════════════════════════════════════════════════════ */

    public function gate(): bool
    {
        $ip   = $this->getClientIp();
        $file = $this->rateLimitDir . '/' . md5($this->apiTag . '|' . $ip) . '.json';
        $now  = time();

        $bucket = ['hits' => [], 'blocked_until' => 0];
        if (file_exists($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $bucket = $decoded;
            }
        }

        if ($now < ($bucket['blocked_until'] ?? 0)) {
            $retryAfter = $bucket['blocked_until'] - $now;
            $this->sendRateLimited($retryAfter, $ip);
            return false;
        }

        $windowStart        = $now - $this->rateWindow;
        $bucket['hits']     = array_values(array_filter(
            $bucket['hits'] ?? [],
            function($ts) use ($windowStart) { return $ts >= $windowStart; }
        ));

        if (count($bucket['hits']) >= $this->rateLimit) {
            $bucket['blocked_until'] = $now + $this->rateWindow;
            $this->writeBucket($file, $bucket);
            $this->logLine("RATE_LIMIT | IP: {$ip} | API: {$this->apiTag} | blocked for {$this->rateWindow}s");
            $this->sendRateLimited($this->rateWindow, $ip);
            return false;
        }

        $bucket['hits'][] = $now;
        $this->writeBucket($file, $bucket);
        return true;
    }

    private function writeBucket(string $file, array $bucket): void
    {
        $tmp = $file . '.' . getmypid() . '.tmp';
        @file_put_contents($tmp, json_encode($bucket), LOCK_EX);
        @rename($tmp, $file);
    }

    private function sendRateLimited(int $retryAfter, string $ip): void
    {
        http_response_code(429);
        header("Retry-After: {$retryAfter}");
        echo json_encode([
            'success' => false,
            'data'    => null,
            'error'   => "Rate limit exceeded. Try again in {$retryAfter}s.",
        ]);
    }

    public function getClientIp(): string
    {
        foreach ([
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ] as $header) {
            $val = isset($_SERVER[$header]) ? $_SERVER[$header] : '';
            if ($val === '') continue;
            $ip = trim(explode(',', $val)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
        return '0.0.0.0';
    }

    /* ══════════════════════════════════════════════════════════════════════
     *  2. FILE-BASED RESPONSE CACHING
     * ══════════════════════════════════════════════════════════════════════ */

    /**
     * Build the cache file path for a tag.
     *
     * NOTE: This version no longer hashes request params into the filename.
     * The cache file is now purely {tag}.json or {tag}_{scopeToken}.json.
     * That means ALL requests to a given tag (+ scope) share ONE cache file,
     * regardless of query/POST/body params. Only use this when the endpoint's
     * response doesn't vary by param, or when `scope` alone is enough to
     * differentiate responses (e.g. device_id-scoped home data).
     *
     * $scope (optional) embeds a stable, glob-able segment in the FILENAME
     * so a subset of a tag's cache files can be cleared by name from another
     * endpoint. Used for device-scoped caches: the home API scopes home_data
     * by device_id, and the save-tracking-logs API clears just that device's
     * home_data via clearCacheByScope(). The scope is tokenised here (see
     * scopeSegment) so callers only ever pass the raw value (e.g. a device_id)
     * and both ends derive the same segment.
     */
    private function cacheFile(string $tag, string $scope = ''): string
    {
        $scopeSeg = $this->scopeSegment($scope);
        return $this->cacheDir . "/{$tag}{$scopeSeg}.json";
    }

    /**
     * Tokenise a scope value into a fixed-length, filename-safe segment.
     * Returns '' for an empty scope (→ unscoped filename, original behaviour),
     * otherwise '_<16 hex>'. md5-substring keeps it short, glob-safe (no
     * special chars) and identical for the same input across endpoints, so the
     * writer (home API) and the clearer (save-logs API) always agree.
     */
    private function scopeSegment(string $scope): string
    {
        $scope = trim($scope);
        if ($scope === '') return '';
        return '_' . substr(md5($scope), 0, 16);
    }

    /**
     * Serve cached response if fresh. Returns true if served.
     * $scope must match the value used at respondAndCache() time.
     */
    public function serveCache(string $tag, string $scope = ''): bool
    {
        if ($this->cacheTtl <= 0) return false;

        $file = $this->cacheFile($tag, $scope);
        if (!file_exists($file)) return false;

        $age = time() - filemtime($file);
        if ($age > $this->cacheTtl) {
            @unlink($file);
            return false;
        }

        $raw = @file_get_contents($file);
        if ($raw === false) return false;

        header('X-Cache: HIT');
        header("X-Cache-Age: {$age}");
        echo $raw;
        return true;
    }

    private function writeCache(string $tag, string $json, string $scope = ''): void
    {
        $file = $this->cacheFile($tag, $scope);
        $tmp  = $file . '.' . getmypid() . '.tmp';
        @file_put_contents($tmp, $json, LOCK_EX);
        @rename($tmp, $file);
    }

    /**
     * Delete all cached responses for a given tag.
     * Cache files now follow the pattern: {cacheDir}/{tag}.json (unscoped)
     * and {cacheDir}/{tag}_{scopeToken}.json (scoped). Both patterns are
     * globbed here so this still clears everything for the tag, scoped or not.
     *
     * @param  string $tag  Cache tag, e.g. 'event_list'
     * @return int          Number of files deleted
     */
    public function clearCacheByTag(string $tag): int
    {
        $deleted = 0;
        $patterns = [
            $this->cacheDir . '/' . $tag . '.json',    // unscoped
            $this->cacheDir . '/' . $tag . '_*.json',  // scoped
        ];

        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            if (!is_array($files)) continue;
            foreach ($files as $file) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            $this->logLine("CACHE_CLEAR | tag: {$tag} | deleted: {$deleted} file(s)");
        }

        return $deleted;
    }

    /**
     * Delete the cached response for a given tag AND scope only.
     * Targets the single file {cacheDir}/{tag}_{scopeToken}.json — i.e. the
     * one home_data cache for one device_id. An empty scope is a no-op
     * (returns 0) — by design, so a missing device_id never falls through
     * to clearing the whole tag.
     *
     * @param  string $tag    Cache tag, e.g. 'home_data'
     * @param  string $scope  Raw scope value, e.g. a device_id
     * @return int            Number of files deleted (0 or 1)
     */
    public function clearCacheByScope(string $tag, string $scope): int
    {
        $scopeSeg = $this->scopeSegment($scope);
        if ($scopeSeg === '') return 0;   // empty scope → never clear the whole tag

        $file = $this->cacheDir . '/' . $tag . $scopeSeg . '.json';
        $deleted = 0;

        if (file_exists($file) && @unlink($file)) {
            $deleted = 1;
        }

        if ($deleted > 0) {
            $this->logLine("CACHE_CLEAR | tag: {$tag} | scope: {$scopeSeg} | deleted: {$deleted} file(s)");
        }

        return $deleted;
    }

    /* ══════════════════════════════════════════════════════════════════════
     *  3. STANDARD JSON RESPONSES
     * ══════════════════════════════════════════════════════════════════════ */

    public function respondAndCache(string $cacheTag, $data, string $scope = ''): void
    {
        $json = json_encode([
            'success' => true,
            'data'    => $data,
            'error'   => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        header('X-Cache: MISS');
        $this->writeCache($cacheTag, $json, $scope);
        echo $json;
    }

    public function respondSuccess($data): void
    {
        echo json_encode([
            'success' => true,
            'data'    => $data,
            'error'   => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function respondError(string $message, int $httpCode = 400): void
    {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'data'    => null,
            'error'   => $message,
        ]);
    }

    /* ══════════════════════════════════════════════════════════════════════
     *  4. INPUT VALIDATION HELPERS
     * ══════════════════════════════════════════════════════════════════════ */

    public function validateParam($source, string $name, string $type = 'string', $default = null)
    {
        $raw = isset($source[$name]) ? $source[$name] : null;
        if ($raw === null || $raw === '') return $default;

        $raw = trim((string)$raw);

        switch ($type) {
            case 'int':
                return ctype_digit(ltrim($raw, '-')) ? (int)$raw : $default;

            case 'positive_int':
                return (ctype_digit($raw) && (int)$raw > 0) ? (int)$raw : $default;

            case 'float':
                return is_numeric($raw) ? (float)$raw : $default;

            case 'alpha':
                return ctype_alpha($raw) ? $raw : $default;

            case 'alnum':
                return ctype_alnum($raw) ? $raw : $default;

            case 'date':
                $d = DateTime::createFromFormat('Y-m-d', $raw);
                return ($d && $d->format('Y-m-d') === $raw) ? $raw : $default;

            case 'string':
            default:
                $raw = str_replace("\0", '', $raw);
                return mb_substr($raw, 0, 1000);
        }
    }

    /* ══════════════════════════════════════════════════════════════════════
     *  LOGGING
     * ══════════════════════════════════════════════════════════════════════ */

    public function logLine(string $line): void
    {
        if (!is_resource($this->handle)) return;
        fwrite($this->handle, "[" . date('Y-m-d H:i:s') . "] " . $line . PHP_EOL);
    }

    /* ══════════════════════════════════════════════════════════════════════
     *  CLEANUP  (call from cron)
     * ══════════════════════════════════════════════════════════════════════ */

    public function cleanup(int $maxAgeSeconds = 3600): int
    {
        $deleted = 0;
        foreach ([$this->cacheDir, $this->rateLimitDir] as $dir) {
            $files = glob($dir . '/*.json');
            if (!is_array($files)) continue;
            foreach ($files as $f) {
                if (time() - filemtime($f) > $maxAgeSeconds) {
                    @unlink($f);
                    $deleted++;
                }
            }
        }
        return $deleted;
    }
}