# Webling PHP API Client Technical Documentation

## Table of Contents
1. [Core Architecture](#core-architecture)
2. [Client Configuration](#client-configuration)
3. [API Operations](#api-operations)
4. [Caching System](#caching-system)
5. [Advanced Usage](#advanced-usage)
6. [Error Handling](#error-handling)
7. [Testing Implementation](#testing-implementation)

## Core Architecture

### HTTP Client Implementation
```php
// src/Webling/API/CurlHttp.php
class CurlHttp {
    private $ch;
    
    public function __construct($endpoint, $apiKey, $options) {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_CONNECTTIMEOUT => $options['connecttimeout'],
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_USERAGENT => $options['useragent']
        ]);
    }

    public function get($path) {
        curl_setopt($this->ch, CURLOPT_URL, $this->endpoint.$path);
        return $this->execute();
    }
    
    private function execute() {
        $response = curl_exec($this->ch);
        $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        return new Response($status, $response);
    }
}
```

### Class Structure
```php
// src/Webling/API/Client.php
class Client implements IClient {
    private $endpoint;
    private $apiKey;
    private $options;
    
    public function __construct($endpoint, $apiKey, $options = []) {
        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey = $apiKey;
        $this->options = array_merge([
            'connecttimeout' => 5,
            'timeout' => 30,
            'useragent' => 'Webling PHP API Client/1.3'
        ], $options);
    }
    
    public function get($path) {
        return $this->request('GET', $path);
    }
    
    // PUT, POST, DELETE methods...
}

// src/Webling/Cache/FileCache.php
class FileCache implements ICache {
    public function getObject($type, $id) {
        $key = $this->createKey($type, $id);
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $data = $this->client->get("/$type/$id");
        $this->set($key, $data);
        return $data;
    }
}
```

## Client Configuration

### Composer Requirements
```php
// composer.json
{
    "require": {
        "php": ">=5.6.0",
        "ext-curl": "*",
        "ext-json": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^4.8"
    }
}
```

### Basic Setup
```php
require 'vendor/autoload.php';

$client = new Webling\API\Client(
    'https://yourdomain.webling.ch',
    'your_api_key_here',
    [
        'timeout' => 15,
        'useragent' => 'MyApp/1.0',
        'ssl_verify' => true
    ]
);
```

### Custom Cache Configuration
```php
$adapter = new Webling\CacheAdapters\FileCacheAdapter([
    'directory' => __DIR__.'/cache',
    'ttl' => 3600 // 1 hour cache
]);

$cache = new Webling\Cache\Cache($client, $adapter);
```

## API Operations

### Response Handling
```php
// src/Webling/API/Response.php
class Response implements IResponse {
    private $statusCode;
    private $headers;
    private $data;
    
    public function __construct($statusCode, $rawData) {
        $this->statusCode = $statusCode;
        $this->parseHeaders($rawData);
        $this->data = json_decode($rawData, true) ?? $rawData;
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function getData() {
        return $this->data;
    }
}
```

### Basic CRUD Operations
```php
// Create member
$response = $client->post('/member', [
    'properties' => [
        'Vorname' => 'Max',
        'Name' => 'Muster'
    ]
]);

// Update member
$client->put('/member/123', [
    'properties' => [
        'Email' => 'max@example.com'
    ]
]);

// Get member group hierarchy
$groups = $cache->getRoot('membergroup');
```

## Caching System

### Cache Interface Definition
```php
// src/Webling/Cache/ICache.php
interface ICache {
    public function getObject($type, $id);
    public function getObjects($type, $ids);
    public function getRoot($type);
    public function clearCache();
    public function updateCache();
}

// src/Webling/CacheAdapters/ICacheAdapter.php
interface ICacheAdapter {
    public function set($key, $value, $ttl);
    public function get($key);
    public function clear();
}
```

### File Cache Adapter Implementation
```php
class FileCacheAdapter implements ICacheAdapter {
    public function set($key, $value, $ttl = 3600) {
        $cachePath = $this->getPath($key);
        $data = serialize([
            'expires' => time() + $ttl,
            'data' => $value
        ]);
        
        // Atomic write with lock
        $tmpFile = tempnam($this->directory, 'cache');
        file_put_contents($tmpFile, $data);
        rename($tmpFile, $cachePath);
    }

    public function get($key) {
        $cachePath = $this->getPath($key);
        if (!file_exists($cachePath)) return null;
        
        $data = unserialize(file_get_contents($cachePath));
        if (time() > $data['expires']) {
            unlink($cachePath);
            return null;
        }
        return $data['data'];
    }
}
```

## Advanced Usage

### CLI Usage Example
```php
// bin/webling
#!/usr/bin/env php
<?php
require __DIR__.'/../vendor/autoload.php';

$client = new Webling\API\Client($_ENV['WEBLING_ENDPOINT'], $_ENV['WEBLING_KEY']);

$command = $argv[1] ?? 'list';
switch ($command) {
    case 'get-member':
        $response = $client->get('/member/'.$argv[2]);
        print_r($response->getData());
        break;
    case 'clear-cache':
        $cache->clearCache();
        echo "Cache cleared\n";
        break;
}
```

### Image Proxy Example
```php
// examples/image_proxy.php
$client = new Webling\API\Client(ENDPOINT, API_KEY);
$cache = new Webling\Cache\Cache($client, new FileCacheAdapter());

$member = $cache->getObject('member', 123);
$imageUrl = $member['properties']['ProfileImage']['href'];

header('Content-Type: ' . $member['properties']['ProfileImage']['mimeType']);
echo $client->getBinary($imageUrl)->getRawData();
```

### Batch Request Handling
```php
$batch = [
    'member123' => ['method' => 'GET', 'path' => '/member/123'],
    'member456' => ['method' => 'GET', 'path' => '/member/456']
];

$results = $client->batchRequest($batch);

foreach ($results as $id => $response) {
    if ($response->getStatusCode() === 200) {
        echo "{$id}: ".json_encode($response->getData())."\n";
    }
}
```

## Error Handling

### Custom Exception Handling
```php
try {
    $client->get('/invalid/endpoint');
} catch (Webling\API\ClientException $e) {
    error_log("API Error {$e->getCode()}: {$e->getMessage()}");
    // Handle 4xx/5xx errors
}

// Retry mechanism
$retries = 0;
do {
    try {
        return $client->get('/member/123');
    } catch (ClientException $e) {
        $retries++;
        sleep(2 ** $retries);
    }
} while ($retries < 3);
```

## Testing Implementation

### Performance Test Implementation
```php
// tests/performance/ThroughputTest.php
class ThroughputTest {
    public function run($requests, $concurrency) {
        $client = new BulkClient(ENDPOINT, API_KEY);
        $client->enableCache(false);
        
        $times = [];
        for ($i = 0; $i < $requests; $i++) {
            $start = microtime(true);
            $client->get('/member/'.rand(100, 500));
            $times[] = microtime(true) - $start;
        }
        
        echo "Average response time: ".array_sum($times)/count($times)."s\n";
        echo "Requests per second: ".$requests/array_sum($times)."\n";
    }
}
```

### Test Configuration
```php
// tests/phpunit.xml.dist
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="bootstrap.php">
    <testsuites>
        <testsuite name="Webling API Tests">
            <directory>./Webling/API</directory>
            <directory>./Webling/Cache</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">../src</directory>
        </whitelist>
    </filter>
</phpunit>
```

### Mock HTTP Client
```php
// tests/Webling/API/Mocks/CurlHttpMock.php
class CurlHttpMock extends CurlHttp {
    private $mockResponses = [];
    
    public function addMockResponse($path, $status, $data) {
        $this->mockResponses[$path] = new Response($status, $data);
    }
    
    public function get($path) {
        return $this->mockResponses[$path] ?? new Response(404);
    }
}
```

### PHPUnit Test Case
```php
class ClientTest extends TestCase {
    public function testAuthenticationFailure() {
        $mockResponse = new Response(401, '{"error": "Unauthorized"}');
        $mockHttp = $this->createMock(CurlHttp::class);
        $mockHttp->method('get')->willReturn($mockResponse);
        
        $client = new Client('https://demo.webling.ch', 'bad_key', [
            'http_client' => $mockHttp
        ]);
        
        $this->expectException(ClientException::class);
        $client->get('/member/123');
    }
    
    public function testCacheInvalidation() {
        $adapter = new FileCacheAdapter(['directory' => './temp_cache']);
        $cache = new Cache(new Client(...), $adapter);
        
        $cache->set('test_key', 'value', 1);
        sleep(2);
        $this->assertNull($cache->get('test_key'));
    }
}
```

### Performance Benchmarks
```bash
# Run performance tests
php tests/performance/ThroughputTest.php --requests=100 --concurrency=10
```

[View Full API Documentation](https://demo.webling.ch/api)
