<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\LessonType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Symfony\Component\Routing\Attribute\Route;



#[Route('admin/{idA}/course/{idC}/lesson')]
class LessonController extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }


 
    #[Route('/new', name: 'new_lesson', methods: ['GET', 'POST'])]
    public function new(Request $request , int $idA, int $idC): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        $course = $this->doctrine->getRepository(Course::class)->find($idC);
        

        $lesson = new Lesson();
        $lesson->setCourse($course);
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données soumises par le formulaire
            $lesson = $form->getData();
            
            // Manipulation spécifique liée à l'upload de fichier
            $fileObject = $form->get('file')->getData();

            if ($fileObject instanceof UploadedFile) {
                // Définir le répertoire de destination pour le téléchargement des fichiers
                $destinationDirectory = $this->getParameter('lesson_files_directory');

                // Déplacer le fichier vers le répertoire de destination
                $fileName = md5(uniqid()) . '.' . $fileObject->guessExtension();
                $fileObject->move($destinationDirectory, $fileName);

                // Stocker le chemin du fichier dans l'entité Lesson
                $lesson->setFile($fileName);
                $lesson->setOriginalFileName($fileObject->getClientOriginalName());
            }

            // Persister l'entité
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($lesson);
            $entityManager->flush();
            return $this->redirectToRoute('show_course', ['idA' => $idA, 'id' => $idC]);

        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView(),
            'course' =>$course,
            'admin_id' => $idA,
            'course_id' => $idC,
            'theme' => $theme,
        ]);
    }

  
    #[Route('/{id}/download', name: 'download_lesson')]
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
    
    
 
    #[Route('/{id}/edit', name: 'edit_lesson', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson, EntityManagerInterface $entityManager, int $idA, int $idC): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données soumises par le formulaire
            $lesson = $form->getData();
            
            // Manipulation spécifique liée à l'upload de fichier
            $fileObject = $form->get('file')->getData();
    
            if ($fileObject instanceof UploadedFile) {
                // Définir le répertoire de destination pour le téléchargement des fichiers
                $destinationDirectory = $this->getParameter('lesson_files_directory');
    
                // Déplacer le fichier vers le répertoire de destination
                $fileName = md5(uniqid()) . '.' . $fileObject->guessExtension();
                $fileObject->move($destinationDirectory, $fileName);
    
                // Mettre à jour le chemin du fichier dans l'entité Lesson
                $lesson->setFile($fileName);
                $lesson->setOriginalFileName($fileObject->getClientOriginalName());
            }
    
            // Persister l'entité modifiée
            $entityManager->flush();
    
            return $this->redirectToRoute('show_course', ['idA' => $idA, 'id' => $idC]);
        }
    
        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form->createView(),
            'admin_id' => $idA,
            'course_id' => $idC,
            'theme' => $theme,
        ]);
    }
    
    

    #[Route('/{id}', name: 'delete_lesson', methods: ['POST'])]
    public function delete(Request $request, Lesson $lesson, EntityManagerInterface $entityManager, int $idA, int $idC): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->request->get('_token'))) {
            $entityManager->remove($lesson);
            $entityManager->flush();
        }

        return $this->redirectToRoute('show_course', ['idA' => $idA, 'id' => $idC]);
    }

    
    #[Route('/{id}', name: 'show_lesson', methods: ['GET'])]
    public function show(Lesson $lesson, int $idC, int $idA, Request $request ): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'admin_id' => $idA,
            'course_id' => $idC,
            'theme' => $theme,
        ]);
    }
    

}
