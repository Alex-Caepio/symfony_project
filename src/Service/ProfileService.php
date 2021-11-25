<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\User;
use App\Exception\FileUploadException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProfileService
{
    private const PROFILE_PHOTO = [
        'size' => 39936,
        'height' => 100,
        'width' => 100,
    ];

    private $em;
    private $fileUploader;

    public function __construct(EntityManagerInterface $em, FileUploader $fileUploader)
    {
        $this->em = $em;
        $this->fileUploader = $fileUploader;
    }

    /** Validate Profile Photo
     *
     * @param UploadedFile $photo
     * @return string[]|bool
     */
    public function validateProfilePhoto(UploadedFile $photo)
    {
        if (empty($photo)) {
            return ['error'=>'No file provided.'];
        }

        if ($photo->getSize() > self::PROFILE_PHOTO['size']) {
            return ['error' => 'Image size must be 39kb or less.'];
        }

        $profilePhotoSize = getimagesize($photo->getPathname());
        if ($profilePhotoSize[1] > self::PROFILE_PHOTO['height'] || $profilePhotoSize[0] > self::PROFILE_PHOTO['width']) {
            return ['error' => 'Image resolution must be 100x100.'];
        }

        return true;
    }

    /** Upload Profile Photo
     *
     * @param User $user
     * @param UploadedFile $photo
     * @return string[]|bool
     */
    public function uploadProfilePhoto(User $user, UploadedFile $photo)
    {
        try {
            $file = $this->fileUploader->upload($photo);
        } catch (FileUploadException $e) {
            return ['error' => 'An error occurred during upload.'];
        }

        $user->setProfilePhoto($file->getId());
        $file->setUser($user);

        $this->em->persist($file);
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }
}