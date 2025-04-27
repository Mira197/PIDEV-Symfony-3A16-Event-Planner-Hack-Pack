<?php

namespace App\Controller\admin;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Knp\Component\Pager\PaginatorInterface; // Pagination

#[Route('/admin/event')]
class EventAdminController extends AbstractController
{
    #[Route('/', name: 'event_admin_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, PaginatorInterface $paginator): Response
    {
        $query = $eventRepository->createQueryBuilder('e')
            ->orderBy('e.start_date', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5
        );

        // Convertir les images en base64 si nécessaire
        foreach ($pagination as $event) {
            if ($event->getImageData()) {
                $event->base64Image = base64_encode(stream_get_contents($event->getImageData()));
            }
        }

        return $this->render('admin/event_admin/indexEventAdmin.html.twig', [
            'events' => $pagination,
        ]);
    }

    #[Route('/ajax/search', name: 'event_ajax_search', methods: ['GET'])]
    public function ajaxSearch(Request $request, EventRepository $eventRepository, NormalizerInterface $normalizer): JsonResponse
    {
        $keyword = $request->query->get('q', '');
        $events = $eventRepository->searchByKeyword($keyword)->getQuery()->getResult();

        // Convertir les images en base64 si nécessaire
        foreach ($events as $event) {
            if ($event->getImageData() && !$event->getImageFilename()) {
                $event->base64Image = base64_encode(stream_get_contents($event->getImageData()));
            }
        }

        $json = $normalizer->normalize($events, 'json', [
            'attributes' => [
                'idEvent',
                'name',
                'city',
                'capacity',
                'startDate',
                'endDate',
                'imageFilename',
                'base64Image'
            ]
        ]);

        return new JsonResponse($json);
    }

    #[Route('/{id}', name: 'event_admin_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        // Convertir en base64 si image BLOB sans filename
        /*$base64Image = null;
        if ($event->getImageData() && !$event->getImageFilename()) {
            $base64Image = base64_encode(stream_get_contents($event->getImageData()));
        }*/
        if ($event->getImageData()) {
            $event->base64Image = base64_encode(stream_get_contents($event->getImageData()));
        }

        return $this->render('admin/event_admin/showEventAdmin.html.twig', [
            'event' => $event,
            //'base64Image' => $base64Image,
        ]);
    }

    #[Route('/{id}/edit', name: 'event_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $binary = file_get_contents($imageFile->getPathname());
                $event->setImageData($binary);

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $event->setImageFilename($newFilename);

                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
            }

            $em->flush();
            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('event_admin_index');
        }

        $base64Image = null;
        if ($event->getImageData() && !$event->getImageFilename()) {
            $base64Image = base64_encode(stream_get_contents($event->getImageData()));
        }

        return $this->renderForm('admin/event_admin/editEventAdmin.html.twig', [
            'event' => $event,
            'form' => $form,
            'base64Image' => $base64Image,
        ]);
    }

    #[Route('/{id}', name: 'event_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $event->getIdEvent(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Event deleted successfully!');
        }

        return $this->redirectToRoute('event_admin_index', [], Response::HTTP_SEE_OTHER);
    }


}
