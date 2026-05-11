<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Photos;
use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: '/api', name: 'api_')]
class ApiAuthenticationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtTokenManager,
        private ValidatorInterface $validator,
    ) {}

    #[Route(path: '/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données reçues
        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'error' => 'Email et mot de passe requis',
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        $password = $data['password'];

        // Recherche de l'utilisateur par email
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'error' => 'Email ou mot de passe incorrect',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Vérification du mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'error' => 'Email ou mot de passe incorrect',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Génération du token JWT
        $token = $this->jwtTokenManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName() ?? '',
                'lastName' => $user->getLastName() ?? '',
            ],
        ], Response::HTTP_OK);
    }

    #[Route(path: '/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données reçues
        $errors = [];
        if (!isset($data['email']) || empty($data['email'])) {
            $errors[] = 'Email requis';
        }
        if (!isset($data['password']) || empty($data['password'])) {
            $errors[] = 'Mot de passe requis';
        }
        if (!isset($data['firstName']) || empty($data['firstName'])) {
            $errors[] = 'Prénom requis';
        }
        if (!isset($data['lastName']) || empty($data['lastName'])) {
            $errors[] = 'Nom requis';
        }

        if (!empty($errors)) {
            return $this->json([
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        $password = $data['password'];
        $firstName = $data['firstName'];
        $lastName = $data['lastName'];

        // Vérification si l'utilisateur existe déjà
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            return $this->json([
                'error' => 'Cet email est déjà utilisé',
            ], Response::HTTP_CONFLICT);
        }

        // Création du nouvel utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Génération du token JWT
        $token = $this->jwtTokenManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route(path: '/my-data', name: 'my_data', methods: ['GET'])]
    public function myData(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $serialize = fn($photo) => [
            'id'          => $photo->getId(),
            'photoUrl'    => $photo->getPhotoUrl(),
            'description' => $photo->getDescription(),
            'public'      => $photo->isPublic(),
            'localisation'=> $photo->getLocalisation() ?? '',
            'date_prise'  => $photo->getDatePrise()?->format('Y-m-d') ?? '',
            'themes'      => $photo->getThemes()->map(fn($t) => ['id' => $t->getId(), 'nom' => $t->getNom()])->toArray(),
        ];

        $privatePhotos = $this->entityManager->getRepository(Photos::class)
            ->findBy(['userPhoto' => $user, 'public' => false], ['date_added' => 'DESC']);
        $publicPhotos = $this->entityManager->getRepository(Photos::class)
            ->findBy(['userPhoto' => $user, 'public' => true], ['date_added' => 'DESC']);
        $albums = $this->entityManager->getRepository(Album::class)
            ->findBy(['user' => $user]);

        return $this->json([
            'user' => [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'firstName' => $user->getFirstName() ?? '',
                'lastName'  => $user->getLastName() ?? '',
            ],
            'privatePhotos' => array_map($serialize, $privatePhotos),
            'publicPhotos'  => array_map($serialize, $publicPhotos),
            'albums' => array_map(fn($album) => [
                'id'       => $album->getId(),
                'categorie'=> $album->getCategorie(),
                'count'    => $album->getPhotos()->count(),
                'cover'    => $album->getPhotos()->first() ? $album->getPhotos()->first()->getPhotoUrl() : null,
            ], $albums),
        ]);
    }

    #[Route(path: '/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Non authentifié',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName() ?? '',
                'lastName' => $user->getLastName() ?? '',
            ],
        ], Response::HTTP_OK);
    }
}
