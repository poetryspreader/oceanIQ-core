<?php
namespace App\Controller;

// ...
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
//use Symfony\Component\Validator\Constraints\Uuid;
//use Symfony\Component\Uid\Uuid; // Импорт правильного класса


class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST', 'OPTIONS'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
            $response->headers->set('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, DELETE');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            return $response;
        }

        $data = json_decode($request->getContent(), true);

        // Валидация данных
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['error' => 'UUID и пароль обязательны'], Response::HTTP_BAD_REQUEST);
        }

        // Создание нового пользователя
        $user = new User();
        $user->setUuid(Uuid::v4()->toRfc4122()); // Генерация UUID в контроллере
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);

        // Сохранение в базе данных
        $entityManager->persist($user);
        $entityManager->flush();

        $response = new JsonResponse([
            'message' => 'Пользователь успешно зарегистрирован',
            'email' => $email,
            'password' => $password
        ], Response::HTTP_CREATED);

        // Добавление CORS-заголовков
        $response->headers->set('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
        return $response;
    }
}