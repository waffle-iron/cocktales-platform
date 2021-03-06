<?php

namespace Cocktales\Domain\User\Persistence;

use Cocktales\Domain\User\Entity\User;
use Cocktales\Framework\Exception\NotFoundException;
use Cocktales\Framework\Exception\UserRepositoryException;
use Cocktales\Framework\Password\PasswordHash;
use Cocktales\Framework\Uuid\Uuid;
use Cocktales\Helpers\CreatesContainer;
use Cocktales\Helpers\RunsMigrations;
use Illuminate\Database\Connection;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class IlluminateDbUserRepositoryTest extends TestCase
{
    use RunsMigrations;
    use CreatesContainer;

    /** @var  ContainerInterface */
    private $container;
    /** @var  Repository */
    private $repository;
    /** @var  Connection */
    private $connection;

    public function setUp()
    {
        $this->container = $this->runMigrations($this->createContainer());
        $this->repository = $this->container->get(Repository::class);
        $this->connection = $this->container->get(Connection::class);
    }

    public function test_interface_implementation_is_bound()
    {
        $this->assertInstanceOf(Repository::class, $this->createContainer()->get(Repository::class));
    }

    public function test_create_user_adds_a_new_record_to_the_database()
    {
        $this->repository->createUser(
            (new User('dc5b6421-d452-4862-b741-d43383c3fe1d'))
                ->setEmail('joe@example.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );

        $this->assertCount(1, $this->connection->table('user')->get());

        $this->repository->createUser(
            (new User('dc5b6421-d452-4862-b741-d43383c3fe1d'))
                ->setEmail('andrea@example.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );

        $this->assertCount(2, $this->connection->table('user')->get());
    }

    public function test_user_can_be_retrieved_by_email()
    {
        $this->repository->createUser(
            (new User('dc5b6421-d452-4862-b741-d43383c3fe1d'))
                ->setEmail('joe@example.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );

        $fetched = $this->repository->getUserByEmail('joe@example.com');

        $this->assertInstanceOf(User::class, $fetched);
        $this->assertEquals('dc5b6421-d452-4862-b741-d43383c3fe1d', $fetched->getId()->__toString());
        $this->assertEquals('joe@example.com', $fetched->getEmail());
    }

    public function test_exception_is_thrown_if_email_is_not_present_in_database()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("User with email 'fake@email.com' does not exist");
        $this->repository->getUserByEmail('fake@email.com');
    }

    public function test_a_user_can_be_deleted_from_the_database()
    {
        $user = $this->repository->createUser(
            (new User('dc5b6421-d452-4862-b741-d43383c3fe1d'))
                ->setEmail('joe@example.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );

        $this->assertCount(1, $this->connection->table('user')->get());

        $this->repository->deleteUser($user);

        $this->assertCount(0, $this->connection->table('user')->get());
    }

    public function test_a_user_can_be_retrieved_by_their_uuid()
    {
        $this->repository->createUser(
            (new User('dc5b6421-d452-4862-b741-d43383c3fe1d'))
                ->setEmail('joe@example.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );

        $fetched = $this->repository->getUserById(new Uuid('dc5b6421-d452-4862-b741-d43383c3fe1d'));

        $this->assertInstanceOf(User::class, $fetched);
        $this->assertEquals('dc5b6421-d452-4862-b741-d43383c3fe1d', $fetched->getId()->__toString());
        $this->assertEquals('joe@example.com', $fetched->getEmail());
    }

    public function test_exception_is_thrown_if_id_is_not_present_in_the_database()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("User with ID 'dc5b6421-d452-4862-b741-d43383c3fe1d' does not exist");
        $this->repository->getUserById(new Uuid('dc5b6421-d452-4862-b741-d43383c3fe1d'));
    }

    public function test_update_user_updates_a_user_record_in_the_database()
    {
        $this->repository->createUser(
            (new User('dc5b6421-d452-4862-b741-d43383c3fe1d'))
                ->setEmail('joe@example.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );

        $fetched = $this->repository->getUserById(new Uuid('dc5b6421-d452-4862-b741-d43383c3fe1d'));

        $this->assertInstanceOf(User::class, $fetched);
        $this->assertEquals('dc5b6421-d452-4862-b741-d43383c3fe1d', $fetched->getId()->__toString());

        $this->repository->updateUser($fetched->setEmail('joe@email.com'));

        $fetched = $this->repository->getUserById(new Uuid('dc5b6421-d452-4862-b741-d43383c3fe1d'));

        $this->assertEquals('joe@email.com', $fetched->getEmail());
    }
}
