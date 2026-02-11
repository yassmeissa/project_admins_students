<?php

namespace App\Controller;


use App\Entity\Lesson;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Service\ThemeProvider;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/student/{idS}')]
class StudentCourseController extends AbstractController
{
  
    private $courseRepository;
    private $doctrine;
    private $themeProvider;
    private $userRepository;


    public function __construct(CourseRepository $courseRepository, ManagerRegistry $doctrine, ThemeProvider $themeProvider, UserRepository $userRepository)
    {
     
        $this->courseRepository = $courseRepository;
        $this->doctrine = $doctrine;
        $this->themeProvider = $themeProvider;
        $this->userRepository = $userRepository;

     
    }

    #[Route('/courses/{courseId}/lessons', name: 'course_lessons')]
    public function showCourseLessons(int $courseId, int $idS, Request $request): Response
    {
        $theme = $this->themeProvider->getTheme($request);

        // Récupérer le cours depuis la base de données
        $course = $this->courseRepository->find($courseId);

        if (!$course) {
            throw $this->createNotFoundException('Le cours n\'existe pas.');
        }

        // Récupérer les leçons associées au cours
        $lessons = $course->getLessons();

        // Récupérer les QCM associés au cours
        $qcms = $course->getQcms()->toArray();

        // Vérifier si des QCM existent
        if (empty($qcms)) {
            // Aucun QCM trouvé, vous pouvez gérer cela en conséquence
            $randomQcm = null; // Par exemple, définissez $randomQcm sur null
        } else {
            // Sélectionner un QCM aléatoire parmi ceux disponibles
            $randomQcm = $qcms[array_rand($qcms)];
        }

        // Afficher la vue Twig des leçons du cours
        return $this->render('student_course/lessons.html.twig', [
            'course' => $course,
            'lessons' => $lessons,
            'idS' => $idS,
            'qcm' => $randomQcm, 
            'theme' => $theme
        ]);
    }


    #[Route('/course/{courseId}/lesson/{id}/download', name: 'download_userlesson')]
    public function downloadLesson($id): Response
    {
        // Récupérer l'entité Lesson en fonction de l'ID
        $lesson = $this->doctrine->getRepository(Lesson::class)->find($id);
        // Vérifier si l'entité Lesson existe
        if (!$lesson) {
            throw $this->createNotFoundException('Lesson not found');
        }
        // Récupérer le chemin complet du fichier
        $fileDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/lessons/';
        $filePath = $fileDirectory . $lesson->getFile();
        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist.');
        }
        // Créer une réponse de fichier binaire
        $response = new BinaryFileResponse($filePath);
    
        // Définir les en-têtes de réponse pour le téléchargement avec le nom d'origine
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $lesson->getOriginalFileName() // Utiliser le nom d'origine du fichier
        ));
    
        return $response;
    }



   
    #[Route('/cours-disponibles', name: 'cours_disponibles')]
    public function afficherCoursDisponibles(CourseRepository $courseRepository, int $idS, Request $request): Response
    {
        $theme = $this->themeProvider->getTheme($request);
    
        // Récupérer l'utilisateur actuellement connecté par son ID dans l'URL
        $utilisateur = $this->userRepository->find($idS);
    
        // Récupérer tous les cours auxquels l'utilisateur n'est pas inscrit
        $coursNonInscrits = $this->courseRepository->findCoursNonInscrits($utilisateur);
    
        // Assurez-vous que $coursNonInscrits est bien une collection d'objets Course
        if (!is_iterable($coursNonInscrits)) {
            throw new \UnexpectedValueException('La méthode findCoursNonInscrits doit renvoyer une collection d\'objets Course.');
        }
    
        // Afficher la liste des cours disponibles dans le modèle de vue
        return $this->render('student_course/disponibles.html.twig', [
            'courses' => $coursNonInscrits, // Passer $coursNonInscrits à la vue
            'theme' => $theme,
            'idS' => $idS,
        ]);
    }
    



    #[Route('/inscrire-multiple', name: 'inscrire_multiple', methods: ['POST'])]
    public function inscrireMultiple(Request $request, CourseRepository $courseRepository, int $idS): Response
    {
        // Récupérer les identifiants des cours sélectionnés à partir de la requête POST
        $selectedCourseIds = $request->get('courses');
        dump($selectedCourseIds);
        // Récupérer l'utilisateur actuellement connecté
        $utilisateur = $this->getUser();
    
        // Récupérer tous les cours auxquels l'utilisateur n'est pas inscrit
        $coursNonInscrits = $courseRepository->findCoursNonInscrits($utilisateur);
    
        // Traiter l'inscription de l'utilisateur aux cours sélectionnés
        foreach ($selectedCourseIds as $courseId) {
            // Rechercher le cours correspondant dans le tableau d'objets
            $course = null;
            foreach ($coursNonInscrits as $cours) {
                if ($cours->getId() == $courseId) {
                    $course = $cours;
                    break;
                }
            }
    
            // Vérifier si le cours existe
            if ($course) {
                // Ajouter l'utilisateur à la liste des étudiants inscrits au cours
                $course->addUser($utilisateur);
            }
        }
    
        // Enregistrer les modifications dans la base de données
        $entityManager = $this->doctrine->getManager();
        $entityManager->flush();
    
        // Rediriger l'utilisateur vers une page de confirmation ou autre
        return $this->redirectToRoute('student_courses', ['idS' => $idS]);
    }
    




   
}
