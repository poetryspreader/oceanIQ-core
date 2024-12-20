<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;
use Doctrine\ORM\EntityManagerInterface;

class ApiService
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    function validateForm($email, $password): ?JsonResponse
    {
        if (!$email || !$password) {
            return new JsonResponse(
                ['error' => 'UUID и пароль обязательны'], Response::HTTP_BAD_REQUEST // 400
            );
        }

        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            return new JsonResponse(
                ['error' => 'Пользователь с таким email уже существует.'], Response::HTTP_CONFLICT // 409
            );
        }

        $validator = Validation::createValidator();
        $passwordViolations = $validator->validate($password, [
            new NotBlank(),
            new Regex([
                'pattern' => '/.{6,}/',
                'message' => 'Пароль должен содержать минимум 6 символов.'
            ]),
            new Regex([
                'pattern' => '/[A-Z]/',
                'message' => 'Пароль должен содержать хотя бы одну заглавную букву.'
            ]),
            new Regex([
                'pattern' => '/[a-z]/',
                'message' => 'Пароль должен содержать хотя бы одну строчную букву.'
            ]),
            new Regex([
                'pattern' => '/[0-9]/',
                'message' => 'Пароль должен содержать хотя бы одну цифру.'
            ]),
            new Regex([
                'pattern' => '/[!@#$%^&*(),.?":{}|<>]/',
                'message' => 'Пароль должен содержать хотя бы один специальный символ.'
            ])
        ]);

        if (count($passwordViolations) > 0) {
            foreach ($passwordViolations as $violation) {
                throw new \InvalidArgumentException($violation->getMessage());
            }
        }

        return null;
    }
}