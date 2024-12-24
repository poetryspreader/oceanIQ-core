<?php

namespace App\Controller;

use App\Entity\Product;
use App\Enum\ProductCategoryEnum;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'product', methods: ['POST'])]
    public function product(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setUuid(Uuid::uuid4()->toString());
        $product->setName($data['name']);
        $product->setType(ProductCategoryEnum::tryFrom($data['type']));
        $product->setPrice($data['price']);
        $product->setAmount($data['amount']);
        $product->setSize($data['size']);

        $entityManager->persist($product);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Продукт успешно добавлен!'
        ], Response::HTTP_CREATED);
    }
}