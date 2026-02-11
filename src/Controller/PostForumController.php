<?php

namespace App\Controller;

use App\Entity\PostForum;
use App\Entity\User;
use App\Form\PostForumType;
use App\Repository\PostForumRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;


#[Route('/student/{idS}/post_forum')]
class PostForumController extends AbstractController
{
    private $doctrine;
    

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        
    }
    #[Route('/', name: 'post_forum_index')]
    public function index(PostForumRepository $postForumRepository, int $idS, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Récupérer tous les messages du forum
        $posts = $postForumRepository->findAll();

        // Créer un tableau pour regrouper les messages par cours
        $postsByCourse = [];

        // Regrouper les messages par cours
        foreach ($posts as $post) {
            $courseName = $post->getCourse()->getName();
            if (!isset($postsByCourse[$courseName])) {
                $postsByCourse[$courseName] = [];
            }
            $postsByCourse[$courseName][] = $post;
        }

        return $this->render('post_forum/index.html.twig', [
            'postsByCourse' => $postsByCourse,
            'theme' => $theme,
            'idS' => $idS
        ]);
    }
    
    #[Route('/new', name: 'post_forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $idS): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        $post = new PostForum();
        $form = $this->createForm(PostForumType::class, $post);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'utilisateur à partir de l'ID dans l'URL
            $userId = $idS; // Suppose que l'ID de l'utilisateur est passé dans l'URL
            $user = $this->doctrine->getRepository(User::class)->find($userId);
            
            // Vérifier si l'utilisateur existe
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur non trouvé');
            }
    
            // Définir l'utilisateur sur l'entité PostForum
            $post->setStudent($user);
    
            // Définir la date avant de persister l'entité
            $post->setDate(new \DateTime());
    
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
    
            return $this->redirectToRoute('post_forum_index', ['idS' => $idS]);
        }
    
        return $this->render('post_forum/new.html.twig', [
            'post' => $post,
            'idS' => $idS,
            'theme' => $theme,
            'form' => $form->createView(),
        ]);
    }
    

    #[Route('/{id}', name: 'post_forum_show', methods: ['GET'])]
    public function show(PostForum $post, int $idS, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        return $this->render('post_forum/show.html.twig', [
            'post' => $post,
            'theme' =>$theme,
            'idS' => $idS
        ]);
    }

    #[Route('/{id}/edit', name: 'post_forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PostForum $post, int $idS): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        $form = $this->createForm(PostForumType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();

            return $this->redirectToRoute('post_forum_index', ['idS' => $idS]);
        }

        return $this->render('post_forum/edit.html.twig', [
            'post' => $post, 
            'theme' => $theme,
            'idS' =>$idS,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'post_forum_delete', methods: ['POST'])]
    public function delete(Request $request, PostForum $post, int $idS): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('post_forum_index', ['idS' => $idS]);
    }
}
