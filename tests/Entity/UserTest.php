<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testDefaultRoles(): void
    {
        $user = new User();
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $user->setPassword('hashedpassword123');
        $this->assertEquals('hashedpassword123', $user->getPassword());
    }

    public function testResetToken(): void
    {
        $user   = new User();
        $token  = bin2hex(random_bytes(16));
        $expiry = new \DateTimeImmutable('+1 hour');

        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiry);

        $this->assertEquals($token, $user->getResetToken());
        $this->assertEquals($expiry, $user->getResetTokenExpiresAt());

        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->assertNull($user->getResetToken());
        $this->assertNull($user->getResetTokenExpiresAt());
    }

    public function testRolesAlwaysContainRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_PREMIUM']);
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertContains('ROLE_PREMIUM', $user->getRoles());
    }
}
