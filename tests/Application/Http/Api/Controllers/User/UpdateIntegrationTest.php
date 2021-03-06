<?php

namespace Cocktales\Application\Http\Api\v1\Controllers\User;

use Cocktales\Domain\User\Entity\User;
use Cocktales\Domain\User\UserOrchestrator;
use Cocktales\Framework\Password\PasswordHash;
use Cocktales\Helpers\CreatesContainer;
use Cocktales\Helpers\RunsMigrations;
use Cocktales\Helpers\UsesHttpServer;
use GuzzleHttp\Psr7\ServerRequest;
use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class UpdateIntegrationTest extends TestCase
{
    use UsesHttpServer;
    use CreatesContainer;
    use RunsMigrations;

    /** @var  ContainerInterface */
    private $container;
    /** @var  UserOrchestrator */
    private $orchestrator;

    public function setUp()
    {
        $this->container = $this->runMigrations($this->createContainer());
        $this->orchestrator = $this->container->get(UserOrchestrator::class);
    }

    public function test_success_response_is_received()
    {
        $this->createUser();

        $request = new ServerRequest(
            'post',
            '/api/v1/user/update',
            [],
            '{"id":"93449e9d-4082-4305-8840-fa1673bcf915","email":"joe@newEmail.com","oldPassword":"password", "newPassword":"newPass"}'
        );

        $response = $this->handle($this->container, $request);

        $jsend = json_decode($response->getBody()->getContents());

        $this->assertEquals('success', $jsend->status);
        $this->assertEquals('joe@newEmail.com', $jsend->data->user->email);
    }

    public function test_fail_response_is_received_if_user_id_is_not_found()
    {
        $request = new ServerRequest(
            'post',
            '/api/v1/user/update',
            [],
            '{"id":"93449e9d-4082-4305-8840-fa1673bcf915","email":"joe@newEmail.com","oldPassword":"password", "newPassword":"newPass"}'
        );

        $response = $this->handle($this->container, $request);

        $jsend = json_decode($response->getBody()->getContents());

        $this->assertEquals('fail', $jsend->status);
        $this->assertEquals('Unable to process request - please try again', $jsend->data->error);
    }

    public function test_fail_response_is_received_if_user_email_is_already_taken_by_another_user()
    {
        $this->createUser();
        $this->createAdditionalUser();

        $request = new ServerRequest(
            'post',
            '/api/v1/user/update',
            [],
            '{"id":"93449e9d-4082-4305-8840-fa1673bcf915","email":"andrea@mail.com","oldPassword":"", "newPassword":""}'
        );

        $response = $this->handle($this->container, $request);

        $jsend = json_decode($response->getBody()->getContents());

        $this->assertEquals('fail', $jsend->status);
        $this->assertEquals('A user has already registered with this email address', $jsend->data->error);
    }

    public function test_fail_response_is_received_if_old_password_does_not_match_password_stored_for_user()
    {
        $this->createUser();

        $request = new ServerRequest(
            'post',
            '/api/v1/user/update',
            [],
            '{"id":"93449e9d-4082-4305-8840-fa1673bcf915","email":"joe@email.com","oldPassword":"wrongPassword", "newPassword":"newPass"}'
        );

        $response = $this->handle($this->container, $request);

        $jsend = json_decode($response->getBody()->getContents());

        $this->assertEquals('fail', $jsend->status);
        $this->assertEquals('Password does not match the password on record - please try again', $jsend->data->error);
    }

    private function createUser()
    {
        $this->orchestrator->createUser(
            (new User('93449e9d-4082-4305-8840-fa1673bcf915'))
                ->setEmail('joe@mail.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );
    }

    private function createAdditionalUser()
    {
        $this->orchestrator->createUser(
            (new User('24306b5d-9107-4c26-bd55-d0ff6ac9382a'))
                ->setEmail('andrea@mail.com')
                ->setPasswordHash(PasswordHash::createFromRaw('password'))
        );
    }
}
