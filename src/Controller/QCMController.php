<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\QCM;
use App\Form\QCMType;
use App\Repository\QCMRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class QCMController extends AbstractController

{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

    }
    
        
    
  
    #[Route('/admin/{idA}/qcmList', name: 'qcm_index')]
    public function index(int $idA, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        // Récupérer le EntityManager
        $entityManager = $this->doctrine->getManager();
        
        // Récupérer tous les QCM depuis la base de données
        $qcms = $entityManager->getRepository(QCM::class)->findAll();
        
        // Récupérer les détails du cours correspondant
        $courses = $entityManager->getRepository(Course::class)->findAll();
        
        // Afficher les QCM dans un template Twig avec les détails du cours correspondant
        return $this->render('qcm/index.html.twig', [
            'qcms' => $qcms,
            'courses' => $courses,
            'admin_id' => $idA,
            'theme' =>$theme
            // Ajoutez la variable 'course_id'
        ]);
    }
    
    


    #[Route('admin/{idA}/qcm/create', name: 'qcm_create')]
    public function createQCM(Request $request, SerializerInterface $serializer, int $idA ): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        $qcm = new QCM();
        // Créez le formulaire à partir du type de formulaire QCMType
        $form = $this->createForm(QCMType::class, $qcm);
        // Traitez la soumission du formulaire
        $form->handleRequest($request);
        $courseId = null;
        if ($qcm->getCourse() !== null) {
            $courseId = $qcm->getCourse()->getId();
        }
        if ($form->isSubmitted() && $form->isValid()) {
            // Exclure les propriétés cours de l'objet QCM pour éviter la référence circulaire
            $context = [
                'ignored_attributes' => ['course']
            ];
    
            // Utilisez le Serializer pour transformer l'objet QCM en XML
            $xmlContent = $serializer->serialize($qcm, 'xml', $context);
    
            // Enregistrez le QCM dans la base de données si nécessaire
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($qcm);
            $entityManager->flush();
    
            // Enregistrez le contenu XML dans un fichier
            $this->saveQCMXmlToFile($qcm, $xmlContent);
    
            // Redirigez l'utilisateur vers une page de confirmation ou affichez un message de succès
            return $this->redirectToRoute('qcm_index' , ['idA' => $idA]);
        }
    
        return $this->render('qcm/qcm_create.html.twig', [
            'form' => $form->createView(),
            'admin_id' => $idA,
            'course_id'=>$courseId, 
            'theme'=>$theme
        ]);
    }
    

    #[Route('admin/{idA}/course/{idC}/qcm/{id}', name: 'qcm_show', methods: ['GET'])]
    public function show(int $idA, int $idC, int $id, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        $qcm = $this->doctrine->getRepository(QCM::class)->find($id);
        
        if (!$qcm) {
            throw $this->createNotFoundException('QCM not found');
        }
        
        return $this->render('qcm/show.html.twig', [
            'qcm' => $qcm,
            'idC' =>$idC,
            'admin_id' => $idA,
            'theme'=> $theme
        ]);
    }
    
    #[Route('admin/{idA}/course/{idC}/qcm/{id}/edit', name: 'qcm_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $idA,int $idC, int $id): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        // Récupérer le QCM à modifier depuis la base de données
        $qcm = $this->doctrine->getRepository(QCM::class)->find($id);
        
        if (!$qcm) {
            throw $this->createNotFoundException('QCM not found');
        }
        
        // Créer le formulaire à partir du type de formulaire QCMType
        $form = $this->createForm(QCMType::class, $qcm);
        // Traiter la soumission du formulaire
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer les modifications dans la base de données
            $entityManager = $this->doctrine->getManager();
            $entityManager->flush();
            
            // Rediriger l'utilisateur vers la page de détails du QCM
            return $this->redirectToRoute('qcm_show', ['idA' => $idA, 'idC' => $idC, 'id' => $id]);
        }
        
        // Afficher le formulaire d'édition
        return $this->render('qcm/edit.html.twig', [
            'form' => $form->createView(),
            'qcm' => $qcm,
            'course_id' => $idC,
            'admin_id' => $idA,
            'theme' => $theme
        ]);
    }



    #[Route('admin/{idA}/qcm/{id}', name: 'qcm_delete', methods: ['DELETE'])]
    public function delete(Request $request, int $idA, int $id): Response
    {
        // Récupérer le QCM à supprimer depuis la base de données
        $entityManager = $this->doctrine->getManager();
        $qcm = $entityManager->getRepository(QCM::class)->find($id);
        
        if (!$qcm) {
            throw $this->createNotFoundException('QCM not found');
        }
        
        // Vérifier si le token CSRF est valide
        if ($this->isCsrfTokenValid('delete'.$qcm->getId(), $request->request->get('_token'))) {
            // Supprimer le QCM de la base de données
            $entityManager->remove($qcm);
            $entityManager->flush();
        }
        
        // Rediriger l'utilisateur vers la liste des QCMs
        return $this->redirectToRoute('qcm_index', ['idA' => $idA]);
    }
    

    #[Route('/admin/{idA}/qcm/search', name: 'search_qcm', methods: ['GET'])]
    public function search(Request $request, int $idA, QCMRepository $QCMRepository): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        // Récupérer le terme de recherche depuis la requête
        $searchTerm = $request->query->get('query');
    
        // Recherchez les QCM dans la base de données en fonction du terme de recherche
        $qcms = $QCMRepository->searchByTitle($searchTerm);

        $courseIds = [];
        foreach ($qcms as $qcm) {
            // Vérifiez si le QCM a un cours associé
            $course = $qcm->getCourse();
            if ($course !== null) {
                // Récupérer l'ID du cours et l'ajouter à la liste
                $courseIds[$qcm->getId()] = $course->getId();
            }
        }
    
        // Renvoyer les résultats de la recherche dans un template Twig
        return $this->render('qcm/search.html.twig', [
            'qcms' => $qcms,
            'admin_id' => $idA,
            'theme' =>$theme,
            'search_term' => $searchTerm,
            'course_ids' => $courseIds,

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