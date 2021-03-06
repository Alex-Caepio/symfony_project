<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->client = static::createClient();
    }

    /**
     * @dataProvider loginDataProvider
     */
    public function testEmailLogin(array $dataLogin, int $expectedCode): void
    {
        $data = [
            'email' => 'alexbr@aa.com',
        ];
        $this->client->jsonRequest('POST', 'api/auth/sendemail', $data);
        $response = $this->client->getResponse();

        $this->assertSame(201, $response->getStatusCode());

        $userRepository = static::getContainer()->get(UserRepository::class);

        $testUser = $userRepository->findOneByEmail('alexbr@aa.com');

        $this->client->loginUser($testUser);

        $this->assertResponseIsSuccessful();
    }

    public function loginDataProvider()
    {
        return [
            [
                [
                    'email' => 'alexbr@aa.com',
                    'password' => '12345',
                ],
                200,
            ],
        ];
    }
}
