<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;




#[Route('/admin/{idA}')]
class AdminController extends AbstractController
{


    #[Route('/', name: 'app_admin')]
    public function index(int $idA, Request $request, UserRepository $userRepository): Response
    {
        // Récupérer l'utilisateur à partir de l'ID
        $user = $userRepository->find($idA);

        // Récupérer le thème stocké en session
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'admin_id' => $idA,
            'admin_user' => $user, 
            'theme' => $theme, 
        ]);
    }
    

    #[Route('/change-theme', name: 'app_admin_change_theme')]
    public function changeTheme(int $idA, Request $request, RouterInterface $router): Response
    {
        // Récupérer le thème sélectionné depuis le formulaire
        $newTheme = $request->request->get('theme');

        // Vérifier si le thème est valide
        if (!in_array($newTheme, ['light', 'dark'])) {
            throw $this->createNotFoundException('Invalid theme.');
        }

        // Créer une réponse de redirection
        $referer = $request->headers->get('referer');
        $response = $this->redirect($referer ?: $this->generateUrl('app_admin', ['idA' => $idA]));
        
        // Stocker le nouveau thème dans un cookie
        $response->headers->setCookie(new Cookie(
            'theme',
            $newTheme,
            time() + (365 * 24 * 60 * 60), // 1 year
            '/',
            null,
            false,
            false
        ));
        
        return $response;
    }
}
