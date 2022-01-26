<?php

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileService
{
    private $directory;
    private $slugger;

    public function __construct($directory, SluggerInterface $slugger)
    {
        $this->directory = $directory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getDirectory(), $fileName);
        } catch (FileException $e) {
            throw new Exception('exception: filename - '.$fileName.', safe filename - '.$safeFilename.', directory - '.$this->getDirectory());
        }

        return $fileName;
    }

    public function getDirectory()
    {
        return $this->directory;
    }
}
