<?php   
namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeService
{
    public function generateQrCode(string $url): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size(250)
            ->margin(10)
            ->build();

        return base64_encode($result->getString());
    }
}
