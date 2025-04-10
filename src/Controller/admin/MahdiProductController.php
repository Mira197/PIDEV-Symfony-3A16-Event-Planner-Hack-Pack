<?php

namespace App\Controller\admin;

use App\Entity\Product;
use App\Form\MahdiProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Stock;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\User;
use App\Repository\ProductRepository;



class MahdiProductController extends AbstractController
{
    #[Route('/product/add', name: 'product_add')]
    public function ajouter(Request $request, EntityManagerInterface $em): Response
    {
        /*$user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un produit.');
            return $this->redirectToRoute('app_login');
        }*/

        $product = new Product();
        $form = $this->createForm(MahdiProductType::class, $product);
        //$form->add('product_add',SubmitType::class);
        $form->handleRequest($request);

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
        
        if ($form->isSubmitted() && $form->isValid()) {
            /*$stock_id = $form->get('stock_id')->getData();
            $stock = $em->getRepository(Stock::class)->find($stock_id);
        
            if (!$stock) {
                $this->addFlash('error', 'Stock introuvable pour l’ID fourni.');
                return $this->redirectToRoute('product_add');
            }*/
            $stock = $product->getStock();
        if (!$stock) {
            $this->addFlash('error', 'Veuillez sélectionner un stock.');
            return $this->redirectToRoute('product_add');
        }
        
            //$product->setStockId($stock_id);
            // Affecter user_id = 1
    $user = $em->getRepository(User::class)->find(44);
    if (!$user) {
        $this->addFlash('error', 'Utilisateur avec l\'ID 1 non trouvé.');
        return $this->redirectToRoute('product_add');
    }
    $product->setUser($user);
            //$product->setUser($user);
        
            //$reference = strtoupper(preg_replace('/\s+/', '', $product->getName())) . '-' . $user->getId() . '-' . time();
            //$product->setReference($reference);
            //$product->setStockId($form->get('stock_id')->getData());
            $em->persist($product);
            $em->flush();
        
            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('product_add');
        }
        


        return $this->render('admin/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/affiche', name: 'affiche')]
    public function affiche(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();



        return $this->render('admin/affiche.html.twig', [
            'products' => $products,
        ]);
    }
    #[Route('/admin/product/edit/{id}', name: 'product_edit')]
    public function edit(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MahdiProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('affiche');
        }

        return $this->render('admin/add.html.twig', [
            'form' => $form->createView(),
            'editMode' => true,
        ]);
    }
    #[Route('/admin/product/delete/{id}', name: 'product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getProduct_id(), $request->request->get('_token'))) {
            $em->remove($product);
            $em->flush();
        }

        return $this->redirectToRoute('affiche');
    }
    #[Route('/afficheclient', name: 'afficheclient')]
    public function afficheclient(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();



        return $this->render('afficheProduct.html.twig', [
            'products' => $products,
        ]);
    }


}
