<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PdfController extends AbstractController
{
    #[Route('/viewer', name: 'pdf_viewer')]
    public function viewer(Request $request): Response
    {
        $pdfPath = $request->query->get('path');
        $highlight = $request->query->get('highlight');
        $page = (int) $request->query->get('page', 1);

        return $this->render('pdf/viewer.html.twig', [
            'path' => $pdfPath,
            'highlight' => $highlight,
            'page' => $page,
        ]);
    }
}
