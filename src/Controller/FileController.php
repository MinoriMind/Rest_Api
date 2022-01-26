<?php

namespace App\Controller;

use App\Entity\File;
use App\Repository\FileRepository;
use App\Repository\UserRepository;
use App\Service\FileService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

#[Route('/files', name: 'file')]
class FileController extends AbstractController
{
    #[Route('/', name: 'upload', methods: ['POST'])]
    public function upload(Request $req, FileService $file_service, UserRepository $user_rep, FileRepository $file_rep): Response
    {
        $login = $req->headers->get('Client-Login');
        $password = $req->headers->get('Client-Password');

        $user = $user_rep->findOneBy([
            'login' => $login,
            'password' => hash('sha256', $password)
        ]);

        if(!$user)
        {
            return $this->json([
                'status' => 400,
                'message' => 'User not found',
            ]);
        }

        $given_file = $req->files->get('file');
        if(!$given_file)
        {
            return $this->json([
                'status' => 400,
                'message' => 'File not found',
            ]);
        }

        $file = $file_rep->findOneBy([
            'originalName' => $given_file->getClientOriginalName(),
            'owner' => $user->getId(),
        ]);

        if($file)
        {
            return $this->json([
                'status' => 400,
                'message' => 'File already uploaded',
            ]);
        }

        try
        {
            $safeFilename = $file_service->upload($given_file);
            $file = new File();
            $file->setSafeName($safeFilename);
            $file->setOriginalName($given_file->getClientOriginalName());
            $file->setOwner($user);

            $user->addFile($file);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->persist($file);
            $em->flush();

            return $this->json([
                'status' => 200,
                'message' => 'File successfully uploaded',
            ]);
        }
        catch (Exception $e)
        {
            return $this->json([
                'status' => 400,
                'message' => 'Exception occured during file uploading',
                'exception message' => $e->getMessage(),
            ]);
        }
    }

    #[Route('/', name: 'getFiles', methods: ['GET'])]
    public function getFiles(Request $req, UserRepository $user_rep, FileRepository $file_rep): Response
    {
        $login = $req->headers->get('Client-Login');
        $password = $req->headers->get('Client-Password');

        $user = $user_rep->findOneBy([
            'login' => $login,
            'password' => hash('sha256', $password)
        ]);

        if(!$user)
        {
            return $this->json([
                'status' => 400,
                'message' => 'User not found',
            ]);
        }

        $files = $file_rep->findBy([
            'owner' => $user->getId(),
        ]);

        $result = [];
        foreach ($files as $file) {
            $array = [
                'filename' => $file->getOriginalName(),
            ];

            $result[] = $array;
        }

        return $this->json([
            'status' => 200,
            'files' => $result,
        ]);
    }

    #[Route('/{filename}', name: 'download', methods: ['GET'])]
    public function download(Request $req, UserRepository $user_rep, FileRepository $file_rep, $filename): Response
    {
        $login = $req->headers->get('Client-Login');
        $password = $req->headers->get('Client-Password');

        $user = $user_rep->findOneBy([
            'login' => $login,
            'password' => hash('sha256', $password)
        ]);

        if(!$user)
        {
            return $this->json([
                'status' => 400,
                'message' => 'User not found',
            ]);
        }

        $file = $file_rep->findOneBy([
            'owner' => $user->getId(),
            'originalName' => $filename,
        ]);

        if ($file) 
        {
            $result_file = $this->getParameter('uploads_dir').'/'.$file->getSafeName();

            return new BinaryFileResponse($result_file);
        } 
        else 
        {
            return $this->json([
                'status' => 400,
                'message' => 'File not found',
            ]);
        }
    }

    #[Route('/{filename}', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $req, UserRepository $user_rep, FileRepository $file_rep, $filename): Response
    {
        $login = $req->headers->get('Client-Login');
        $password = $req->headers->get('Client-Password');

        $user = $user_rep->findOneBy([
            'login' => $login,
            'password' => hash('sha256', $password)
        ]);

        if(!$user)
        {
            return $this->json([
                'status' => 400,
                'message' => 'User not found',
            ]);
        }

        $file = $file_rep->findOneBy([
            'owner' => $user->getId(),
            'originalName' => $filename,
        ]);

        if(!$file)
        {
            return $this->json([
                'status' => 400,
                'message' => 'File not found',
            ]);
        }

        try
        {
            $em = $this->getDoctrine()->getManager();
            $em->remove($file);
            $em->flush();

            $filesystem = new Filesystem();
            $filesystem->remove([$this->getParameter('uploads_dir').'/'.$file->getSafeName()]);

            return $this->json([
                'status' => 200,
                'message' => 'File deleted',
            ]);
        }
        catch (IOExceptionInterface $e) 
        {
            return $this->json([
                'status' => 400,
                'message' => 'Exception occured during file deletion',
                'exception message' => $e->getMessage(),
            ]);
        }
    }
}
