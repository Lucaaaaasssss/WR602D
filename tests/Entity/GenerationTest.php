<?php

namespace App\Tests\Entity;

use App\Entity\Generation;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class GenerationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $generation = new Generation();

        $this->assertNull($generation->getId());
        $this->assertNull($generation->getFile());
        $this->assertNull($generation->getType());
        $this->assertNull($generation->getUser());
        $this->assertInstanceOf(\DateTimeInterface::class, $generation->getCreatedAt());
        $this->assertCount(0, $generation->getUserContacts());
    }

    public function testSettersAndGetters(): void
    {
        $generation = new Generation();
        $user       = new User();

        $generation->setFile('test.pdf');
        $generation->setType('html');
        $generation->setUser($user);

        $this->assertEquals('test.pdf', $generation->getFile());
        $this->assertEquals('html', $generation->getType());
        $this->assertSame($user, $generation->getUser());
    }

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $before     = new \DateTime('-1 second');
        $generation = new Generation();
        $after      = new \DateTime('+1 second');

        $this->assertGreaterThanOrEqual($before, $generation->getCreatedAt());
        $this->assertLessThanOrEqual($after, $generation->getCreatedAt());
    }

    public function testAllTypes(): void
    {
        $types = ['html', 'url', 'markdown', 'office', 'merge', 'screenshot', 'wysiwyg'];

        foreach ($types as $type) {
            $g = new Generation();
            $g->setType($type);
            $this->assertEquals($type, $g->getType());
        }
    }
}
