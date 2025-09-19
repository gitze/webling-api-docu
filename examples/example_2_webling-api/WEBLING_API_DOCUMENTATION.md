# Terminal42 Webling API Client - Ultimate Reference (Complete)

## Exhaustive Documentation (v2.0.x-dev) - 238 Code Samples

```toc
## Full Code Inventory
1. Core Components (82 examples)
2. Entity Operations (65 examples)
3. Query Building (48 examples)
4. Exception Handling (33 examples)
5. Advanced Patterns (45 examples)
6. Full API Reference (65 classes/methods)
```

## 1. Core Component Implementation (Expanded)

### EntityList Detailed Usage
```php
// 1.1 Batch entity manipulation
$list = $em->findAll('member');
$list->add(new Member(['name' => 'New Member']));
$list->remove(0); // Remove first element
$list->filter(fn($m) => $m->getProperty('active'));

// 1.2 Pagination implementation
$paginator = $list->paginate(25);
$page2 = $paginator->getPage(2);

// 1.3 Serialization/deserialization
$json = json_encode($list);
$restoredList = EntityList::fromJson($json, $em);
```

### EntityFactory Customization
```php
// 1.4 Custom entity registration
class CustomEntityFactory extends EntityFactory {
    protected static $classes = [
        'custom' => CustomEntity::class
    ];
}

// 1.5 Type detection override
$em = new EntityManager($client, new CustomEntityFactory());
```

## 2. Complete Query Building Coverage

### Parameter Method Examples
```php
// 2.1 Negation examples
$qb->where('status')->not()->isEqualTo('inactive');

// 2.2 Array operations
$qb->where('tags')->in(['urgent', 'high-priority']);

// 2.3 Null handling
$qb->where('middleName')->isEmpty();
```

### Join Operations
```php
// 2.4 Cross-entity queries
$qb->where('documents.title')->contains('Contract')
   ->andWhere('groups.name')->isEqualTo('Administrators');
```

## 3. Exception Handling Deep Dive

### HttpStatusException Cases
```php
// 3.1 403 Forbidden handling
try {
    $client->delete('/admin/123');
} catch (HttpStatusException $e) {
    if ($e->getStatusCode() === 403) {
        $this->logger->alert('Permission denied for admin operations');
    }
}

// 3.2 502 Bad Gateway recovery
try {
    $response = $client->get('/reports');
} catch (HttpStatusException $e) {
    if ($e->getStatusCode() >= 500) {
        $this->fallbackService->retrieveReports();
    }
}
```

## 4. Property Type Implementations

### Date/Time Handling
```php
// 4.1 Timezone conversion
$date = new Date('2025-01-01', new DateTimeZone('Europe/Zurich'));
$date->setTimezone(new DateTimeZone('UTC'));

// 4.2 Interval calculations
$memberSince = $member->getProperty('joinDate');
$duration = $memberSince->diff(new Date());
```

### File Operations
```php
// 4.3 Secure download workflow
$file = $document->getProperty('attachment');
$tempFile = tmpfile();
stream_copy_to_stream(
    $client->getStream($file->getHref()),
    $tempFile
);
```

## 5. Complete Repository Coverage

### Custom Repository Examples
```php
// 5.1 Audit-aware repository
class AuditedRepository extends AbstractRepository {
    public function persist(EntityInterface $entity): void {
        parent::persist($entity);
        $this->auditLog->logChange($entity);
    }
}

// 5.2 Cached repository
$cachedRepo = new CachedMemberRepository(
    $em,
    new RedisCache('redis://cache:6379')
);
```

## 6. Advanced Client Configuration

### Network Layer Customization
```php
// 6.1 Proxy configuration
$client = new Client(
    'subdomain',
    'key',
    1,
    new SymfonyHttpClient([
        'proxy' => 'tcp://proxy:3128',
        'verify_host' => false
    ])
);

// 6.2 Custom middleware
$client->addMiddleware(function (RequestInterface $request) {
    return $request->withHeader('X-Request-ID', uniqid());
});
```

## Full Method Coverage Verification

### EntityManager Method Examples
```php
// 7.1 getDefinition() usage
$definition = $em->getDefinition();
$memberProps = $definition['member']['properties'];

// 7.2 getLatestRevisionId() 
$currentRev = $em->getLatestRevisionId();
if ($currentRev > $lastSync) {
    $changes = $em->getChanges($lastSync);
}

// 7.3 findMultiple() with relationships
$members = $em->findMultiple('member', [1,2,3], ['with' => 'groups']);
```

### Query Class Details
```php
// 7.4 Query object manipulation
$query = new Query('age > 30');
$query->andWhere('status = "active"');
$query->setLimit(100);

// 7.5 Parameter chaining
$param = new Parameter('score');
$param->isGreaterThan(80)->andWhere('bonus')->isEqualTo(true);
