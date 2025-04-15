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

#[Route('/admin/promo-codes', name: 'aya_admin_code_promo_')]
class AyaCodePromoAdminController extends AbstractController
{

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CodePromoRepository $repo): Response
    {
        $form = $this->createForm(AyaCodePromoType::class);

        return $this->render('admin/aya_code_promo/aya_list_promo_codes.html.twig', [
            'codes' => $repo->findAll(),
            'form' => $form->createView(), // 👈 Ajouté ici !
        ]);
    }



    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $promo = new CodePromo();
        $form = $this->createForm(AyaCodePromoType::class, $promo);

        if (str_contains($request->headers->get('Content-Type'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $form->submit($data);
        } else {
            $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Date de création seulement si le formulaire est valide
            $promo->setDateCreation(new \DateTime());
            $em->persist($promo);
            $em->flush();

            if ($request->isXmlHttpRequest() || $request->headers->get('Content-Type') === 'application/json') {
                return new JsonResponse([
                    'success' => true,
                    'message' => '✅ Promo code added successfully.',
                    'id' => $promo->getId(),
                ]);
            }

            $this->addFlash('success', '✅ Promo code added successfully.');
            return $this->redirectToRoute('aya_admin_code_promo_index');
        }

        // ❌ Si non valide, renvoyer les erreurs côté JSON ou vue classique
        if ($request->isXmlHttpRequest() || $request->headers->get('Content-Type') === 'application/json') {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse([
                'success' => false,
                'message' => '❌ Failed to add promo code.',
                'errors' => $errors,
            ]);
        }

        // 🔁 Rechargement avec erreurs affichées dans le modal
        $codes = $em->getRepository(CodePromo::class)->findAll();
        return $this->render('admin/aya_code_promo/aya_list_promo_codes.html.twig', [
            'form' => $form->createView(),
            'codes' => $codes,
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
