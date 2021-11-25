<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

#[Route('/todo', name: 'todo')]
class TodoController extends AbstractController
{
    #[Route('/', name: 'create', methods:["POST"])]
    public function create(Request $request, UserRepository $user_rep): Response
    {
        $content_type = $request->getContentType();
        if ($content_type != 'json') {
            return $this->json([
                'status' => 400,
                'message' => 'Only application/json content type is allowed',
            ]);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $this->json([
                'status' => 400,
                'message' => 'Error during parsing json',
            ]);
        }

        if(!isset($data['login']))
        {
            return $this->json([
                'status' => 400,
                'message' => 'Login not found',
            ]);
        }

        if(!isset($data['password']))
        {
            return $this->json([
                'status' => 400,
                'message' => 'Password not found',
            ]);
        }

        if(!isset($data['name']))
        {
            return $this->json([
                'status' => 400,
                'message' => 'Name not found',
            ]);
        }

        if(!isset($data['text']))
        {
            return $this->json([
                'status' => 400,
                'message' => 'Text not found',
            ]);
        }

        $login = $data['login'];
        $password = $data['password'];

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

        $todo = new Todo();
        $todo->setName($data["name"]);
        $todo->setText($data["text"]);
        $todo->setUser($user);

        $em = $this->getDoctrine()->getManager();
        $em = $this->getDoctrine()->getManager();
        try 
        {
            $em->persist($todo);
            $em->flush();
        } 
        catch (UniqueConstraintViolationException $exception)
        {
            return $this->json([
                'status' => 400,
                'message' => 'Todo with same name exists',
            ]);
        }

        return $this->json([
            'status' => 200,
            'message' => 'Todo successfully added',
        ]);

    }

    #[Route('/', name: 'get', methods:["GET"])]
    public function getTodo(Request $request, UserRepository $user_rep, TodoRepository $todo_rep): Response
    {
        $content_type = $request->getContentType();
        if ($content_type != 'json') {
            return $this->json([
                'status' => 400,
                'message' => 'Only application/json content type is allowed',
            ]);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $this->json([
                'status' => 400,
                'message' => 'Error during parsing json',
            ]);
        }

        if(!isset($data['login']))
        {
            return $this->json([
                'status' => 400,
                'message' => 'Login not found',
            ]);
        }

        if(!isset($data['password']))
        {
            return $this->json([
                'status' => 400,
                'message' => 'Password not found',
            ]);
        }

        $login = $data['login'];
        $password = $data['password'];

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

        $todos = $todo_rep->findBy([
            'user' => $user
        ]);

        $result = [];
        foreach ($todos as $todo) {
            $array = [
                'id' => $todo->getId(),
                'name' => $todo->getName(),
                'text' => $todo->getText()
            ];

            $result[] = $array;
        }

        return $this->json([
            'status' => 200,
            'message' => $result,
        ]);

    }
}
