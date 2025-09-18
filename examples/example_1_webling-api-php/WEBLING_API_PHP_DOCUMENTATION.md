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
