<?php

namespace AppBundle\Manager;
use AppBundle\Entity\Image;
use GifCreator\GifCreator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

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
            $fs->mkdir($pathToSave, 0755);
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

    /**
     * @param $images
     * @return string
     * @throws \Exception
     */
    public function createGif($images)
    {
        $fs = new Filesystem();
        $pathToSave = $this->container->getParameter('gif_directory');
        $frames = array();
        $durations = array();

        /** @var Image $image */
        foreach ($images as $image) {
            $frames[] = $this->getRelativePath(
                $this->container->getParameter('image_directory_small'),
                $image->getSmallImage()
            );
            // Create an array containing the duration (in millisecond) of each frames (in order too)
            $durations[] = 50;
        }

        // Initialize and create the GIF
        $gc = new GifCreator();
        $gc->create($frames, $durations, 0);

        $gifBinary = $gc->getGif();

        if (!$fs->exists($pathToSave)) {
            $fs->mkdir($pathToSave, 0755);
        }

        $fileName = md5(uniqid()).'.gif';

        file_put_contents($this->getRelativePath($pathToSave, $fileName), $gifBinary);

        return $fileName;
    }

    /**
     * @param Request $request
     * @param $directory
     * @param $filename
     *
     * @return string
     */
    public function getAbsolutePath(Request $request, $directory, $filename)
    {
        return 'http://'.$request->getHost().'/'.$directory.'/'.$filename;
    }

    /**
     * @param $directory
     * @param $filename
     *
     * @return string
     */
    public function getRelativePath($directory, $filename)
    {
        return $directory.'/'.$filename;
    }
}