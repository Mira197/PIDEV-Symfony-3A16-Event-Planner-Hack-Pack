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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;





class MahdiProductController extends AbstractController
{
    #[Route('/product/add', name: 'product_add')]
    public function ajouter(Request $request, EntityManagerInterface $em ,SessionInterface $session,
    UserRepository $userRepository): Response
    {
        /*$user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un produit.');
            return $this->redirectToRoute('app_login');
        }*/
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
    
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('app_login');
        }
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
            return $this->redirectToRoute('app_product_list');
        }
        
            //$product->setStockId($stock_id);
            // Affecter user_id = 1
            
    
    $product->setUser($user);
            //$product->setUser($user);
        
            //$reference = strtoupper(preg_replace('/\s+/', '', $product->getName())) . '-' . $user->getId() . '-' . time();
            //$product->setReference($reference);
            //$product->setStockId($form->get('stock_id')->getData());
            $uploadedImage = $request->files->get('image_file');
                if ($uploadedImage && $uploadedImage->isValid()) {
                    $imageData = file_get_contents($uploadedImage->getPathname());
                    $product->setImage($imageData);
                }
            $em->persist($product);
            $em->flush();
        
            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('product_add');
            dump($form->getErrors(true));
        }
        


        return $this->render('admin/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/product/addprodfour', name: 'product_add_four')]
    public function ajouter1(Request $request, EntityManagerInterface $em ,SessionInterface $session,
    UserRepository $userRepository): Response
    {
        /*$user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter un produit.');
            return $this->redirectToRoute('app_login');
        }*/
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
    
        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('app_login');
        }
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
            return $this->redirectToRoute('app_product_list');
        }
        
            //$product->setStockId($stock_id);
            // Affecter user_id = 1
            
    
    $product->setUser($user);
            //$product->setUser($user);
        
            //$reference = strtoupper(preg_replace('/\s+/', '', $product->getName())) . '-' . $user->getId() . '-' . time();
            //$product->setReference($reference);
            //$product->setStockId($form->get('stock_id')->getData());
            $uploadedImage = $request->files->get('image_file');
                if ($uploadedImage && $uploadedImage->isValid()) {
                    $imageData = file_get_contents($uploadedImage->getPathname());
                    $product->setImage($imageData);
                }
            $em->persist($product);
            $em->flush();
        
            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('product_add_four');
            dump($form->getErrors(true));
        }
        


        return $this->render('admin/addFournisseurProd.html.twig', [
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
    #[Route('/admin/product/delete/{id}', name: 'product_delete')]
    public function delete(Request $request, Product $product, EntityManagerInterface $em,Security $security): Response
    {
        /* if ($product->getUser() !== $security->getUser()) {
        throw $this->createAccessDeniedException("You are not allowed to delete this product.");
    }*/
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé avec succès !'); 
    
        return $this->redirectToRoute('app_product_list');
    }
   
   
    
    

#[Route('/afficheclient', name: 'afficheclient')]
public function index(Request $request, ProductRepository $productRepository, PaginatorInterface $paginator): Response
{
    $products = $productRepository->findAll();
    $groupedProducts = [];

    foreach ($products as $product) {
        $category = $product->getCategory();
        if (!isset($groupedProducts[$category])) {
            $groupedProducts[$category] = [];
        }
        $groupedProducts[$category][] = $product;
    }

    // Nouvelle étape : paginer chaque catégorie
    $paginatedGroups = [];
    foreach ($groupedProducts as $category => $productsInCategory) {
        $paramName = 'page_' . md5($category); // ← On fait le md5 ici
        $pagination = $paginator->paginate(
            $productsInCategory,
            $request->query->getInt($paramName, 1),
            3
        );
        $paginatedGroups[] = [
            'category' => $category,
            'hash' => $paramName, // ← on envoie le nom du paramètre
            'pagination' => $pagination
        ];
    }

    return $this->render('afficheProduct.html.twig', [
        'groupedProducts' => $paginatedGroups
    ]);
}


    

#[Route('/products', name: 'product_list')]
public function listProducts(Request $request, ProductRepository $productRepo): Response
{
    $category = $request->query->get('category');

    if ($category) {
        $products = $productRepo->findBy(['category' => $category]);
    } else {
        $products = $productRepo->findAll();
    }

    // Grouper par catégorie
    $grouped = [];
    foreach ($products as $product) {
        $cat = $product->getCategory();
        $grouped[$cat][] = $product;
    }

    return $this->render('afficheProduct.html.twig', [
        'groupedProducts' => $grouped
    ]);
}
#[Route('/products/ajax', name: 'ajax_filter_products')]
public function ajaxFilterProducts(Request $request, ProductRepository $productRepository): Response
{
    $category = $request->query->get('category');

    if ($category) {
        $products = $productRepository->findBy(['category' => $category]);
    } else {
        $products = $productRepository->findAll();
    }

    // Group by category
    $groupedProducts = [];
    foreach ($products as $product) {
        $cat = $product->getCategory();
        $groupedProducts[$cat][] = $product;
    }

    return $this->render('partials/_products.html.twig', [
        'groupedProducts' => $groupedProducts
    ]);
}
#[Route('/admin/product', name: 'app_product_list')]
public function listProductsAdmin(ProductRepository $repo): Response
{
    $products = $repo->findAll();
    
    return $this->render('admin/affiche.html.twig', [
        'products' => $products,
    ]);


}
#[Route('/mes-produits', name: 'mes_produits')]
public function mesProduits(SessionInterface $session, ProductRepository $productRepository): Response
{
    $userId = $session->get('user_id'); // récupère l'id du user connecté via session

    if (!$userId) {
        return $this->redirectToRoute('login'); // ou toute autre logique de redirection
    }

    // Récupère tous les produits du user connecté
    $products = $productRepository->findBy(['user' => $userId]);

    return $this->render('admin/affichefournisseur.html.twig', [
        'products' => $products,
    ]);
}


}
