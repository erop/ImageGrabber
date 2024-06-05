<?php

namespace App\Controller;

use App\Service\ImageGrabbingService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ShowImagesController extends AbstractController
{
    
    private ImageGrabbingService $service;
    
    public function __construct(ImageGrabbingService $service)
    {
        $this->service = $service;
    }
    
    #[Route('/images', name: 'app_show_images')]
    public function __invoke(SessionInterface $session): Response
    {
        $baseUri = $session->get(IndexController::SESSION_KEY);
        
        if (null === $baseUri) {
            $this->addFlash('error', 'Пустой URL');
            return $this->redirectToRoute('app_index');
        }
        
        $baseUri = $this->cleanUpUri($baseUri);
        
        try {
            [$absoluteImageUris, $imagesSize] = $this->service->processImages($baseUri);
            
            return $this->render('show_images.html.twig', [
                'controller_name' => 'ShowImagesController',
                'images' => $absoluteImageUris,
                'imagesSize' => $imagesSize
            ]);
        } catch (RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('app_index');
        }
    }
    
    private function cleanUpUri(mixed $baseUri): string
    {
        return rtrim($baseUri, "/");
    }
}
