<?php

namespace App\Controller\admin;

use App\Entity\CodePromo;
use App\Form\AyaCodePromoType; // Ensure this class exists in the App\Form namespace
use App\Repository\CodePromoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twilio\Rest\Client;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use App\Service\SmsSender;
use Knp\Component\Pager\PaginatorInterface; 
#[Route('/admin/promo-codes', name: 'aya_admin_code_promo_')]
class AyaCodePromoAdminController extends AbstractController
{
    
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, CodePromoRepository $repo, PaginatorInterface $paginator): Response
    {
        $query = $repo->createQueryBuilder('p')
            ->orderBy('p.date_expiration', 'ASC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // numéro de page
            5 // nombre d'éléments par page
        );

        return $this->render('admin/aya_code_promo/aya_list_promo_codes.html.twig', [
            'codes' => $pagination,
        ]);
    }




    // #[Route('/new', name: 'new', methods: ['POST'])]
    // public function new(Request $request, EntityManagerInterface $em, TexterInterface $texter): Response
    // {
    //     $promo = new CodePromo();
    //     $form = $this->createForm(AyaCodePromoType::class, $promo);

    //     if (str_contains($request->headers->get('Content-Type'), 'application/json')) {
    //         $data = json_decode($request->getContent(), true);
    //         $form->submit($data);
    //     } else {
    //         $form->handleRequest($request);
    //     }

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $promo->setDateCreation(new \DateTime());
    //         $em->persist($promo);
    //         $em->flush();

    //         // ✅ Construction du message
    //         $messageText = sprintf(
    //             "🎁 Nouveau Code Promo !\n🔖 Code : %s\n💸 Réduction : %d%%\n📅 Expire le : %s",
    //             $promo->getCodePromo(),
    //             $promo->getPourcentage(),
    //             $promo->getDateExpiration()?->format('Y-m-d') ?? 'non définie'
    //         );

    //         // ✅ Envoi SMS avec Notifier
    //         $sms = new SmsMessage($_ENV['TWILIO_TO_NUMBER'], $messageText);
    //         $texter->send($sms);

    //         if ($request->isXmlHttpRequest() || $request->headers->get('Content-Type') === 'application/json') {
    //             return new JsonResponse([
    //                 'success' => true,
    //                 'message' => '✅ Promo code added successfully.',
    //                 'id' => $promo->getId(),
    //             ]);
    //         }

    //         $this->addFlash('success', '✅ Promo code added successfully.');
    //         return $this->redirectToRoute('aya_admin_code_promo_index');
    //     }

    //     // ❌ Gestion des erreurs
    //     if ($request->isXmlHttpRequest() || $request->headers->get('Content-Type') === 'application/json') {
    //         $errors = [];
    //         foreach ($form->getErrors(true) as $error) {
    //             $errors[] = $error->getMessage();
    //         }

    //         return new JsonResponse([
    //             'success' => false,
    //             'message' => '❌ Failed to add promo code.',
    //             'errors' => $errors,
    //         ]);
    //     }

    //     // 🔁 Rechargement page (si formulaire classique)
    //     $codes = $em->getRepository(CodePromo::class)->findAll();
    //     return $this->render('admin/aya_code_promo/aya_list_promo_codes.html.twig', [
    //         'form' => $form->createView(),
    //         'codes' => $codes,
    //     ]);
    // }

    #[Route('/add', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $promo = new CodePromo();
        $form = $this->createForm(AyaCodePromoType::class, $promo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $promo->setDateCreation(new \DateTime());
            $em->persist($promo);
            $em->flush();

            $this->addFlash('success', '✅ Promo code added successfully.');
            return $this->redirectToRoute('aya_admin_code_promo_index');
        }

        return $this->render('admin/aya_code_promo/aya_add_promo.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    



    #[Route('/edit/{id}', name: 'edit', methods: ['POST'])]
    public function edit(
        Request $request,
        CodePromo $codePromo,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(AyaCodePromoType::class, $codePromo);
            $form->submit($data);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->flush();
                return new JsonResponse(['success' => true]);
            }

            $errors = [];
            foreach ($form->getErrors(true, true) as $error) {
                $field = $error->getOrigin()->getName();

                $mappedField = match ($field) {
                    'code_promo' => 'codePromo',
                    'date_expiration' => 'dateExpiration',
                    'pourcentage'  => 'pourcentage',
                    default => $field
                };

                $errors[$mappedField][] = $error->getMessage();
            }

            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/edit-form/{id}', name: 'edit_form', methods: ['GET', 'POST'])]
    public function editForm(CodePromo $codePromo, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AyaCodePromoType::class, $codePromo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', '✅ Promo code updated successfully.');
            return $this->redirectToRoute('aya_admin_code_promo_index');
        }

        return $this->render('admin/aya_code_promo/aya_edit_promo.html.twig', [
            'form' => $form->createView()
        ]);
    }






    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, CodePromo $codePromo, EntityManagerInterface $em): Response

    {
        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $codePromo->getId(), $submittedToken)) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
            }

            $this->addFlash('danger', 'Invalid CSRF token');
            return $this->redirectToRoute('aya_admin_code_promo_index');
        }

        $em->remove($codePromo);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlash('success', 'Promo code deleted.');
        return $this->redirectToRoute('aya_admin_code_promo_index');
    }
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = strtolower($request->query->get('q', ''));

        // On récupère tous les codes promo si la recherche est vide
        $codes = $em->getRepository(CodePromo::class)->findAll();

        // On filtre manuellement en PHP (plus souple pour les formats comme date ou status)
        $results = [];

        foreach ($codes as $code) {
            $expirationDate = $code->getDateExpiration()?->format('Y-m-d');
            $status = $expirationDate && $expirationDate < (new \DateTime())->format('Y-m-d') ? 'expired' : 'active';

            // Convertir les champs à comparer en chaînes simples
            $fields = [
                strtolower($code->getCodePromo()),
                (string)$code->getPourcentage(),
                strtolower($expirationDate),
                strtolower($status),
            ];

            // Inclure si une des colonnes contient le mot-clé
            if ($query === '' || array_filter($fields, fn($val) => str_contains($val, $query))) {
                $results[] = [
                    'id' => $code->getId(),
                    'codePromo' => $code->getCodePromo(),
                    'pourcentage' => $code->getPourcentage(),
                    'dateExpiration' => $expirationDate,
                    'status' => $status,
                ];
            }
        }

        return new JsonResponse($results);
    }
}
