# Webling API PHP Client - Complete Reference Documentation

## Overview
**Library:** usystems/webling-api-php  
**Version:** 1.3.1  
**PHP Version:** >= 5.6.0  
**License:** MIT  
**Author:** Demian Holderegger (support@webling.ch)

This is the complete, self-contained documentation for the Webling API PHP client. This document serves as the definitive reference and contains all source code, examples, and API documentation in one comprehensive file.

## Table of Contents
1. [Installation & Setup](#installation--setup)
2. [Core API Documentation](#core-api-documentation)
3. [Caching System](#caching-system)
4. [Complete Source Code](#complete-source-code)
5. [Examples & Usage](#examples--usage)
6. [Testing](#testing)
7. [Configuration](#configuration)

---

# Installation & Setup

## Composer Installation
```bash
composer require usystems/webling-api-php
```

## Requirements
- PHP >= 5.6.0
- cURL extension
- JSON extension

## Basic Setup
```php
require 'vendor/autoload.php';

$client = new Webling\API\Client(
    'https://yourdomain.webling.ch',
    'your_api_key_here'
);
```

---

# Core API Documentation

## Client Class

### Constructor
```php
public function __construct($endpoint, $apiKey, $options = [])
```
- `$endpoint`: Webling API endpoint URL
- `$apiKey`: Your API key
- `$options`: Configuration options

### Available Options
```php
$options = [
    'connecttimeout' => 5,     // Connection timeout in seconds
    'timeout' => 30,          // Transfer timeout in seconds
    'useragent' => 'Custom UA' // Custom user agent string
];
```

### HTTP Methods
```php
// GET request
$response = $client->get('/member/123');

// POST request
$response = $client->post('/member', ['name' => 'John']);

// PUT request
$response = $client->put('/member/123', ['name' => 'Jane']);

// DELETE request
$response = $client->delete('/member/123');
```

### Response Handling
```php
if ($response->getStatusCode() < 400) {
    $data = $response->getData();        // Parsed JSON
    $raw = $response->getRawData();      // Raw response string
}
```

## Caching System

### Cache Setup
```php
$adapter = new Webling\CacheAdapters\FileCacheAdapter([
    'directory' => './cache'
]);

$cache = new Webling\Cache\Cache($client, $adapter);
```

### Cache Operations
```php
// Get single object
$member = $cache->getObject('member', 123);

// Get multiple objects
$members = $cache->getObjects('member', [123, 456, 789]);

// Get root objects (groups, etc.)
$groups = $cache->getRoot('membergroup');

// Update cache
$cache->updateCache();

// Clear cache
$cache->clearCache();
```

### Binary File Caching
```php
// Get binary data (images, documents)
$imageData = $cache->getObjectBinary(
    'member',
    123,
    $member['properties']['ProfileImage']['href']
);
```

---

# Complete Source Code

## Core API Classes

### Webling\API\Client
```php
<?php

namespace Webling\API;

class Client implements IClient
{
    private $endpoint;
    private $apiKey;
    private $options;

    public function __construct($endpoint, $apiKey, $options = [])
    {
        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey = $apiKey;
        $this->options = array_merge([
            'connecttimeout' => 5,
            'timeout' => 30,
            'useragent' => 'Webling PHP API Client/1.3'
        ], $options);
    }

    public function get($path)
    {
        return $this->request('GET', $path);
    }

    public function post($path, $data = null)
    {
        return $this->request('POST', $path, $data);
    }

    public function put($path, $data = null)
    {
        return $this->request('PUT', $path, $data);
    }

    public function delete($path)
    {
        return $this->request('DELETE', $path);
    }

    private function request($method, $path, $data = null)
    {
        $http = new CurlHttp($this->endpoint, $this->apiKey, $this->options);
        return $http->request($method, $path, $data);
    }
}
```

### Webling\API\CurlHttp
```php
<?php

namespace Webling\API;

class CurlHttp
{
    private $endpoint;
    private $apiKey;
    private $options;
    private $ch;

    public function __construct($endpoint, $apiKey, $options)
    {
        $this->endpoint = $endpoint;
        $this->apiKey = $apiKey;
        $this->options = $options;
        $this->initCurl();
    }

    private function initCurl()
    {
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_CONNECTTIMEOUT => $this->options['connecttimeout'],
            CURLOPT_TIMEOUT => $this->options['timeout'],
            CURLOPT_USERAGENT => $this->options['useragent']
        ]);
    }

    public function request($method, $path, $data = null)
    {
        $url = $this->endpoint . $path;

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data !== null) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($this->ch);
        $status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        if (curl_error($this->ch)) {
            throw new ClientException(curl_error($this->ch));
        }

        return new Response($status, $response);
    }

    public function __destruct()
    {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }
}
```

### Webling\API\Response
```php
<?php

namespace Webling\API;

class Response implements IResponse
{
    private $statusCode;
    private $headers;
    private $data;

    public function __construct($statusCode, $rawData)
    {
        $this->statusCode = $statusCode;
        $this->parseResponse($rawData);
    }

    private function parseResponse($rawData)
    {
        $parts = explode("\r\n\r\n", $rawData, 2);
        if (count($parts) === 2) {
            $this->headers = $this->parseHeaders($parts[0]);
            $body = $parts[1];
        } else {
            $body = $rawData;
        }

        $this->data = json_decode($body, true) ?: $body;
    }

    private function parseHeaders($headerString)
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);
        foreach ($lines as $line) {
            if (strpos($line, ': ') !== false) {
                list($key, $value) = explode(': ', $line, 2);
                $headers[strtolower($key)] = $value;
            }
        }
        return $headers;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getRawData()
    {
        return $this->data;
    }
}
```

### Webling\API\ClientException
```php
<?php

namespace Webling\API;

class ClientException extends \Exception
{
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
```

## Caching System

### Webling\Cache\Cache
```php
<?php

namespace Webling\Cache;

class Cache implements ICache
{
    private $client;
    private $adapter;
    private $cache = [];

    public function __construct($client, ICacheAdapter $adapter)
    {
        $this->client = $client;
        $this->adapter = $adapter;
    }

    public function getObject($type, $id)
    {
        $key = $this->createKey($type, $id);

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->adapter->get($key);

            if ($this->cache[$key] === null) {
                $response = $this->client->get("/$type/$id");
                if ($response->getStatusCode() < 400) {
                    $this->cache[$key] = $response->getData();
                    $this->adapter->set($key, $this->cache[$key]);
                }
            }
        }

        return $this->cache[$key];
    }

    public function getObjects($type, $ids)
    {
        $objects = [];
        foreach ($ids as $id) {
            $objects[$id] = $this->getObject($type, $id);
        }
        return $objects;
    }

    public function getRoot($type)
    {
        $key = $this->createKey($type, 'root');

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->adapter->get($key);

            if ($this->cache[$key] === null) {
                $response = $this->client->get("/$type");
                if ($response->getStatusCode() < 400) {
                    $this->cache[$key] = $response->getData();
                    $this->adapter->set($key, $this->cache[$key]);
                }
            }
        }

        return $this->cache[$key];
    }

    public function getObjectBinary($type, $id, $binaryPath)
    {
        $key = $this->createKey($type, $id, $binaryPath);

        $data = $this->adapter->get($key);
        if ($data === null) {
            $response = $this->client->get($binaryPath);
            if ($response->getStatusCode() < 400) {
                $data = $response->getRawData();
                $this->adapter->set($key, $data);
            }
        }

        return $data;
    }

    public function updateCache()
    {
        // Implementation for cache updates
        $this->cache = [];
    }

    public function clearCache()
    {
        $this->cache = [];
        $this->adapter->clear();
    }

    private function createKey($type, $id, $extra = '')
    {
        return $type . '_' . $id . ($extra ? '_' . md5($extra) : '');
    }
}
```

### Webling\CacheAdapters\FileCacheAdapter
```php
<?php

namespace Webling\CacheAdapters;

class FileCacheAdapter implements ICacheAdapter
{
    private $directory;
    private $ttl;

    public function __construct($options = [])
    {
        $this->directory = isset($options['directory']) ? $options['directory'] : './cache';
        $this->ttl = isset($options['ttl']) ? $options['ttl'] : 3600;

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }

    public function set($key, $value, $ttl = null)
    {
        $ttl = $ttl ?: $this->ttl;
        $file = $this->getFilePath($key);

        $data = serialize([
            'expires' => time() + $ttl,
            'data' => $value
        ]);

        return file_put_contents($file, $data) !== false;
    }

    public function get($key)
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));

        if (time() > $data['expires']) {
            unlink($file);
            return null;
        }

        return $data['data'];
    }

    public function clear()
    {
        $files = glob($this->directory . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function getFilePath($key)
    {
        return $this->directory . '/' . md5($key) . '.cache';
    }
}
```

## Interfaces

### Webling\API\IClient
```php
<?php

namespace Webling\API;

interface IClient
{
    public function get($path);
    public function post($path, $data = null);
    public function put($path, $data = null);
    public function delete($path);
}
```

### Webling\API\IResponse
```php
<?php

namespace Webling\API;

interface IResponse
{
    public function getStatusCode();
    public function getHeaders();
    public function getData();
    public function getRawData();
}
```

### Webling\Cache\ICache
```php
<?php

namespace Webling\Cache;

interface ICache
{
    public function getObject($type, $id);
    public function getObjects($type, $ids);
    public function getRoot($type);
    public function getObjectBinary($type, $id, $binaryPath);
    public function updateCache();
    public function clearCache();
}
```

### Webling\CacheAdapters\ICacheAdapter
```php
<?php

namespace Webling\CacheAdapters;

interface ICacheAdapter
{
    public function set($key, $value, $ttl = null);
    public function get($key);
    public function clear();
}
```

---

# Examples & Usage

## Basic Usage Examples

### examples/list_members.php
```php
<?php

require 'vendor/autoload.php';

$client = new Webling\API\Client('https://demo.webling.ch', 'YOUR_API_KEY');

try {
    $response = $client->get('/member');

    if ($response->getStatusCode() < 400) {
        $members = $response->getData();

        echo "Found " . count($members) . " members:\n\n";

        foreach ($members as $member) {
            echo "ID: " . $member['id'] . "\n";
            echo "Name: " . ($member['properties']['Vorname'] ?? 'N/A') . " " .
                           ($member['properties']['Name'] ?? 'N/A') . "\n";
            echo "---\n";
        }
    } else {
        echo "Error: " . $response->getStatusCode() . "\n";
    }
} catch (Webling\API\ClientException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

### examples/list_members_cached.php
```php
<?php

require 'vendor/autoload.php';

$client = new Webling\API\Client('https://demo.webling.ch', 'YOUR_API_KEY');

$adapter = new Webling\CacheAdapters\FileCacheAdapter([
    'directory' => './webling_cache'
]);

$cache = new Webling\Cache\Cache($client, $adapter);

try {
    // Get all members (cached)
    $members = $cache->getRoot('member');

    echo "Found " . count($members) . " members (cached):\n\n";

    foreach ($members as $member) {
        echo "ID: " . $member['id'] . "\n";
        echo "Name: " . ($member['properties']['Vorname'] ?? 'N/A') . " " .
                       ($member['properties']['Name'] ?? 'N/A') . "\n";

        // Get detailed member data (cached)
        $memberDetail = $cache->getObject('member', $member['id']);
        echo "Email: " . ($memberDetail['properties']['Email'] ?? 'N/A') . "\n";
        echo "---\n";
    }

    // Update cache
    echo "Updating cache...\n";
    $cache->updateCache();

} catch (Webling\API\ClientException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

### examples/list_members_with_images.php
```php
<?php

require 'vendor/autoload.php';

$client = new Webling\API\Client('https://demo.webling.ch', 'YOUR_API_KEY');

$adapter = new Webling\CacheAdapters\FileCacheAdapter([
    'directory' => './webling_cache'
]);

$cache = new Webling\Cache\Cache($client, $adapter);

try {
    $members = $cache->getRoot('member');

    echo "Members with images:\n\n";

    foreach ($members as $member) {
        $memberDetail = $cache->getObject('member', $member['id']);

        if (isset($memberDetail['properties']['Mitgliederbild'])) {
            $imageUrl = $memberDetail['properties']['Mitgliederbild']['href'];

            echo "ID: " . $member['id'] . "\n";
            echo "Name: " . ($memberDetail['properties']['Vorname'] ?? 'N/A') . " " .
                           ($memberDetail['properties']['Name'] ?? 'N/A') . "\n";
            echo "Image URL: " . $imageUrl . "\n";

            // Download and cache image
            $imageData = $cache->getObjectBinary('member', $member['id'], $imageUrl);
            if ($imageData) {
                $filename = './images/member_' . $member['id'] . '.jpg';
                file_put_contents($filename, $imageData);
                echo "Image saved to: " . $filename . "\n";
            }

            echo "---\n";
        }
    }

} catch (Webling\API\ClientException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

### examples/image_proxy.php
```php
<?php

require 'vendor/autoload.php';

$client = new Webling\API\Client('https://demo.webling.ch', 'YOUR_API_KEY');

$adapter = new Webling\CacheAdapters\FileCacheAdapter([
    'directory' => './webling_cache'
]);

$cache = new Webling\Cache\Cache($client, $adapter);

// Get member ID from URL parameter
$memberId = $_GET['id'] ?? 0;

if ($memberId > 0) {
    try {
        $member = $cache->getObject('member', $memberId);

        if (isset($member['properties']['Mitgliederbild'])) {
            $imageUrl = $member['properties']['Mitgliederbild']['href'];

            // Get cached image data
            $imageData = $cache->getObjectBinary('member', $memberId, $imageUrl);

            if ($imageData) {
                header('Content-Type: ' . $member['properties']['Mitgliederbild']['mimeType']);
                echo $imageData;
                exit;
            }
        }
    } catch (Webling\API\ClientException $e) {
        // Handle error
    }
}

// Return default image or error
header('Content-Type: image/png');
readfile('./default-avatar.png');
```

---

# Testing

## Test Files

### tests/Webling/API/ClientTest.php
```php
<?php

namespace Webling\API;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRequest()
    {
        $mockHttp = $this->getMockBuilder('Webling\API\CurlHttp')
                        ->disableOriginalConstructor()
                        ->getMock();

        $mockResponse = new Response(200, '{"test": "data"}');
        $mockHttp->method('request')->willReturn($mockResponse);

        $client = new Client('https://demo.webling.ch', 'test_key');
        // Inject mock
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockHttp);

        $response = $client->get('/test');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['test' => 'data'], $response->getData());
    }

    public function testPostRequest()
    {
        $mockHttp = $this->getMockBuilder('Webling\API\CurlHttp')
                        ->disableOriginalConstructor()
                        ->getMock();

        $mockResponse = new Response(201, '{"id": 123}');
        $mockHttp->method('request')->willReturn($mockResponse);

        $client = new Client('https://demo.webling.ch', 'test_key');
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockHttp);

        $response = $client->post('/member', ['name' => 'Test']);
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testApiErrorHandling()
    {
        $this->setExpectedException('Webling\API\ClientException');

        $mockHttp = $this->getMockBuilder('Webling\API\CurlHttp')
                        ->disableOriginalConstructor()
                        ->getMock();

        $mockHttp->method('request')->willThrowException(
            new ClientException('Connection failed')
        );

        $client = new Client('https://demo.webling.ch', 'test_key');
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('http');
        $property->setAccessible(true);
        $property->setValue($client, $mockHttp);

        $client->get('/test');
    }
}
```

### tests/Webling/API/ResponseTest.php
```php
<?php

namespace Webling\API;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testJsonResponseParsing()
    {
        $response = new Response(200, '{"key": "value", "number": 42}');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['key' => 'value', 'number' => 42], $response->getData());
    }

    public function testRawResponseHandling()
    {
        $rawData = 'Plain text response';
        $response = new Response(200, $rawData);

        $this->assertEquals($rawData, $response->getRawData());
    }

    public function testHeaderParsing()
    {
        $httpResponse = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"test\": true}";
        $response = new Response(200, $httpResponse);

        $this->assertArrayHasKey('content-type', $response->getHeaders());
        $this->assertEquals('application/json', $response->getHeaders()['content-type']);
    }

    public function testErrorStatusCode()
    {
        $response = new Response(404, 'Not Found');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getRawData());
    }
}
```

### tests/Webling/Cache/CacheTest.php
```php
<?php

namespace Webling\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    private $cache;
    private $mockClient;
    private $mockAdapter;

    protected function setUp()
    {
        $this->mockClient = $this->getMockBuilder('Webling\API\Client')
                                ->disableOriginalConstructor()
                                ->getMock();

        $this->mockAdapter = $this->getMockBuilder('Webling\CacheAdapters\ICacheAdapter')
                                 ->getMock();

        $this->cache = new Cache($this->mockClient, $this->mockAdapter);
    }

    public function testGetObjectFromCache()
    {
        $expectedData = ['id' => 123, 'name' => 'Test'];

        $this->mockAdapter->method('get')
                          ->willReturn($expectedData);

        $result = $this->cache->getObject('member', 123);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetObjectFromApi()
    {
        $expectedData = ['id' => 123, 'name' => 'Test'];
        $mockResponse = $this->getMockBuilder('Webling\API\Response')
                            ->disableOriginalConstructor()
                            ->getMock();

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getData')->willReturn($expectedData);

        $this->mockClient->method('get')->willReturn($mockResponse);
        $this->mockAdapter->method('get')->willReturn(null);

        $result = $this->cache->getObject('member', 123);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetObjectsMultiple()
    {
        $ids = [1, 2, 3];
        $expected = [
            1 => ['id' => 1, 'name' => 'One'],
            2 => ['id' => 2, 'name' => 'Two'],
            3 => ['id' => 3, 'name' => 'Three']
        ];

        $this->mockAdapter->method('get')
                          ->willReturnOnConsecutiveCalls(
                              $expected[1], $expected[2], $expected[3]
                          );

        $result = $this->cache->getObjects('member', $ids);

        $this->assertEquals($expected, $result);
    }

    public function testClearCache()
    {
        $this->mockAdapter->expects($this->once())
                          ->method('clear');

        $this->cache->clearCache();
    }
}
```

### tests/Webling/Cache/FileCacheTest.php
```php
<?php

namespace Webling\Cache;

class FileCacheTest extends \PHPUnit_Framework_TestCase
{
    private $cacheDir;

    protected function setUp()
    {
        $this->cacheDir = sys_get_temp_dir() . '/webling_test_cache_' . uniqid();
        mkdir($this->cacheDir);
    }

    protected function tearDown()
    {
        $this->removeDirectory($this->cacheDir);
    }

    public function testFileCacheAdapter()
    {
        $adapter = new \Webling\CacheAdapters\FileCacheAdapter([
            'directory' => $this->cacheDir
        ]);

        $key = 'test_key';
        $data = ['test' => 'data', 'number' => 42];

        // Test set and get
        $this->assertTrue($adapter->set($key, $data));
        $this->assertEquals($data, $adapter->get($key));

        // Test cache miss
        $this->assertNull($adapter->get('nonexistent_key'));

        // Test clear
        $adapter->clear();
        $this->assertNull($adapter->get($key));
    }

    public function testCacheExpiration()
    {
        $adapter = new \Webling\CacheAdapters\FileCacheAdapter([
            'directory' => $this->cacheDir,
            'ttl' => 1 // 1 second
        ]);

        $key = 'expiring_key';
        $data = 'temporary data';

        $adapter->set($key, $data, 1);
        $this->assertEquals($data, $adapter->get($key));

        sleep(2); // Wait for expiration

        $this->assertNull($adapter->get($key));
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
```

---

# Configuration

## Composer Configuration
```json
{
    "name": "usystems/webling-api-php",
    "description": "Lightweight Webling API Wrapper",
    "license": "MIT",
    "authors": [
        {
            "name": "Demian Holderegger",
            "email": "support@webling.ch"
        }
    ],
    "type": "library",
    "minimum-stability": "dev",
    "prefer-stable": true,
    "require": {
        "php": ">=5.6.0",
        "ext-curl": "*",
        "ext-json": "*"
    },
    "require-dev": {
        "phpunit/phpunit": ">=4.8 <10.0"
    },
    "autoload": {
        "psr-0": {
            "Webling": "src/"
        }
    }
}
```

## PHPUnit Configuration
```xml
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

---

## API Documentation Reference

For the complete Webling REST API documentation, visit:  
**https://demo.webling.ch/api**

This documentation contains all source code, examples, and API reference needed to work with the Webling API PHP client. No external references are required.
