<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/student/{idS}')]
class StudentController extends AbstractController
{

 

    #[Route('/', name: 'app_student')]
    public function index(Request $request, int $idS, UserRepository $userRepository): Response
    {

        // Récupérer l'utilisateur à partir de l'ID
        $student = $userRepository->find($idS);
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Vérifier si le pop-up a déjà été affiché (via cookie ou session)
        if (!$request->cookies->has('first_login')) {
            // Si c'est la première connexion, afficher le pop-up
            return $this->redirectToRoute('show_popup', ['idS' => $idS]);
        }

        // Sinon, afficher la page d'accueil normale
        return $this->render('student/index.html.twig', [
            'student_id' => $idS,
            'student' => $student,
            'theme' => $theme,
        ]);
    }

    #[Route('/change-theme', name: 'app_student_change_theme')]
    public function changeTheme(int $idS, Request $request, RouterInterface $router): Response
    {
        // Récupérer le thème sélectionné depuis le formulaire
        $newTheme = $request->request->get('theme');

        // Vérifier si le thème est valide
        if (!in_array($newTheme, ['light', 'dark'])) {
            throw $this->createNotFoundException('Invalid theme.');
        }

        // Créer une réponse de redirection
        $referer = $request->headers->get('referer');
        $response = $this->redirect($referer ?: $this->generateUrl('app_student', ['idS' => $idS]));
        
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




    #[Route('/search_usercourse', name: 'search_usercourse')]
    public function searchUserCourse(Request $request, int $idS, CourseRepository $courseRepository): Response
    {
        // Récupérer le terme de recherche depuis la requête
        $query = $request->query->get('query');

        // Si le terme de recherche est vide, rediriger vers la page d'accueil de l'utilisateur
        if (empty($query)) {
            return $this->redirectToRoute('app_student', ['idS' => $idS]);
        }
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";


        // Rechercher les cours de l'utilisateur correspondant au terme de recherche
        $courses = $courseRepository->searchCoursesByUser($idS, $query); 
        shuffle($courses);
        // Rendre la vue avec les résultats de la recherche
        return $this->render('student_course/search.html.twig', [
            'courses' => $courses,
            'student_id' => $idS,
            'query' => $query,
            'theme' =>$theme,
        ]);
    }

    #[Route('/api/search_courses', name: 'api_search_courses', methods: ['GET'])]
    public function apiSearchCourses(Request $request, int $idS, CourseRepository $courseRepository): JsonResponse
    {
        $query = $request->query->get('query', '');
        
        // Rechercher les cours correspondant au terme de recherche
        $courses = $courseRepository->searchCoursesByUser($idS, $query);
        
        // Formater les données pour retourner au JavaScript
        $data = [];
        foreach ($courses as $course) {
            $data[] = [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'imageUrl' => $course->getTheme()?->getImageUrl() ?? null,
                'themeName' => $course->getTheme()?->getName() ?? 'Aucun thème',
                'url' => $this->generateUrl('course_lessons', ['idS' => $idS, 'courseId' => $course->getId()]),
            ];
        }
        
        return new JsonResponse($data);
    }



    #[Route('/courses', name: 'student_courses')]
    public function course_student(CourseRepository $courseRepository, UserRepository $userRepository, Request $request, int $idS): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Récupérer l'utilisateur à partir de l'ID dans l'URL
        $user = $userRepository->find($idS);

        // Récupérer les cours auxquels l'utilisateur est inscrit
        $courses = $courseRepository->findCoursesByUser($user);
        shuffle($courses);

        // Rendre la vue Twig avec les données nécessaires
        return $this->render('student_course/index.html.twig', [
            'user' => $user,
            'courses' => $courses,
            'student_id' => $idS,
            'theme' =>$theme,
        ]);
    }

  

    

}
