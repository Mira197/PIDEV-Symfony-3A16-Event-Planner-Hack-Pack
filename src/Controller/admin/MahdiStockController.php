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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport; 
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\HttpFoundation\JsonResponse;



class MahdiStockController extends AbstractController
{
    #[Route('/stock/add', name: 'stock_add')]
    public function ajouterstock(Request $request, EntityManagerInterface $em ,SessionInterface $session,
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
            return $this->redirectToRoute('app_product_list');
        }
        


        return $this->render('admin/addstock.html.twig', [
            'form' => $form1->createView(),
        ]);
    }
    #[Route('/stock/addstockfour', name: 'stock_add_four')]
    public function ajouterstock1(Request $request, EntityManagerInterface $em ,SessionInterface $session,
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
            return $this->redirectToRoute('app_product_list');
        }
        


        return $this->render('admin/addstockfour.html.twig', [
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
            return $this->redirectToRoute('app_product_list');
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
    #[Route('/admin/stock', name: 'app_stock_list')]
public function listStocks(StockRepository $repo): Response
{
    $stocks = $repo->findAll();
    
    return $this->render('admin/affichestock.html.twig', [
        'stocks' => $stocks,
    ]);
}
#[Route('/mes-stocks', name: 'mes_stocks')]
public function mesStocks(SessionInterface $session, StockRepository $stockRepository): Response
{
    $userId = $session->get('user_id');

    if (!$userId) {
        return $this->redirectToRoute('login');
    }

    $stocks = $stockRepository->findBy(['user' => $userId]);

    return $this->render('admin/affichestockfournisseur.html.twig', [
        'stocks' => $stocks,
    ]);
}
private $mailer;

public function __construct(MailerInterface $mailer)
{
    $this->mailer = $mailer;
}


#[Route('/checkStocks', name: 'check_stocks')]
public function checkStocks(StockRepository $stockRepository)
{
    // Recherche tous les stocks dans la base de données
    
    $stocks = $stockRepository->findAll();

    // Vérifier chaque stock
    foreach ($stocks as $stock) {
        if ($stock->getAvailable_quantity() < $stock->getMinimum_quantity()) {
            // Si le stock est inférieur au minimum, envoyer un email au fournisseur
            $this->sendLowStockNotification(
                $stock->getStock_id(),
                
                $stock->getAvailable_quantity(),
                $stock->getMinimum_quantity(),
                $stock->getUser()->getEmail()
            );
        }
    }

    // Rediriger l'utilisateur avec un message de confirmation
    $this->addFlash('success', 'Les stocks ont été vérifiés et les fournisseurs ont été notifiés si nécessaire.');

    return $this->redirectToRoute('affichestock');  // Rediriger vers la page des stocks
}

public function sendLowStockNotification($stockId,  $availableQuantity, $minQuantity, $supplierEmail)
{
    // Créer un email pour notifier le fournisseur
    
    $email = (new Email())
        ->from('mahdi.elgharbi2@gmail.com')
        ->to($supplierEmail)
        ->subject('Alerte : Stock insuffisant')
        ->text("Le stock du produit  avec ID {$stockId} est inférieur au minimum. Quantité actuelle: {$availableQuantity}.")
        ->html("<p>Le stock du produit  avec ID {$stockId} est inférieur au minimum. Quantité actuelle: {$availableQuantity}.</p>");

    // Envoi de l'email
    $this->mailer->send($email);
}



#[Route('/checkStocksNotif', name: 'check_stocks_notif')]
public function afficheFournisseur(
    SessionInterface $session,
    UserRepository $userRepository,
    StockRepository $stockRepository,
    NotifierInterface $notifier
): Response {
    $userId = $session->get('user_id');
    $user = $userRepository->find($userId);
    $stocks = $stockRepository->findBy(['user' => $user]);

    $notifications = [];

    foreach ($stocks as $stock) {
        if ($stock->getAvailable_quantity() < $stock->getMinimum_quantity()) {
            $notifications[] = [
                'id' => $stock->getStock_id(),
                'available' => $stock->getAvailable_quantity(),
                'minimum' => $stock->getMinimum_quantity()
            ];

            // Envoie une vraie notification si tu veux (utile si on veut logs email/sms/browser)
            $notifier->send((new Notification('Stock bas', ['browser']))
                ->content("Produit {$stock->getStock_id()} faible: {$stock->getAvailable_quantity()}/{$stock->getMinimum_quantity()}"));
        }
    }

    return $this->render('/admin/baseFournisseur.html.twig', [
        'notifications' => $notifications
    ]);
}






}
        
     






