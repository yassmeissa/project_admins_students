<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Repository\CourseRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/student/{idS}')]
class PopUpController extends AbstractController
{
    private $doctrine;
    private $courseRepository;

    public function __construct(ManagerRegistry $doctrine, CourseRepository $courseRepository)
    {
        $this->doctrine = $doctrine;
        $this->courseRepository = $courseRepository;
    }

  

    #[Route('/popup', name: 'show_popup')]
    public function showPopup(int $idS, Request $request): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Vérifier si le popup a déjà été affiché via un cookie
        if (!$request->cookies->has('popup_shown')) {
            // Récupérer les thèmes depuis la base de données
            $themes = $this->doctrine->getRepository(Theme::class)->findAll();

            // Créer une réponse avec le pop-up
            $response = $this->render('popup/index.html.twig', [
                'student_id' => $idS,
                'theme' => $theme,
                'themes' => $themes,
            ]);

            // Ajouter un cookie pour marquer le pop-up comme affiché
            $response->headers->setCookie(new Cookie(
                'popup_shown',
                'true',
                time() + (365 * 24 * 60 * 60), // 1 year
                '/',
                null,
                false,
                false
            ));

            return $response;
        }

        // Si le pop-up a déjà été affiché, rediriger vers la page d'accueil de l'étudiant
        return $this->redirectToRoute('student_courses', ['idS' => $idS]);
    }

    #[Route('/process/popup/submission', name: 'process_popup_submission', methods: ['POST'])]
    public function processPopupSubmission(Request $request, int $idS): Response
    {
        $theme = $request->cookies->has("theme") && $request->cookies->get("theme") === "dark" ? "dark" : "light";

        // Récupérer les données soumises par l'utilisateur à partir du formulaire pop-up
        $formData = $request->request->all();

        // Récupérer l'utilisateur actuellement connecté (vous devrez peut-être ajuster cette logique selon votre application)
        $user = $this->getUser();

        // Vérifier si des données ont été soumises
        if (!empty($formData['themes'])) {
            // Récupérer les thèmes sélectionnés par l'utilisateur
            $selectedThemes = $formData['themes'];

            // Récupérer les cours correspondant aux thèmes sélectionnés
            $courses = $this->courseRepository->findByThemes($selectedThemes);

            // Ajouter l'utilisateur à la liste des utilisateurs inscrits à chaque cours
            foreach ($courses as $course) {
                $course->addUser($user);
                // Vous pouvez également ajouter d'autres logiques ici, comme la vérification si l'utilisateur est déjà inscrit au cours
            }

            // Enregistrer les changements dans la base de données
            $this->doctrine->getManager()->flush();

            // Rediriger vers une page affichant les cours auxquels l'utilisateur est maintenant inscrit
            return $this->redirectToRoute('student_courses', ['idS' => $idS, 'theme' =>$theme]);
        } else {

            // Dans cet exemple, nous renvoyons une réponse JSON avec un message d'erreur
            return new JsonResponse(['error' => 'Aucun thème ou cours n\'a été sélectionné.']);
        }
    }
}
