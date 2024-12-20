<?php
namespace App\Controller;

// ...
use App\Entity\User;
use App\Service\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ApiController extends AbstractController
{

    #[Route('/api/register', name: 'api_register', methods: ['POST', 'OPTIONS'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ApiService $apiService
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
            $response->headers->set('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            return $response;
        }

        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $apiService->validateForm($email, $password);

        $user = new User();
        $user->setUuid(Uuid::v4()->toRfc4122());
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);

        $entityManager->persist($user);
        $entityManager->flush();

        $response = new JsonResponse([
            'message' => 'Пользователь успешно зарегистрирован'
        ], Response::HTTP_CREATED);

        // Добавление CORS-заголовков
        $response->headers->set('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
        return $response;
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['message' => 'Пожалуйста, укажите email и пароль.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['message' => 'Неверный email или пароль.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['message' => 'Неверный email или пароль.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $jwtManager->create($user);

        return new JsonResponse(['token' => $token], Response::HTTP_OK);
    }
}