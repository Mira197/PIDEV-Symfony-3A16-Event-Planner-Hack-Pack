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


    #[Route('/edit/{id}', name: 'edit')]
    public function edit(CodePromo $codePromo, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AyaCodePromoType::class, $codePromo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('aya_admin_code_promo_index');
        }

        return $this->render('admin/aya_code_promo/edit.html.twig', [
            'form' => $form->createView(),
            'code' => $codePromo,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(CodePromo $codePromo, EntityManagerInterface $em): Response
    {
        $em->remove($codePromo);
        $em->flush();
        return $this->redirectToRoute('aya_admin_code_promo_index');
    }
}
