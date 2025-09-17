<?php

declare(strict_types=1);

namespace Terminal42\WeblingApi\Test;

use PHPUnit\Framework\TestCase;
use Terminal42\WeblingApi\Changes;
use Terminal42\WeblingApi\ClientInterface;
use Terminal42\WeblingApi\Entity\EntityInterface;
use Terminal42\WeblingApi\Entity\Member;
use Terminal42\WeblingApi\EntityFactory;
use Terminal42\WeblingApi\EntityFactoryInterface;
use Terminal42\WeblingApi\EntityList;
use Terminal42\WeblingApi\EntityManager;

class EntityManagerTest extends TestCase
{
    public function testFindAllMembers(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $manager = new EntityManager($client, new EntityFactory());

        $client
            ->expects($this->once())
            ->method('get')
            ->with('/member')
            ->willReturn(['objects' => [1, 2, 3]])
        ;

        $members = $manager->findAll('member');

        $this->assertInstanceOf(EntityList::class, $members);
        $this->assertSame([1, 2, 3], $members->getIds());
    }

    public function testFindMember(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $factory = $this->createMock(EntityFactoryInterface::class);
        $manager = new EntityManager($client, $factory);

        $client
            ->expects($this->once())
            ->method('get')
            ->with('/member/111')
            ->willReturn(['type' => 'member'])
        ;

        $entity = $this->createMock(EntityInterface::class);
        $factory
            ->expects($this->once())
            ->method('create')
            ->with($manager, ['type' => 'member'])
            ->willReturn($entity)
        ;

        $manager->find('member', 111);

        // Tests the manager will not call the client or factory again
        $manager->find('member', 111);
    }

    public function testPersistWithId(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $entity = new Member(111);
        $manager = new EntityManager($client, new EntityFactory());

        $client
            ->expects($this->once())
            ->method('put')
            ->with('/member/111')
        ;

        $manager->persist($entity);
    }

    public function testPersistWithoutId(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $entity = new Member();
        $manager = new EntityManager($client, new EntityFactory());

        $client
            ->expects($this->once())
            ->method('post')
            ->willReturn(111)
        ;

        $manager->persist($entity);

        $this->assertSame(111, $entity->getId());
    }

    public function testPersistReadonly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = $this->createMock(ClientInterface::class);
        $entity = new Member(111, true);
        $manager = new EntityManager($client, new EntityFactory());

        $manager->persist($entity);
    }

    public function testRemove(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $entity = new Member(111);
        $manager = new EntityManager($client, new EntityFactory());

        $client
            ->expects($this->once())
            ->method('delete')
            ->with('/member/111')
        ;

        $manager->remove($entity);

        $this->assertNull($entity->getId());
    }

    public function testRemoveWithoutId(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $client = $this->createMock(ClientInterface::class);
        $entity = new Member();
        $manager = new EntityManager($client, new EntityFactory());

        $manager->remove($entity);
    }

    public function testRemoveReadonly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $client = $this->createMock(ClientInterface::class);
        $entity = new Member(111, true);
        $manager = new EntityManager($client, new EntityFactory());

        $manager->remove($entity);
    }

    public function testGetLatestRevisionId(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $manager = new EntityManager($client, new EntityFactory());

        $client
            ->expects($this->once())
            ->method('get')
            ->with('/replicate')
            ->willReturn(
                [
                    'revision' => 1234,
                    'version' => 720,
                ]
            )
        ;

        $this->assertSame(1234, $manager->getLatestRevisionId());
    }

    public function testGetChanges(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $manager = new EntityManager($client, new EntityFactory());

        $client
            ->expects($this->once())
            ->method('get')
            ->with('/replicate/1234')
            ->willReturn(
                [
                    'objects' => [
                        'member' => [
                            469,
                            492,
                        ],
                        'membergroup' => [
                            554,
                            552,
                        ],
                        'debitor' => [
                            848,
                        ],
                    ],
                    'context' => [],
                    'definitions' => [],
                    'settings' => false,
                    'quota' => true,
                    'subscription' => false,
                    'revision' => 1530,
                    'version' => 720,
                ]
            )
        ;

        $changes = $manager->getChanges(1234);

        $this->assertInstanceOf(Changes::class, $changes);
        $this->assertSame(1234, $changes->getRevisionFrom());
        $this->assertSame(1530, $changes->getRevisionTo());

        $this->assertInstanceOf(EntityList::class, $changes->getEntities('member'));
        $this->assertSame([469, 492], $changes->getEntities('member')->getIds());
    }
}
