<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Endroid\QrCode\Builder\BuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class AyaQRCodeController extends AbstractController
{
    private $builder;
    private $orderRepository;  

    public function __construct(BuilderInterface $builder, OrderRepository $orderRepository)
    {
        $this->builder = $builder;
        $this->orderRepository = $orderRepository;
    }

    // ✅ Route pour générer le fichier texte
    #[Route('/aya/qr/download/{id}', name: 'aya_order_qr_download')]
    public function downloadTxt(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $filesystem = new Filesystem();
        $filePath = $this->getParameter('kernel.project_dir') . '/public/qr/aya_order_'.$id.'.txt';

        $content = "🎉 3ala-Kifi - Order Confirmation 🎉\n\n";
        $content .= "Event Date: " . ($order->getEventDate()?->format('Y-m-d H:i') ?? 'N/A') . "\n";
        $content .= "Address: " . ($order->getExactAddress() ?? 'N/A') . "\n";
        $content .= "Total Price: " . ($order->getTotalPrice() ?? '0') . " TND\n";
        $content .= "Status: " . ($order->getStatus() ?? 'N/A') . "\n\n";
        $content .= "Thank you for trusting us! 💜";

        $filesystem->dumpFile($filePath, $content);

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        ]);
    }

    // ✅ Route pour générer le QR code avec texte brut
    #[Route('/aya/qr/code/{id}', name: 'aya_order_qr_code')]
    public function qrCodeOrder(int $id, OrderRepository $orderRepository, BuilderInterface $qrBuilder): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        $content = "📋 3alaKifi - Order Summary\n";
        $content .= "Status: " . $order->getStatus() . "\n";
        $content .= "Total Price: " . $order->getTotalPrice() . " TND\n";
        $content .= "Ordered At: " . $order->getOrderedAt()?->format('Y-m-d H:i') . "\n";
        $content .= "Payment: " . $order->getPaymentMethod() . "\n";
        $content .= "Address: " . $order->getExactAddress() . "\n";

        $result = $qrBuilder
            ->data($content)
            ->size(300)
            ->margin(10)
            ->build();

        return new Response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
        ]);
    }
}
