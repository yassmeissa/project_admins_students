<?php

namespace App\Controller;

use App\Entity\NoteStudent;
use App\Entity\QCM;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\NoteStudentRepository;
use App\Repository\QCMRepository;
use App\Repository\QuestionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

#[Route('/student/{idS}')]
class StudentQCMController extends AbstractController
{

    private $noteStudentRepository;
    private $QCMRepository;
    private $questionRepository;
    private $courseRepository;
    private $threshold;
    private $doctrine;
    
    // Modifier le constructeur pour initialiser CourseRepository
    public function __construct(ManagerRegistry $doctrine, NoteStudentRepository $noteStudentRepository, QCMRepository $QCMRepository, QuestionRepository $questionRepository, CourseRepository $courseRepository, int $threshold = 70)
    {
        // Assigner les dépendances aux propriétés de la classe
        $this->noteStudentRepository = $noteStudentRepository;
        $this->QCMRepository = $QCMRepository;
        $this->questionRepository = $questionRepository;
        $this->courseRepository = $courseRepository; // Ajouter cette ligne
        $this->threshold = $threshold;
        $this->doctrine = $doctrine;
        
    }

    const MAX_ATTEMPTS = 3;

    

    #[Route('/courses/{courseId}/qcm/{qcmId}', name: 'student_qcm', methods: ['GET'])]
    public function showRandomQcm(QCMRepository $QCMRepository, CourseRepository $courseRepository, QuestionRepository $questionRepository, int $idS, int $courseId, int $qcmId, NoteStudentRepository $noteStudentRepository, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Vérifier le nombre de fois que l'utilisateur a passé ce QCM
        $userAttempts = $noteStudentRepository->countAttemptsByUserAndQCM($idS, $qcmId);
    
        // Vérifier si l'utilisateur peut passer le QCM une autre fois
        if ($userAttempts >= self::MAX_ATTEMPTS) {            // Si l'utilisateur a déjà passé le QCM trois fois, rediriger ou renvoyer un message d'erreur
            return $this->redirectToRoute('student_course_error', ['error' => 'Vous avez déjà passé ce QCM trois fois.']);
        }
    
        // Récupérer un cours
        $course = $courseRepository->find($courseId);
    
        if (!$course) {
            throw $this->createNotFoundException('Le cours demandé n\'existe pas.');
        }
    
        // Vérifier si le cours a le QCM spécifié
        $qcm = $QCMRepository->findOneBy(['id' => $qcmId, 'course' => $course]);
    
        if (!$qcm) {
            throw $this->createNotFoundException('Le QCM demandé n\'existe pas pour ce cours.');
        }
        // Récupérer les questions associées au QCM
        $questions = $questionRepository->findBy(['qcm' => $qcm]);
    
        return $this->render('student_course/qcm.html.twig', [
            'qcm' => $qcm,
            'questions' => $questions,
            'student_id' => $idS,
            'theme' =>$theme
        ]);
    }
    

   
    #[Route('/courses/{courseId}/qcm/{qcmId}/submit_qcm', name: 'submit_qcm', methods: ['POST'])]
    public function submitQcm(Request $request, int $idS, int $courseId, int $qcmId, NoteStudentRepository $noteStudentRepository): Response
    {
        
        // Vérifier le nombre de fois que l'utilisateur a passé ce QCM
        $userAttempts = $noteStudentRepository->countAttemptsByUserAndQCM($idS, $qcmId);
        $userAttempts++;
        // Vérifier si l'utilisateur peut passer le QCM une autre fois
        if ($userAttempts >= self::MAX_ATTEMPTS) {
            // Si l'utilisateur a déjà passé le QCM trois fois, rediriger ou renvoyer un message d'erreur
            return $this->redirectToRoute('student_course_error', ['error' => 'Vous avez déjà passé ce QCM trois fois.']);
        }
    
        $scorePercentage = $this->calculateStudentScore($request->request->all()['answers'], $qcmId);
        $previousScore = $noteStudentRepository->findOneBy(['users' => $idS, 'QCMs' => $qcmId]);
        
       
        $all = $request->request->all();
        $userAnswers = $all['answers'];
        

        // Vérifie si le résultat n'est pas null et si c'est une instance de NoteStudent
        if ($previousScore !== null && $previousScore instanceof NoteStudent) {
            // Comparer les scores uniquement si le résultat est conforme à nos attentes
            if ($scorePercentage > $previousScore->getScore()) {
                // Mettre à jour la base de données uniquement si la note est supérieure à celle précédente
                $this->updateStudentScore($idS, $qcmId, $scorePercentage);
            }
        } elseif ($previousScore !== null) {
            // Si le résultat n'est pas null mais n'est pas une instance de NoteStudent, cela peut indiquer un problème
            throw new \RuntimeException('La méthode findOneBy a retourné un objet inattendu.');
        } else {
            // Si le résultat est null, cela signifie qu'il n'y a pas de score précédent pour cet utilisateur et ce QCM
            $this->updateStudentScore($idS, $qcmId, $scorePercentage);
        }
    
        $cookie = new Cookie('qcm_results', json_encode(['qcm_id' => $qcmId, 'score' => $scorePercentage, 'user_answers' => $userAnswers]), strtotime('+1 day'));
        // Créer une réponse avec une redirection
        $response = $this->redirectToRoute('recommendations', ['idS' => $idS]);
        // Ajouter le cookie à la réponse
        $response->headers->setCookie($cookie);
    
        // Retourner la réponse avec la redirection et le cookie
        return $response;
    

    }
    

