<?php
// src/Controller/admin/AyaPromoExportController.php
namespace App\Controller\admin;

use App\Repository\CodePromoRepository;
use App\Repository\PromoCodeRepository;
use League\Csv\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class AyaPromoExportController extends AbstractController
{
    #[Route('/admin/promo/export/csv', name: 'admin_promo_export_csv')]
    public function export(CodePromoRepository $repo): StreamedResponse
    {
        $codes = $repo->findAll();
        $total = 0;

        return new StreamedResponse(function () use ($codes, &$total) {
            echo "\xEF\xBB\xBF"; // BOM UTF-8

            $csv = Writer::createFromString('');
            $csv->setDelimiter(';');

            $csv->insertOne(['Code', 'Discount (%)', 'Expiration Date', 'Status']);

            foreach ($codes as $code) {
                $status = ($code->getDateExpiration() < new \DateTime()) ? 'Expired' : 'Active';
                $discount = $code->getPourcentage();
                $total += $discount;

                $csv->insertOne([
                    $code->getCodePromo(),
                    $discount,
                    $code->getDateExpiration()?->format('d/m/Y') ?? '-',
                    $status
                ]);
            }

            $csv->insertOne([]);
            $csv->insertOne(['', '', '', 'TOTAL DISCOUNT', $total . ' %']);

            echo $csv->toString();
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="promo_codes.csv"',
        ]);
    }
}
