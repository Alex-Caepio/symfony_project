<?php

namespace App\Tests\Service\Playlist;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Playlist;
use App\Service\Playlist\PlaylistService;
use App\Repository\PlaylistRepository;

use function PHPUnit\Framework\assertSame;

class PlaylistServiceTest extends WebTestCase
{

    private $playlistService;

    private $entityManagerMock;

    private $playlistRepositoryMock;

    private $playlist;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @TODO fix this test and functionality if needed
     */
//     public function testIndexServiceReturnArrayWithAllPlaylists(): void
//     {   
//         $this->playlist = new Playlist();
        
//         $this->playlist->setName('test');
//         $this->playlist->setDescription('test');
//         $this->playlist->setCreatedAt(new \DateTimeImmutable());
//         $this->playlist->setUpdatedAt(new \DateTimeImmutable());

//         $this->playlistRepositoryMock = $this
//                                 ->getMockBuilder(PlaylistRepository::class)
//                                 ->disableOriginalConstructor()
//                                 ->getMock();
//         $this->playlistRepositoryMock->expects($this->any())
//                                 ->method('findAll')
//                                 ->willReturn([$this->playlist]);
// +
//         $this->entityManagerMock = $this
//                             ->getMockBuilder(EntityManagerInterface::class)
//                             ->disableOriginalConstructor()
//                             ->getMock();
//         $this->entityManagerMock ->expects($this->any())
//                            ->method('getRepository')
//                            ->willReturn($this->playlistRepositoryMock);

//         $this->playlistService = new PlaylistService($this->entityManagerMock);

//         $excepted = $this->entityManagerMock->getRepository(Playlist::class)->findAll();
//         $testMethod = $this->playlistService ->indexService();
        
//         assertSame($excepted , $testMethod);
//     }
}