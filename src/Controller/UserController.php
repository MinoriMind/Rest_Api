<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Entity\User;

#[Route('/user', name: 'user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'register', methods: ['POST'])]
    public function register(Request $request): Response
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

        if (!isset($data['login'])) {
            return $this->json([
                'status' => 400,
                'message' => 'Login not found',
            ]);
        }

        if (!isset($data['password'])) {
            return $this->json([
                'status' => 400,
                'message' => 'Password not found',
            ]);
        }

        $login = $data['login'];
        $password = $data['password'];

        $user = new User();
        $user->setLogin($login);
        $user->setPassword(hash('sha256', $password));

        $em = $this->getDoctrine()->getManager();
        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException $exception) {
            return $this->json([
                'status' => 400,
                'message' => 'User with such login already registered',
            ]);
        }

        return $this->json([
            'status' => 200,
            'message' => 'User succesfullty registered',
        ]);
    }
}
