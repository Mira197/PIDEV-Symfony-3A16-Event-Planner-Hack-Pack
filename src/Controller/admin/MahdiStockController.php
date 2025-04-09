<?php

namespace App\Controller\admin;

use App\Entity\Product;
use App\Form\MahdiStockType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Stock;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\User;
use App\Repository\StockRepository;



class MahdiStockController extends AbstractController
{
    #[Route('/stock/add', name: 'stock_add')]
    public function ajouterstock(Request $request, EntityManagerInterface $em): Response
    {
        /*$user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un produit.');
            return $this->redirectToRoute('app_login');
        }*/

        $stock = new Stock();
        $form1 = $this->createForm(MahdiStockType::class, $stock);
        //$form->add('product_add',SubmitType::class);
        $form1->handleRequest($request);

        /*if ($form->isSubmitted() && $form->isValid()) {
            if ($product->getPrice() < 0) {
                $this->addFlash('error', 'Le prix du produit ne peut pas être négatif.');
            } else {
                // Génération de la référence unique ici directement
                //$reference = strtoupper(preg_replace('/\s+/', '', $product->getName())) . '-' . $user->getId() . '-' . time();
                //$product->setReference($reference);
                //$product->setUser($user);

                $em->persist($product);
                $em->flush();

                $this->addFlash('success', 'Produit ajouté avec succès !');
                return $this->redirectToRoute('product_add');
            }
        }*/
        
        if ($form1->isSubmitted() && $form1->isValid()) {
            
            // Affecter user_id = 1
    $user = $em->getRepository(User::class)->find(1);
    if (!$user) {
        $this->addFlash('error', 'Utilisateur avec l\'ID 1 non trouvé.');
        return $this->redirectToRoute('product_add');
    }
    $stock->setUser($user);
            //$product->setUser($user);
        
            //$reference = strtoupper(preg_replace('/\s+/', '', $product->getName())) . '-' . $user->getId() . '-' . time();
            //$product->setReference($reference);
        
            $em->persist($stock);
            $em->flush();
        
            $this->addFlash('success', 'Stock ajouté avec succès !');
            return $this->redirectToRoute('stock_add');
        }
        


        return $this->render('admin/addstock.html.twig', [
            'form' => $form1->createView(),
        ]);
    }
    #[Route('/affichestock', name: 'affichestock')]
    public function affiche(StockRepository $stockRepository): Response
    {
        $stocks = $stockRepository->findAll();



        return $this->render('admin/affichestock.html.twig', [
            'stocks' => $stocks,
        ]);
    }
    #[Route('/admin/stock/edit/{id}', name: 'stock_edit')]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $em): Response
    {
        $form1 = $this->createForm(MahdiStockType::class, $stock);

        $form1->handleRequest($request);
        if ($form1->isSubmitted() && $form1->isValid()) {
            $em->flush();
            return $this->redirectToRoute('affichestock');
        }

        return $this->render('admin/addstock.html.twig', [
            'form' => $form1->createView(),
            'editMode' => true,
        ]);
    }
    #[Route('/admin/stock/delete/{id}', name: 'stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $stock->getStock_id(), $request->request->get('_token'))) {
            $em->remove($stock);
            $em->flush();
        }

        return $this->redirectToRoute('affichestock');
    }







}