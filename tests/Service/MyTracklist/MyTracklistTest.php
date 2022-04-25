<?php

namespace App\Tests\Service\MyTracklist;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Track;
use App\Repository\TrackRepository;
use App\Service\MyTracklist\MyTracklistService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FileUploader;
use App\DTO\TracklistDTO;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\Factories;
use App\Factory\TrackFactory;
use Symfony\Component\Filesystem\Filesystem;
use App\Exception\MyTracklistException;

class MyTracklistTest extends WebTestCase
{
    use Factories;

    private $fileSystemMock;
    private $myTracklistService;
    private $entityManagerMock;
    private $trackRepositoryMock;
    private $track;
    private $fileUploaderMock;
    private $tracklistDto;
    private $uploadedFileMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->tracklistDto = $this->createMock(TracklistDTO::class);
        $this->fileSystemMock = $this->createMock(Filesystem::class);
        $this->fileUploaderMock = $this->createMock(FileUploader::class);
        $this->uploadedFileMock = $this->createMock(UploadedFile::class);

        $this->track = TrackFactory::createMany(5);

        $this->trackRepositoryMock = $this
                                     ->getMockBuilder(TrackRepository::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();

        $this->entityManagerMock = $this
                                   ->getMockBuilder(EntityManagerInterface::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $this->entityManagerMock->expects($this->any())
                                   ->method('getRepository')
                                   ->willReturn($this->trackRepositoryMock);

        $this->myTracklistService = new MyTracklistService(
            $this->entityManagerMock,
            $this->fileUploaderMock,
            $this->fileSystemMock
        );
    }

    public function testIndexService(): void
    {
        $this->trackRepositoryMock->expects($this->any())
                                  ->method('findAll')
                                  ->willReturn([$this->track]);

        $expected = $this->entityManagerMock->getRepository(Track::class)->findAll();
        $testMethod = $this->myTracklistService->indexService();
        $this->assertSame($expected, $testMethod);
    }

    public function testCreateService(): void
    {
        $expected = [[
            'trackType' => ['Book', 'Podcast', 'Music'],
            'genreType' => [
                'Rock',
                'Pop',
                'Classical',
                'Jazz',
                'Blues',
                'Hip-Hop',
                'Hardcore',
                'Metal',
                'Trance',
                'House',
                'Punk',
                'Grunge',
                'Folk',
                "Drum'n'bass",
                'Russian Chanson',
                'Retro',
                'Funk',
                'Ethnic',
                'Reggae',
                'Lounge',
            ],
        ]];

        $testMethod = $this->myTracklistService->createService();

        $this->assertSame($expected, $testMethod);
    }

    public function testStoreServiceIfAlbumAndCoverIsNULL(): void
    {
        $this->tracklistDto = $this->createMock(TracklistDTO::class);

        $this->tracklistDto->title = 'My live';
        $this->tracklistDto->author = 'Deskot';
        $this->tracklistDto->track_path = $this->uploadedFileMock;
        $this->tracklistDto->type = 'Music';
        $this->tracklistDto->genre = 'Rock';

        $this->fileUploaderMock->expects($this->any())
                               ->method('upload')
                               ->with($this->tracklistDto->track_path)
                               ->willReturn(new class() {
                                   public function getPath()
                                   {
                                       return 'URL';
                                   }
                               });

        $track = new Track();

        $track->setAuthor($this->tracklistDto->author);
        $track->setTrackPath($this->fileUploaderMock->upload($this->tracklistDto->track_path)->getPath());
        $track->setTitle($this->tracklistDto->title);
        $track->setGenre($this->tracklistDto->genre);
        $track->setType($this->tracklistDto->type);

        $this->entityManagerMock->expects($this->once())
                                ->method('persist')
                                ->with($track);
        $this->entityManagerMock->expects($this->once())
                                ->method('flush');

        $testMethod = $this->myTracklistService->storeService($this->tracklistDto);
        $this->assertSame($track->getAuthor(), $testMethod->getAuthor());
        $this->assertSame($track->getTitle(), $testMethod->getTitle());
        $this->assertSame($track->getTrackPath(), $testMethod->getTrackPath());
        $this->assertSame($track->getType(), $testMethod->getType());
        $this->assertSame($track->getGenre(), $testMethod->getGenre());
        $this->assertSame($track->getCover(), null);
        $this->assertSame($track->getAlbum(), null);
    }

    public function testStoreServiceIfAlbumAndCoverIsNotNULL(): void
    {
        $this->tracklistDto->title = 'My live';
        $this->tracklistDto->author = 'Deskot';
        $this->tracklistDto->track_path = $this->uploadedFileMock;
        $this->tracklistDto->type = 'Music';
        $this->tracklistDto->genre = 'Rock';
        $this->tracklistDto->album = 'This is my Live';
        $this->tracklistDto->cover = $this->uploadedFileMock;

        $this->fileUploaderMock->expects($this->any())
                               ->method('upload')
                               ->with($this->tracklistDto->track_path)
                               ->willReturn(new class() {
                                   public function getPath()
                                   {
                                       return 'URL TRACK';
                                   }
                               });
        $this->fileUploaderMock->expects($this->any())
                               ->method('upload')
                               ->with($this->tracklistDto->cover)
                               ->willReturn(new class() {
                                   public function getPath()
                                   {
                                       return 'URL IMAGE';
                                   }
                               });

        $track = new Track();

        $track->setAuthor($this->tracklistDto->author);
        $track->setTrackPath($this->fileUploaderMock->upload($this->tracklistDto->track_path)->getPath());
        $track->setTitle($this->tracklistDto->title);
        $track->setGenre($this->tracklistDto->genre);
        $track->setType($this->tracklistDto->type);
        $track->setAlbum($this->tracklistDto->album);
        $track->setCover($this->fileUploaderMock->upload($this->tracklistDto->cover)->getPath());

        $this->entityManagerMock->expects($this->once())
                                ->method('persist')
                                ->with($track);
        $this->entityManagerMock->expects($this->once())
                                ->method('flush');

        $testMethod = $this->myTracklistService->storeService($this->tracklistDto);

        $this->assertSame($track->getAlbum(), $testMethod->getAlbum());
        $this->assertSame($track->getCover(), $testMethod->getCover());
    }

    public function testUpdateServiceAllPropertyIsNull(): void
    {
        $this->trackRepositoryMock->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn(new Track());

        $testMethod = $this->myTracklistService->updateService($this->tracklistDto, 1);

        $this->assertSame($this->tracklistDto->title, $testMethod->getTitle());
        $this->assertSame($this->tracklistDto->cover, $testMethod->getCover());
        $this->assertSame($this->tracklistDto->album, $testMethod->getAlbum());
        $this->assertSame($this->tracklistDto->author, $testMethod->getAuthor());
        $this->assertSame($this->tracklistDto->type, $testMethod->getType());
        $this->assertSame($this->tracklistDto->genre, $testMethod->getGenre());
    }

    public function testUpdateServicePropertyGenreIsNotNull(): void
    {
        $this->trackRepositoryMock->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn(new Track());
        $this->tracklistDto->genre = 'Rock';

        $testMethod = $this->myTracklistService->updateService($this->tracklistDto, 1);

        $this->assertSame($this->tracklistDto->genre, $testMethod->getGenre());
    }

    public function testUpdateServicePropertyTypeIsNotNull(): void
    {
        $this->trackRepositoryMock->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn(new Track());
        $this->tracklistDto->type = 'Music';

        $testMethod = $this->myTracklistService->updateService($this->tracklistDto, 1);

        $this->assertSame($this->tracklistDto->type, $testMethod->getType());
    }

    public function testUpdateServicePropertyAuthorIsNotNull(): void
    {
        $this->trackRepositoryMock->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn(new Track());
        $this->tracklistDto->author = 'Deskot';

        $testMethod = $this->myTracklistService->updateService($this->tracklistDto, 1);

        $this->assertSame($this->tracklistDto->author, $testMethod->getAuthor());
    }

    public function testUpdateServicePropertyTitleIsNotNull(): void
    {
        $this->trackRepositoryMock->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn(new Track());
        $this->tracklistDto->title = 'My live';

        $testMethod = $this->myTracklistService->updateService($this->tracklistDto, 1);

        $this->assertSame($this->tracklistDto->title, $testMethod->getTitle());
    }

    public function testUpdateServicePropertyAlbumIsNotNull(): void
    {
        $this->trackRepositoryMock->expects($this->any())
            ->method('find')
            ->with(1)
            ->willReturn(new Track());
        $this->tracklistDto->album = 'This is my live';

        $testMethod = $this->myTracklistService->updateService($this->tracklistDto, 1);

        $this->assertSame($this->tracklistDto->album, $testMethod->getAlbum());
    }

    public function testUpdateServicePropertyCoverIsNotNull(): void
    {
        $track = new Track();
        $this->trackRepositoryMock->expects($this->exactly(2))
            ->method('find')
            ->with(1)
            ->willReturn($track);
        $this->tracklistDto->cover = $this->uploadedFileMock;
        $this->fileUploaderMock->expects($this->any())
                               ->method('upload')
                               ->with($this->tracklistDto->cover)
                               ->willReturn(new class() {
                                   public function getPath()
                                   {
                                       return 'URL IMAGE';
                                   }
                               });
        $track->setCover($this->fileUploaderMock->upload($this->tracklistDto->cover)->getPath());
        $this->fileSystemMock->expects($this->any())
                             ->method('remove')
                             ->with('../public/uploads/'.$track->getCover());

        $testMethod = $this->myTracklistService->updateService($this->tracklistDto, 1);

        $this->assertSame('URL IMAGE', $testMethod->getCover());
    }

    public function testShowServiceSuccess(): void
    {
        $this->trackRepositoryMock->expects($this->any())
                                  ->method('find')
                                  ->with(2)
                                  ->willReturn($this->track[1]);

        $expected = $this->entityManagerMock->getRepository(Track::class)->find(2);
        $testMethod = $this->myTracklistService->showService(2);

        $this->assertSame($expected, $testMethod);
    }

    public function testShowServiceError(): void
    {
        $this->expectException(MyTracklistException::class);
        $this->expectExceptionMessage('Can not find track');
        $this->myTracklistService->showService(10);
    }

    public function testEditServiceSuccess(): void
    {
        $this->trackRepositoryMock->expects($this->any())
                                  ->method('find')
                                  ->with(1)
                                  ->willReturn($this->track[0]);

        $expected = $this->entityManagerMock->getRepository(Track::class)->find(1);
        $testMethod = $this->myTracklistService->editService(1);

        $this->assertSame($expected, $testMethod);
    }

    public function testEditServiceError(): void
    {
        $this->expectException(MyTracklistException::class);
        $this->expectExceptionMessage('Can not find track');
        $this->myTracklistService->showService(10);
    }

    public function testDeleteServiceSuccess(): void
    {
        $this->trackRepositoryMock->expects($this->any())
                                                ->method('find')
                                                ->with(1)
                                                ->willReturn(new class() {
                                                    public function getCover()
                                                    {
                                                        return 'image.img';
                                                    }

                                                    public function getTrackPath()
                                                    {
                                                        return 'track.mp3';
                                                    }
                                                });

        $this->entityManagerMock->expects($this->any(1))
                                ->method('remove');

        $this->entityManagerMock->expects($this->any(1))
                                ->method('flush');

        $this->entityManagerMock->remove($this->trackRepositoryMock->find(1));

        $expected = [
            'success' => true,
            'body' => 'Track deleted successfully',
        ];

        $testMethod = $this->myTracklistService->deleteService(1);

        $this->assertSame($expected, $testMethod);
    }

    public function testDeleteServiceError(): void
    {
        $this->expectException(MyTracklistException::class);
        $this->expectExceptionMessage('Can not find track');
        $this->myTracklistService->deleteService(-1000);
    }
}
