<?php
namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminTodoController extends AbstractController
{
    #[Route('/admin/todo', name: 'admin_todo')]
    public function index(SessionInterface $session)
    {
        $tasks = $session->get('tasks', []);

        return $this->render('admin/todolist.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/admin/todo/add', name: 'admin_todo_add', methods: ['POST'])]
    public function add(Request $request, SessionInterface $session)
    {
        $tasks = $session->get('tasks', []);
        $tasks[] = [
            'title' => $request->request->get('title'),
            'isDone' => false,
        ];
        $session->set('tasks', $tasks);

        return $this->redirectToRoute('admin_todo');
    }

    #[Route('/admin/todo/toggle/{index}', name: 'admin_todo_toggle')]
    public function toggle(SessionInterface $session, int $index)
    {
        $tasks = $session->get('tasks', []);
        if (isset($tasks[$index])) {
            $tasks[$index]['isDone'] = !$tasks[$index]['isDone'];
            $session->set('tasks', $tasks);
        }

        return $this->redirectToRoute('admin_todo');
    }

    #[Route('/admin/todo/delete/{index}', name: 'admin_todo_delete')]
    public function delete(SessionInterface $session, int $index)
    {
        $tasks = $session->get('tasks', []);
        if (isset($tasks[$index])) {
            unset($tasks[$index]);
            $session->set('tasks', array_values($tasks));
        }

        return $this->redirectToRoute('admin_todo');
    }
}
