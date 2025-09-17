<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Repository;

use Terminal42\WeblingApi\Entity\Membergroup;
use Terminal42\WeblingApi\EntityList;
use Terminal42\WeblingApi\Query\Query;

/**
 * @method EntityList|Membergroup[] findAll(array $order = [])
 * @method Membergroup              findById($id)
 * @method EntityList|Membergroup[] findBy(Query $query, array $order = [])
 */
class MembergroupRepository extends AbstractRepository
{
    public function getType(): string
    {
        return 'membergroup';
    }
}