    public function updateStudentScore(int $userId, int $qcmId, float $scorePercentage): void
    {
        $entityManager = $this->doctrine->getManager();
        $userRepository = $entityManager->getRepository(User::class);
        $QCMRepository = $entityManager->getRepository(QCM::class);
        $user = $userRepository->find($userId);
        $qcm = $QCMRepository->find($qcmId);
    
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé pour l\'ID donné.');
        }
    
        if (!$qcm) {
            throw $this->createNotFoundException('QCM non trouvé pour l\'ID donné.');
        }
    
        // Utiliser la méthode findOneBy pour obtenir le score précédent
        $previousScore = $this->noteStudentRepository->findOneBy(['users' => $user, 'QCMs' => $qcm]);
    
        // Vérifier si le résultat est un objet NoteStudent
        if ($previousScore instanceof NoteStudent) {
            // Accéder au score précédent
            $previousScoreValue = $previousScore->getScore();
    
            // Assurez-vous que le résultat retourné est un nombre flottant
            if (!is_float($previousScoreValue)) {
                throw new \InvalidArgumentException('Le score précédent n\'a pas le bon format.');
            }
    
            // Vérifier si le nouveau score est supérieur au score précédent
            if ($scorePercentage > $previousScoreValue) {
                // Mettre à jour le score existant
                $previousScore->setScore($scorePercentage);
                $entityManager->flush();
            }
        } else {
            // Créer une nouvelle entité NoteStudent
            $noteStudent = new NoteStudent();
            $noteStudent->setUsers($user);
            $noteStudent->setQCMs($qcm);
            $noteStudent->setScore($scorePercentage);
            $entityManager->persist($noteStudent);
            $entityManager->flush();
        }
    }
    

    
    private function calculateStudentScore(array $qcmData, int $qcmId): float
    {
        // Récupérer les questions du même QCM
        $questions = $this->questionRepository->findBy(['qcm' => $qcmId]);
        //dump($questions);
        // Initialiser le nombre de réponses correctes
        $correctAnswersCount = 0;

        foreach ($questions as $question) {
            $questionId = $question->getId();
            // Vérifier si la question a été répondu par l'utilisateur
            if (isset($qcmData[$questionId])) {
                //dump($qcmData);
                //dump($qcmData[$questionId]);
                $userAnswerIndex = (int) $qcmData[$questionId];
                //dump($userAnswerIndex);
                // Récupérer l'index de la réponse correcte pour cette question
                $correctAnswerIndex = (int) $question->getCorrectAnswerIndex();
                // Comparer l'index de la réponse de l'utilisateur avec l'index de la réponse correcte
                if ($userAnswerIndex === $correctAnswerIndex) {
                    // Si la réponse est correcte, incrémenter le compteur des réponses correctes
                    $correctAnswersCount++;
                }
            }
        }

        // Calculer le score en pourcentage
        $totalQuestions = count($questions);
        //dump($correctAnswersCount);
        $scorePercentage = ($correctAnswersCount / $totalQuestions) * 100;

        return $scorePercentage;
    }

    // Méthode pour trouver les leçons non trouvées par l'étudiant dans le cours
    private function findLessonsWithIncorrectAnswers(array $qcmData): array
    {
        // Vérifier si les réponses de l'utilisateur sont présentes dans les données
        if (!isset($qcmData['user_answers'])) {
            throw new \InvalidArgumentException('Les réponses de l\'utilisateur sont manquantes dans les données.');
        }

        $userAnswers = $qcmData['user_answers'];
        $lessonNames = []; // Initialisez un tableau pour stocker les noms des leçons avec des réponses incorrectes

        // Parcourir chaque question pour vérifier si l'utilisateur a donné une réponse incorrecte
        foreach ($userAnswers as $questionId => $userAnswer) {
            // Récupérer la question correspondant à l'identifiant
            $question = $this->questionRepository->find($questionId);

            // Vérifier si la question existe
            if ($question) {
                // Récupérer l'indice de la réponse correcte pour cette question
                $correctAnswerIndex = $question->getCorrectAnswerIndex();

                // Vérifier si l'utilisateur a donné une réponse incorrecte
                if ((int) $userAnswer !== $correctAnswerIndex) {
                    // Récupérer la leçon associée à cette question
                    $lesson = $question->getLesson();

                    // Ajouter le nom de la leçon à la liste si elle n'est pas déjà présente
                    if ($lesson && !in_array($lesson->getName(), $lessonNames, true)) {
                        $lessonNames[] = $lesson->getName();
                        
                    }
                }
            }
        }
        //dump($lessonNames);
        return $lessonNames;
        
    }

        
    
    #[Route('/recommendations', name: 'recommendations')]
    public function recommendations(Request $request, int $idS, QCMRepository $QCMRepository, QuestionRepository $questionRepository, NoteStudentRepository $noteStudentRepository): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Récupérer les réponses du QCM depuis les cookies
        $qcmResults = json_decode($request->cookies->get('qcm_results'), true);
        //dump($qcmResults);
        
        // Vérifier si les données du QCM peuvent être décodées depuis le cookie
        if ($qcmResults === null || !isset($qcmResults['qcm_id'])) {
            throw new \InvalidArgumentException('Les données du QCM ne peuvent pas être décodées depuis le cookie ou l\'ID du QCM est manquant.');
        }
    
        // Récupérer l'ID du QCM à partir des données du cookie
        $qcmId = $qcmResults['qcm_id'];
    
        // Récupérer le QCM à partir de son ID
        $qcm = $QCMRepository->find($qcmId);
      
        // Vérifier si le QCM existe
        if (!$qcm) {
            throw $this->createNotFoundException('Le QCM correspondant n\'a pas été trouvé.');
        }
    
        // Récupérer les questions associées à ce QCM
        $questions = $questionRepository->findBy(['qcm' => $qcmId]);
        
        // Vérifier si des questions ont été trouvées
        if (!$questions) {
            throw $this->createNotFoundException('Aucune question n\'a été trouvée pour ce QCM.');
        }

        $userAnswers = $qcmResults['user_answers'];


        $studentScore = $this->calculateStudentScore($userAnswers, $qcmId);

        // Appel de la méthode recommendCourses avec les données complètes du QCM
        $recommendedCourses = $this->recommendCourses($qcmResults, $qcmId,  $idS, $noteStudentRepository);
        dump($recommendedCourses);
        
    
        // Déterminer les leçons non trouvées par l'étudiant dans le cours
        $missingLessons = $this->findLessonsWithIncorrectAnswers($qcmResults);
    
        $threshold = 70;
    
        // Si le score de l'étudiant est inférieur au seuil, lui recommander de refaire le cours
        if ($studentScore < $threshold) {
            $recommendation = 'Nous vous recommandons de refaire le cours en vous concentrant sur les leçons suivantes : ' . implode(', ', $missingLessons);
        } else {
            // Sinon, lui recommander d'explorer d'autres cours du même thème
            $recommendation = 'Votre score est suffisamment élevé. Vous pouvez explorer d\'autres cours du même thème.';
        }
    
        // Afficher les recommandations à l'utilisateur
        return $this->render('student_qcm/recommendations.html.twig', [
            'studentScore' =>$studentScore,
            'recommended_courses' => $recommendedCourses,
            'recommendation' => $recommendation,
            'idS' => $idS,
            'theme' =>$theme
        ]);
    }
        
        

    private function recommendCourses(array $qcmData, int $qcmId, int $idS, NoteStudentRepository $noteStudentRepository): array
    {
        // Vérifier si l'identifiant du QCM est présent dans les données
        if (!isset($qcmData['qcm_id'])) {
            throw new \InvalidArgumentException('L\'identifiant du QCM est manquant dans les données.');
        }
    
        // Récupérer l'identifiant du QCM à partir des données
        $qcmId = $qcmData['qcm_id'];
        
    
        // Récupérer le QCM associé à l'identifiant
        $qcm = $this->QCMRepository->find($qcmId);
       
        // Vérifier si le QCM existe
        if (!$qcm) {
            throw $this->createNotFoundException('Le QCM demandé n\'existe pas.');
        }
    
        // Récupérer le cours associé au QCM
        $course = $qcm->getCourse();
        
        // Récupérer l'objet Theme associé au cours
        $theme = $course->getTheme();
        
        // Récupérer l'identifiant du thème du cours
        $themeId = $theme->getId();
        dump($themeId);

        $studentScore = $noteStudentRepository->findScoreByUserAndQCM($idS, $qcmId);

        // Si l'étudiant a dépassé le seuil, recommandez des cours du même thème
        if ($studentScore >= $this->threshold) {
            // Recherchez d'autres cours similaires dans votre base de données
            // Dans la méthode recommendCourses de votre contrôleur
            $similarCourses = $this->courseRepository->findSimilarCoursesByTheme($themeId);
                
            return $similarCourses;
        }
    
        // Si l'étudiant n'a pas dépassé le seuil, retournez une liste vide
        return [];
    }
    
}
