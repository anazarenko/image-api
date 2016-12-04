<?php

namespace AppBundle\Manager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class ImageManager
 * @package AppBundle\Manager
 */
class ImageManager
{
    const TYPE_SMALL = 1;
    const TYPE_BIG = 2;

    /** @var ContainerInterface */
    private $container;

    /**
     * ImageManager constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $filename
     * @param string $pathToSave
     * @param int $width max width value
     * @param int $height max height width
     *
     * @return string
     */
    public function resizeImage($filename, $pathToSave, $width, $height)
    {
        $file = new File($filename);
        $fs = new Filesystem();

        // Get new sizes
        list($width_orig, $height_orig) = getimagesize($filename);

        $ratio_orig = $width_orig/$height_orig;

        if ($width/$height > $ratio_orig) {
            $width = $height*$ratio_orig;
        } else {
            $height = $width/$ratio_orig;
        }

        $image_p = imagecreatetruecolor($width, $height);

        if ($file->getExtension() === 'jpeg') {
            $image = imagecreatefromjpeg($filename);
        } else {
            $image = imagecreatefrompng($filename);
        }

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        if (!$fs->exists($pathToSave)) {
            $fs->mkdir($pathToSave, 0700);
        }

        $newFilename = md5($file->getFilename()).'.'.$file->getExtension();

        // Save image
        try {
            if ($file->getExtension() === 'jpeg') {
                imagejpeg($image_p, $pathToSave . '/' . $newFilename, 100);
            } else {
                imagepng($image_p, $pathToSave . '/' . $newFilename, 9);
            }
        } catch(\Exception $e) {
            $newFilename = '';
        }

        return $newFilename;
    }
}