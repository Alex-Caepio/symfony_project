<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ProfileTest extends WebTestCase
{
    private $client;
    private $user;
    private $faker;

    public function setUp(): void
    {
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('b.astapau@andersenlab.com');
        $this->user = $testUser;

        $this->faker = Factory::create();
    }

    public function testUploadProfilePhoto(): void
    {
        $this->client->loginUser($this->user);

        $profilePhoto = new UploadedFile($this->faker->image('/tmp', 100, 100), "image_name" . '.png', 'image/png', null, true);

        $this->client->request('POST', '/api/profile/about/photo', [], ['photo' => $profilePhoto]);

        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($response->success);
        $this->assertSame($response->body->message, 'Profile photo was successfully uploaded.');
    }

    public function testShowUserInfoSuccess(): void
    {
        $this->client->loginUser($this->user);

        $this->client->request('GET', '/api/profile/about/info');

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertTrue($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testShowUserInfoFailure(): void
    {
        $this->client->request('GET', '/api/profile/about/info');

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('Access denied', $responseData->body->message);
    }

    public function testUpdateUserInfoSuccess(): void
    {
        $this->client->loginUser($this->user);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "firstName" => "John",
            "lastName" => "Doe",
            "userName" => "iamJohnDoe",
            "country" => "Belarus",
            "city" => "Minsk",
            "phone" => "+375294444444"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertTrue($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $response->getStatusCode());

        $this->assertEquals("John", $responseData->user->firstName);
        $this->assertEquals("Doe", $responseData->user->lastName);
        $this->assertEquals("iamJohnDoe", $responseData->user->userName);
        $this->assertEquals("Belarus", $responseData->user->country);
        $this->assertEquals("Minsk", $responseData->user->city);
        $this->assertEquals("+375294444444", $responseData->user->phone);

        $actualUser = static::getContainer()->get(UserRepository::class)->findOneById($this->user->getId());
        $this->assertEquals("John", $actualUser->getFirstName());
        $this->assertEquals("Doe", $actualUser->getLastName());
        $this->assertEquals("iamJohnDoe", $actualUser->getUserName());
        $this->assertEquals("Belarus", $actualUser->getCity()->getCountry()->getName());
        $this->assertEquals("Minsk", $actualUser->getCity()->getName());
        $this->assertEquals("+375294444444", $actualUser->getPhone());
    }

    public function testUpdateUserInfoFailureAccess(): void
    {
        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "firstName" => "John",
            "lastName" => "Doe",
            "userName" => "iamJohnDoe",
            "country" => "Belarus",
            "city" => "Minsk",
            "phone" => "+375294444444"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('Access denied', $responseData->body->message);
    }

    public function testUpdateUserInfoFailureInvalidUser(): void
    {
        $this->client->loginUser($this->user);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $invalidUser = $userRepository->findOneByEmail('password@notandersenlab.com');

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $invalidUser->getId(), [
            "firstName" => "John",
            "lastName" => "Doe",
            "userName" => "iamJohnDoe",
            "country" => "Belarus",
            "city" => "Minsk",
            "phone" => "+375294444444"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('You are not allowed to change this user`s data', $responseData->body->message);
    }

    public function testUpdateUserInfoSuccessOwnPhone(): void
    {
        $this->client->loginUser($this->user);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "firstName" => "John",
            "lastName" => "Doe",
            "userName" => "iamJohnDoe",
            "country" => "Belarus",
            "city" => "Minsk",
            "phone" => "+375291235566"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertTrue($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testUpdateUserInfoFailureValidateName(): void
    {
        $this->client->loginUser($this->user);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "firstName" => "J"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('firstName', $responseData->body->name);
        $this->assertSame('Must be 2 characters or more', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "firstName" => "Joooooooooooooooooooooooooooooooooooooooooooooooooooooooooohn"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('firstName', $responseData->body->name);
        $this->assertSame('Must be 60 characters or less', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "firstName" => ".John"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('firstName', $responseData->body->name);
        $this->assertSame('Can contain letters, numbers, !@#$%&‘*+—/\=?^_`{|}~!»№;%:?*()[]<>,\' symbols, and one dot not first or last', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "lastName" => "D"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('lastName', $responseData->body->name);
        $this->assertSame('Must be 2 characters or more', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "lastName" => "Doooooooooooooooooooooooooooooooooooooooooooooooooooooooooooe"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('lastName', $responseData->body->name);
        $this->assertSame('Must be 60 characters or less', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "lastName" => ".Doe"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('lastName', $responseData->body->name);
        $this->assertSame('Can contain letters, numbers, !@#$%&‘*+—/\=?^_`{|}~!»№;%:?*()[]<>,\' symbols, and one dot not first or last', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "userName" => "i"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('userName', $responseData->body->name);
        $this->assertSame('Must be 2 characters or more', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "userName" => "iamJoooooooooooooooooooooooooooooooooooooooooooooooooooohnDoe"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('userName', $responseData->body->name);
        $this->assertSame('Must be 60 characters or less', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "userName" => ".iamJohnDoe"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('userName', $responseData->body->name);
        $this->assertSame('Can contain letters, numbers, !@#$%&‘*+—/\=?^_`{|}~!»№;%:?*()[]<>,\' symbols, and one dot not first or last', $responseData->body->message);
    }

    public function testUpdateUserInfoFailureValidatePhone(): void
    {
        $this->client->loginUser($this->user);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "phone" => "+375"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Phone must be 7 characters or more', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "phone" => "+375294444444444"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Phone must be 15 characters or less', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "phone" => "375294444444"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Phone can containe first plus symbol and numbers', $responseData->body->message);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "phone" => "+37529444444x"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('Phone can containe first plus symbol and numbers', $responseData->body->message);
    }

    public function testUpdateUserInfoSuccessCity(): void
    {
        $this->client->loginUser($this->user);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "city" => "Minsk"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertTrue($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $response->getStatusCode());

        $actualUser = static::getContainer()->get(UserRepository::class)->findOneById($this->user->getId());
        $this->assertEquals("Belarus", $actualUser->getCity()->getCountry()->getName());
        $this->assertEquals("Minsk", $actualUser->getCity()->getName());
    }

    public function testUpdateUserInfoFailureCity(): void
    {
        $this->client->loginUser($this->user);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "city" => "SomeCity"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('You have to choose right city', $responseData->body->message);
        $cityNames = [
            "Gomel",
            "Minsk",
            "Polotsk",
            "Vitebsk",
            "Krakow",
            "Kazan",
            "Remote RF",
            "Rostov",
            "Saint-Petersburg",
            "Cherkasy",
            "Chernihiv",
            "Dnipro",
            "Kharkiv",
            "Kyiv",
            "Odessa"
        ];
        $this->assertSame($cityNames, $responseData->body->cities);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "country" => "Belarus"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('You have to choose right city', $responseData->body->message);
        $cityNames = [
            "Gomel",
            "Minsk",
            "Polotsk",
            "Vitebsk"
        ];
        $this->assertSame($cityNames, $responseData->body->cities);
    }

    public function testUpdateUserInfoFailureCountry(): void
    {
        $this->client->loginUser($this->user);

        $this->client->jsonRequest('PUT', '/api/profile/about/update/' . $this->user->getId(), [
            "country" => "SomeCountry"
        ]);

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent());

        $this->assertFalse($responseData->success);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('You have to choose right country', $responseData->body->message);
        $countryNames = [
            "Belarus",
            "Poland",
            "Russia",
            "Ukraine"
        ];
        $this->assertSame($countryNames, $responseData->body->countries);
    }

}
