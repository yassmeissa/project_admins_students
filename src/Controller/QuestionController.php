<?php

namespace App\Controller;

use App\Entity\QCM;
use App\Entity\Question;
use App\Form\QuestionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class QuestionController extends AbstractController
{

    private $doctrine;

    
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

    }


    #[Route('/admin/{idA}/course/{idC}/qcm/{idQ}/question/{id}', name: 'show_question', methods: ['GET'])]
    public function show(Question $question, int $idA, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        $qcm = $question->getQcm();
        $course = $qcm->getCourse(); 
        // Assuming you have a method to get the related QCM
        return $this->render('question/show.html.twig', [
            'theme' =>$theme,
            'question' => $question,
            'admin_id' => $idA,
            'course' =>$course,
            'qcm' => $qcm // Passing the QCM variable to the Twig template
        ]);
    }

    #[Route('/admin/{idA}/course/{idC}/qcm/{idQ}/question/{id}/edit', name: 'edit_question', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager, int $idA, int $idC, int $idQ): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        $qcm = $question->getQcm();
        $course = $qcm->getCourse();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
    
            // Actualiser le fichier XML du QCM
            $this->saveQCMXmlToFile($qcm);
    
            // Récupérez l'ID de la question
            $questionId = $question->getId();
    
            return $this->redirectToRoute('show_question', [
                'idA' => $idA,
                'idC' => $idC,
                'idQ' => $idQ,
                'id' => $questionId // Utilisez l'ID de la question ici
            ], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('question/edit.html.twig', [
            'theme' =>$theme,
            'question' => $question,
            'idC' =>$idC,
            'form' => $form,
            'admin_id' => $idA,
            'qcm' => $qcm
        ]);
    }
    


    #[Route('/admin/{idA}/course/{idC}/qcm/{idQ}/question/{id}', name: 'delete_question', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $entityManager, int $idA, int $idC): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->request->get('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
    
            // Récupérer le QCM associé à la question supprimée
            $qcm = $question->getQcm();
            
            // Actualiser le fichier XML du QCM
            $this->saveQCMXmlToFile($qcm);
    
            // Récupérer l'ID du QCM
            $qcmId = $qcm->getId();
                
            return $this->redirectToRoute('qcm_show', ['idA' => $idA, 'idC' => $idC, 'id' => $qcmId], Response::HTTP_SEE_OTHER);
        }
    }

    #[Route('/admin/{idA}/course/{idC}/qcm/{id}/add-question', name: 'add_question')]
    public function addQuestion(Request $request, int $idA, int $idC, int $id): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le QCM correspondant à l'ID
            $qcm = $this->doctrine->getRepository(QCM::class)->find($id);
    
            // Vérifier si le QCM existe
            if (!$qcm) {
                throw $this->createNotFoundException('QCM non trouvé avec l\'ID ' . $id);
            }
            // Associer la question au QCM
            $question->setQCM($qcm);
    
            // Enregistrer la question dans la base de données
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($question);
            $entityManager->flush();
    
            // Appeler la méthode pour enregistrer les questions dans le fichier XML
            $this->saveQCMXmlToFile($qcm);
    
            // Rediriger vers la page du QCM
            return $this->redirectToRoute('qcm_show', ['idA' => $idA, 'idC' => $idC, 'id' => $id]);
        }
    
        // S'il y a une erreur de soumission ou si le formulaire n'est pas valide, rend le formulaire avec les données
        return $this->render('question/add_question.html.twig', [
            'theme' =>$theme,
            'form' => $form->createView(),
            'admin_id' => $idA,
            'course_id' => $idC,
            'qcm_id' =>$id,
        ]);
    }
    
    



    private function saveQCMXmlToFile(QCM $qcm): void
    {
        // Récupérer les données du QCM
        $qcmData = [
            'title' => $qcm->getTitle(), // Titre du QCM
            'course' => $qcm->getCourse()->getName(), // Nom du cours associé au QCM
            'questions' => [],
        ];
        
        // Récupérer les questions du QCM
        $questions = $qcm->getQuestions();
        
        // Boucle sur chaque question pour récupérer ses données
        foreach ($questions as $question) {
            $questionData = [
                'lesson' => $question->getLesson()->getName(), // Nom de la leçon associée à la question
                'content' => $question->getContent(),
                'answers' => $question->getAnswers(),
                'correctAnswerIndex' => $question->getCorrectAnswerIndex(),
                // Ajoutez d'autres données de question si nécessaire
            ];
            $qcmData['questions'][] = $questionData;
        }
    
        // Convertir les données en format XML
        $xmlEncoder = new XmlEncoder();
        $xmlContent = $xmlEncoder->encode($qcmData, 'xml');
        
        // Enregistrement dans un fichier XML
        $qcmId = $qcm->getId(); // ID du QCM
       // Spécifier le chemin complet où vous souhaitez enregistrer le fichier XML
       $filePath = __DIR__ . '/../../src/Command/qcms/qcm_' . $qcm->getId() . '.xml';

    // Vérifier si le dossier qcms existe, sinon le créer de manière récursive
    $directoryPath = dirname($filePath);
    if (!is_dir($directoryPath)) {
        mkdir($directoryPath, 0777, true);
    }
        // Enregistrer le contenu XML dans le fichier
        file_put_contents($filePath, $xmlContent);
    }
    
    
}