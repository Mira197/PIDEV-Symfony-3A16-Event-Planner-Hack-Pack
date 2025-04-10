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

#[Route('/admin/promo-codes', name: 'aya_admin_code_promo_')]
class AyaCodePromoAdminController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CodePromoRepository $repo): Response
    {
        return $this->render('admin/aya_code_promo/aya_list_promo_codes.html.twig', [
            'codes' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // 🔐 Vérification des champs
        if (empty($data['code_promo']) || empty($data['pourcentage']) || empty($data['date_expiration'])) {
            return new JsonResponse(['success' => false, 'message' => 'All fields are required.']);
        }

        try {
            $promo = new CodePromo();
            $promo->setCodePromo($data['code_promo']);
            $promo->setPourcentage($data['pourcentage']);
            $promo->setDateExpiration($data['date_expiration'] ? new \DateTime($data['date_expiration']) : null);
            $promo->setDateCreation(new \DateTime()); // utile si pas encore défini ailleurs


            $em->persist($promo);
            $em->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    #[Route('/edit/{id}', name: 'edit', methods: ['POST'])]
    public function edit(Request $request, CodePromo $codePromo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['code_promo'], $data['pourcentage'], $data['date_expiration'])) {
            return new JsonResponse(['success' => false, 'message' => 'Missing fields']);
        }

        $codePromo->setCodePromo($data['code_promo']);
        $codePromo->setPourcentage($data['pourcentage']);
        $codePromo->setDateExpiration(new \DateTime($data['date_expiration']));

        $em->flush();

        return new JsonResponse(['success' => true]);
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
}