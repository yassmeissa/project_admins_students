<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/admin/{idA}/user')]
class UserController extends AbstractController
{
    private $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/manage-users', name: 'user_index')]
    public function manageUsers(int $idA, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        $users = $this->userRepository->findNonAdminUsers();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'theme' =>$theme,
            'admin_id' => $idA,
        ]);
    }
   
    #[Route('/new', name: 'new_user', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, int $idA): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Créer une nouvelle instance de l'entité User
        $user = new User();
        // Créer le formulaire à partir du UserType avec l'entité User
        $form = $this->createForm(UserType::class, $user);
        // Gérer la requête
        $form->handleRequest($request);
        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword)->setRoles(['Student']);
            // Persister l'entité User
            $entityManager->persist($user);
            // Exécuter les requêtes SQL en attente
            $entityManager->flush();
            // Redirection vers la page de gestion des utilisateurs
            return $this->redirectToRoute('user_index', ['idA' => $idA]);
        }
        // Rendre le formulaire dans le template twig
        return $this->render('user/new.html.twig', [
            'user' => $user,
            'theme' =>$theme,
            'form' => $form->createView(),
            'admin_id' => $idA
        ]);
    }


    #[Route('/{id}', name: 'show_user', methods: ['GET'])]
    public function show(User $user, int $idA, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        return $this->render('user/show.html.twig', [
            'user' => $user,
            'theme' =>$theme,
            'admin_id' => $idA,
            
        ]);
    }


    #[Route('/{id}/edit', name: 'edit_user' , methods: ['GET', 'POST'])]
   public function editUser(Request $request, User $user, EntityManagerInterface $entityManager, int $idA): Response
   {
    $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

       $form = $this->createForm(UserType::class, $user);
       $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
           $entityManager->flush();
           return $this->redirectToRoute('user_index', ['idA' => $idA]);
       }
       return $this->render('user/edit.html.twig', [
           'user' => $user,
           'form' => $form,
           'theme' =>$theme,
           'admin_id' => $idA
       ]);
   }


    #[Route('/{id}/delete', name: 'delete_user', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager, int $idA): Response
    {
        

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('user_index', ['idA' => $idA]);
    }

    #[Route("/search", name: "search_user", methods: ["GET"])]
    public function search(Request $request, $idA, UserRepository $userRepository): Response
   {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
 
       // Récupérer le terme de recherche de la requête GET
       $query = $request->query->get('query');

       // Rechercher les utilisateurs par nom d'utilisateur
       $users = $userRepository->findByUsername($query);

       return $this->render('user/search.html.twig', [
           'users' => $users,
           'theme' => $theme,
           'admin_id' => $idA,
           'searchQuery' =>$query,
       ]);
   }

}


