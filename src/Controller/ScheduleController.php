<?php

/**
 * Scheduler controller
 */

namespace App\Controller;

use DateTime;
use Exception;
use Antxony\Util;
use DateTimeZone;
use App\Entity\User;
use App\Entity\Schedule;
use Antxony\Def\Task\Task;
use Antxony\Observation;
use App\Controller\ObsController;
use App\Entity\ScheduleRecurrent;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use App\Repository\ScheduleRepository;
use App\Repository\ScheduleRecurrentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use App\Repository\ScheduleCategoryRepository;
use App\Repository\SchedulePriorityRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * ScheduleController class
 * @package App\Controller
 * @Route("/schedule")
 * @author Antxony <dantonyofcarim@gmail.com>
 */
class ScheduleController extends AbstractController
{

    protected Util $util;

    protected ScheduleRepository $rep;

    protected ScheduleRecurrentRepository $srRep;

    protected ScheduleCategoryRepository $scRep;

    protected SchedulePriorityRepository $spRep;

    protected UserRepository $uRep;

    protected ClientRepository $cRep;

    protected Security $security;

    public function __construct(
        Util $util,
        ScheduleRepository $rep,
        ScheduleRecurrentRepository $srRep,
        ScheduleCategoryRepository $scRep,
        SchedulePriorityRepository $spRep,
        Security $security,
        UserRepository $uRep,
        ClientRepository $cRep
    ) {
        $this->util = $util;
        $this->rep = $rep;
        $this->srRep = $srRep;
        $this->scRep = $scRep;
        $this->spRep = $spRep;
        $this->security = $security;
        $this->uRep = $uRep;
        $this->cRep = $cRep;
    }
    /**
     * @Route("", name="schedule_index", methods={"GET"})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function index(): Response
    {
        try {
            $users = $this->uRep->findAll();
            $categoriesC = $this->scRep->findAll();
            $categories = [];
            $categories[] = [
                'value' => '0',
                'name' => 'TODAS',
                'view' => '<div class="text-center">TODAS</div>'
            ];
            foreach ($categoriesC as $category) {
                $categories[] = [
                    'value' => $category->getId(),
                    'name' => $category->getName(),
                    'view' => '<div class="row"><div class="col-md-2 p-0"><div class="badge round color-shadow w-100"style="background-color: ' . $category->getBackgroundColor() . '; color: ' . $category->getBackgroundColor() . ';"><i class="fas fa-palette"></i></div></div><div class="col-md-10 text-center text-truncate">' . $category->getName() . '</div></div>'
                ];
            }
            return $this->render('view/schedule/index.html.twig', [
                'users' => $users,
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/month", name="schedule_month", methods={"GET"}, options={"expose" = true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function month(Request $request): Response
    {
        try {
            $params = json_decode(json_encode($request->query->all()));
            $params = (object) array_merge((array)$params, array('actualUser' => $this->security->getUser()));
            $monthOffset = $params->offset;
            $month = [];
            $first = (int)strftime("%w", strtotime("first day of {$monthOffset} month"));
            $last = (int)strftime("%e", strtotime("last day of {$monthOffset} month"));
            $actual = (int)strftime("%w", strtotime("0 day"));
            $monthName = strftime("%B %Y", strtotime("today {$monthOffset} month"));
            $week = [];
            $eventsS = $this->rep->getBy("month", $params);
            for ($i = 0; $i < 7; $i++) {
                $dif = $i - $actual;
                $week[] = strftime("%A", strtotime("{$dif} day"));
            }
            for ($i = 0; $i < $last + $first; $i++) {
                $day = ($i - $first + 1);
                if ($i < $first) {
                    $month[] = [
                        'day' => null,
                        'date' => '',
                        'events' => [],
                        'eventrd' => [],
                        'eventrm' => [],
                        'eventry' => [],
                    ];
                } else {
                    $events = [];
                    foreach ($eventsS as $event) {
                        $eventDay = (int)$event->getDate()->format('d');
                        if ($eventDay == $day) {
                            $events[] = $event;
                        }
                    }
                    $today = strftime("%d", strtotime("today"));
                    $diff = $day - $today;
                    $eventrd = $this->getRecurrentDays(
                        $params,
                        strftime("%u", strtotime("{$diff} day {$monthOffset} month")),
                        "{$diff} day {$monthOffset} month"
                    );
                    $eventrm = $this->getRecurrentMonth(
                        $params,
                        strftime("%d", strtotime("{$diff} day {$monthOffset} month")),
                        "{$diff} day {$monthOffset} month"
                    );
                    $eventry = $this->getRecurrentYear(
                        $params,
                        strftime("%m", strtotime("{$diff} day {$monthOffset} month")),
                        strftime("%d", strtotime("{$diff} day {$monthOffset} month")),
                        "{$diff} day {$monthOffset} month"
                    );
                    $month[] = [
                        'day' => $day,
                        'date' => strftime("%d-%m-%Y", strtotime("{$diff} day {$monthOffset} month")),
                        'events' => $events,
                        'eventrd' => $eventrd,
                        'eventrm' => $eventrm,
                        'eventry' => $eventry,
                    ];
                }
            }
            while (count($month) % 7 != 0) {
                $month[] = [
                    'day' => null,
                    'date' => '',
                    'events' => [],
                    'eventrd' => [],
                    'eventrm' => [],
                    'eventry' => [],
                ];
            }
            return $this->render("view/schedule/types/month.html.twig", [
                'first' => $first,
                'month' => $month,
                'monthName' => $monthName,
                'week' => $week,
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/week", name="schedule_week", methods={"GET"}, options={"expose" = true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function week(Request $request): Response
    {
        try {
            $params = json_decode(json_encode($request->query->all()));
            $params = (object) array_merge((array)$params, array('actualUser' => $this->security->getUser()));
            $offset = $params->offset;
            $week = [];
            $actual = (int)strftime("%w", strtotime("0 day"));
            $dif = 0 - $actual;
            $monthName = strftime("%B %Y", strtotime("{$dif} day {$offset} week"));
            $lastDayMonth = $monthName;
            $eventsS = $this->rep->getBy("week", $params);
            for ($i = 0; $i < 7; $i++) {
                $dif = $i - $actual;
                $evDay = strftime("%d-%m-%Y", strtotime("{$dif} day {$offset} week"));
                $events = [];
                foreach ($eventsS as $event) {
                    if ($evDay == $event->getDate()->format("d-m-Y")) {
                        $events[] = $event;
                    }
                }
                $eventrd = $this->getRecurrentDays(
                    $params,
                    strftime("%u", strtotime("{$dif} day {$offset} week")),
                    "{$dif} day {$offset} week"
                );
                $eventrm = $this->getRecurrentMonth(
                    $params,
                    strftime("%d", strtotime("{$dif} day {$offset} week")),
                    "{$dif} day {$offset} week"
                );
                $eventry = $this->getRecurrentYear(
                    $params,
                    strftime("%m", strtotime("{$dif} day {$offset} week")),
                    strftime("%d", strtotime("{$dif} day {$offset} week")),
                    "{$dif} day {$offset} week"
                );
                $lastDayMonth = strftime("%B %Y", strtotime("{$dif} day {$offset} week"));
                $week[] = [
                    "name" => strftime("%A", strtotime("{$dif} day {$offset} week")),
                    "day" => strftime("%d", strtotime("{$dif} day {$offset} week")),
                    "date" => strftime("%d-%m-%Y", strtotime("{$dif} day {$offset} week")),
                    "events" => $events,
                    "eventrd" => $eventrd,
                    "eventrm" => $eventrm,
                    "eventry" => $eventry,
                ];
            }
            return $this->render("view/schedule/types/week.html.twig", [
                'monthName' => $monthName,
                'lastDayMonth' => $lastDayMonth,
                'week' => $week,
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/day", name="schedule_day", methods={"GET"}, options={"expose" = true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function day(Request $request): Response
    {
        try {
            $day = [];
            $params = json_decode(json_encode($request->query->all()));
            $params = (object) array_merge((array)$params, array('actualUser' => $this->security->getUser()));
            $offset = $params->offset;
            $monthName = strftime("%B %Y", strtotime("today {$offset} day"));
            $day += ["name" => strftime("%A", strtotime("today {$offset} day"))];
            $day += ["day" => strftime("%d", strtotime("today {$offset} day"))];
            $day += ["date" => strftime("%d-%m-%Y", strtotime("today {$offset} day"))];
            $eventsS = $this->rep->getBy("day", $params);
            $events = [];
            // $eventrd = [];
            $evDay = strftime("%d-%m-%Y", strtotime("today {$offset} day"));
            foreach ($eventsS as $event) {
                if ($evDay == $event->getDate()->format("d-m-Y")) {
                    $events[] = $event;
                }
            }
            $eventry = $this->getRecurrentYear(
                $params,
                strftime("%m", strtotime("today {$offset} day")),
                strftime("%d", strtotime("today {$offset} day")),
                "today {$offset} day"
            );
            $day += ["events" => $events];
            $day += ["eventrd" => $this->getRecurrentDays(
                $params,
                strftime("%u", strtotime("today {$offset} day")),
                "today {$offset} day"
            )];
            $day += ["eventrm" => $this->getRecurrentMonth(
                $params,
                strftime("%d", strtotime("today {$offset} day")),
                "today {$offset} day"
            )];
            $day += ["eventry" => $eventry];
            return $this->render("view/schedule/types/day.html.twig", [
                'monthName' => $monthName,
                'day' => $day,
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    public function getRecurrentDays($params, $day, $dateString): array
    {
        if (!$params->showRecurrents) {
            return [];
        }
        $events = [];
        $eventsR = $this->srRep->getBy("week", $params, $dateString);
        foreach ($eventsR as $event) {
            $yes = false;
            foreach ($event->getDays() as $dday) {
                if ($dday == $day) {
                    $yes = true;
                    break;
                }
            }
            if ($yes) {
                $events[] = $event;
            }
        }
        return $events;
    }

    public function getRecurrentMonth($params, $day, $dateString): array
    {
        if (!$params->showRecurrents) {
            return [];
        }
        $events = [];
        $eventsR = $this->srRep->getBy("month", $params, $dateString);
        foreach ($eventsR as $event) {
            $yes = false;
            foreach ($event->getDays() as $dday) {
                if ($dday == $day) {
                    $yes = true;
                    break;
                }
            }
            if ($yes) {
                $events[] = $event;
            }
        }
        return $events;
    }

    public function getRecurrentYear($params, $month, $day, $dateString): array
    {
        if (!$params->showRecurrents) {
            return [];
        }
        $events = [];
        $eventsR = $this->srRep->getBy("year", $params, $dateString);
        foreach ($eventsR as $event) {
            $yes = false;
            foreach ($event->getDays()[0] as $mmonth) {
                if ($mmonth == $month) {
                    foreach ($event->getDays()[1] as $dday) {
                        if ($dday == $day) {
                            $yes = true;
                            break;
                        }
                    }
                }
            }
            if ($yes) {
                $events[] = $event;
            }
        }
        return $events;
    }

    /**
     * @Route("/form", name="schedule_form", methods={"GET"}, options={"expose" = true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function form(): Response
    {
        try {
            $categoriesC = $this->scRep->findAll();
            $prioritiesC = $this->spRep->findAll();
            $usersC = $this->uRep->findAll();
            $categories = $priorities = $users = [];
            foreach ($categoriesC as $category) {
                $categories[] = [
                    'value' => $category->getId(),
                    'name' => $category->getName(),
                    'view' => '<div class="row"><div class="col-md-2"><div class="badge round color-shadow w-100"style="background-color: ' . $category->getBackgroundColor() . '; color: ' . $category->getBackgroundColor() . ';"><i class="fas fa-palette"></i></div></div><div class="col-md-10 text-center">' . $category->getName() . '</div></div>'
                ];
            }
            foreach ($prioritiesC as $priority) {
                $priorities[] = [
                    'value' => $priority->getId(),
                    'name' => $priority->getName(),
                    'view' => '<div class="row"><div class="col-md-2"><div class="badge round color-shadow w-100"style="background-color: ' . $priority->getColor() . '; color: ' . $priority->getColor() . ';"><i class="fas fa-palette"></i></div></div><div class="col-md-10 text-center">' . $priority->getName() . '</div></div>'
                ];
            }
            $users[] = [
                'value' => 0,
                'name' => 'Sin asignar',
                'view' => 'sin asignar'
            ];
            foreach ($usersC as $user) {
                $users[] = [
                    'value' => $user->getId(),
                    'name' => $user->getName(),
                    'view' => $user->getName()
                ];
            }
            return $this->render("view/schedule/form.html.twig", [
                'categories' => $categories,
                'priorities' => $priorities,
                'users' => $users
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/done", name="schedule_done", methods={"POST"}, options={"expose"=true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function done(Request $request, Observation $ob): Response
    {
        try {
            /**
             * @var User
             */
            $user = $this->security->getUser();
            $content = json_decode($request->getContent());
            if ($content->recurrent) {
                $task = $this->srRep->find($content->id);
                if ($task == null) {
                    throw new Exception("No se encontró la tarea");
                }
                if (
                    !$user->hasRole("ROLE_SUPERVISOR") &&
                    ($user->getId() != $task->getCreatedBy()->getId())
                ) {
                    if (($task->getAssigned() != null)) {
                        if ($user->getId() != $task->getAssigned()->getId()) {
                            throw new Exception("No tienes permiso para realizar esta acción");
                        }
                    } else {
                        throw new Exception("No tienes permiso para realizar esta acción");
                    }
                }
                $task
                    ->setEndDate(new \DateTime("now", new \DateTimeZone("America/Mexico_City")))
                    ->setUpdatedAt(new \DateTime("now", new \DateTimeZone("America/Mexico_City")))
                    ->setUpdatedBy($user);
                $message = "ha finalizado la tarea";
                $ob->add($task->getId(), "ScheduleRecurrent", "<small class=\"text-muted text-center\"><em>{$message}</em></small>");
                $this->util->info("Se {$message} <b>{$task->getId()}</b> (<em>{$task->getTitle()}</em>)");
                return new Response("Se " . $message);
            } else {
                $task = $this->rep->find($content->id);
                if ($task == null) {
                    throw new Exception("No se encontró la tarea");
                }
                if (
                    !$user->hasRole("ROLE_SUPERVISOR") &&
                    ($user->getId() != $task->getCreatedBy()->getId())
                ) {
                    if (($task->getAssigned() != null)) {
                        if ($user->getId() != $task->getAssigned()->getId()) {
                            throw new Exception("No tienes permiso para realizar esta acción");
                        }
                    } else {
                        throw new Exception("No tienes permiso para realizar esta acción");
                    }
                }
                $task
                    ->setDone($content->done)
                    ->updated($this->security->getUser());
                $message = "ha ";
                if ($content->done) {
                    $message .= "finalizado";
                } else {
                    $message .= "reactivado";
                }
                $message .= " la tarea";
                $ob->add($task->getId(), "Schedule", "<small class=\"text-muted text-center\"><em>{$message}</em></small>");
                $this->util->info("Se {$message} <b>{$task->getId()}</b> (<em>{$task->getTitle()}</em>)");
                return new Response("Se " . $message);
            }
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/asign", name="schedule_asign_form", methods={"GET"}, options={"expose"=true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function asignForm(): Response
    {
        try {
            $users = $this->uRep->findAll();
            return $this->render("view/schedule/asign.html.twig", [
                'users' => $users
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/update", name="schedule_update", methods={"PUT", "PATCH"}, options={"expose" = true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function update(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $task = $this->rep->find($content->id);
            if ($task == null) {
                throw new Exception("No se pudo encontrar la tarea");
            }
            switch ($content->type) {
                case Task::ASIGN:
                    $user = $this->uRep->find($content->value);
                    if ($user == null) {
                        throw new Exception("No se pudo encontrar al usuario");
                    }
                    $task
                        ->setAssigned($user)
                        ->updated($this->security->getUser());
                    $message = "Se ha asignado la tarea <b>{$task->getId()}</b> (<em>{$task->getTitle()}</em>) al usuario <b>{$user->getId()}</b> (<em>{$user->getName()}</em>)";
                    break;
                case Task::PRIORITY:
                    $priority = $this->spRep->find($content->value);
                    if ($priority == null) {
                        throw new Exception("No se pudo encontrar la prioridad");
                    }
                    $task
                        ->setPriority($priority)
                        ->updated($this->security->getUser());
                    $message = "Se ha cambiado la priordad de la tarea <b>{$task->getId()}</b> (<em>{$task->getTitle()}</em>) a <b>{$priority->getId()}</b> (<em>{$priority->getName()}</em>)";
                    break;
                case Task::DATE:
                    $old = $task->getDate();
                    $new = new DateTime($content->value, new DateTimeZone("America/Mexico_City"));
                    $task
                        ->setDate($new)
                        ->updated($this->security->getUser());
                    $message = "Se ha cambiado la fecha de la tarea <b>{$task->getId()}</b> (<em>{$task->getTitle()}</em>) de <b>{$old->format("d-m-Y H:i:s")}</b> a <b>{$new->format("d-m-Y H:i:s")}</b>";
                    break;
                default:
                    throw new Exception("No se pudo determinar la orden");
                    break;
            }
            $this->util->info($message);
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/{id}", name="schedule_show", methods={"GET"}, options={"expose" = true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function show(int $id, Request $request): Response
    {
        try {
            $params = json_decode(json_encode($request->query->all()));
            if ($params->recurrent) {
                $task = $this->srRep->find($id);
            } else {
                $task = $this->rep->find($id);
            }
            if ($task == null) {
                throw new Exception("No se pudo encontrar la tarea");
            }
            if ($params->recurrent) {
                return $this->render("view/schedule/showR.html.twig", [
                    'task' => $task
                ]);
            } else {
                return $this->render("view/schedule/show.html.twig", [
                    'task' => $task
                ]);
            }
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/{id}", name="schedule_delete", methods={"DELETE"}, options={"expose"=true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function delete(int $id): Response
    {
        try {
            /**
             * @var User
             */
            $user = $this->security->getUser();
            $task = $this->rep->find($id);
            if ($task == null) {
                throw new Exception("No se pudo encontrar la tarea");
            }
            if (
                !$user->hasRole("ROLE_SUPERVISOR") &&
                ($user->getId() != $task->getCreatedBy()->getId())
            ) {
                throw new Exception("No tienes permiso para realizar esta acción");
            }
            $oldId = $task->getId();
            $oldTitle = $task->getTitle();
            $this->rep->delete($task);
            $this->rep->update();
            $message = "Se ha eliminado la tarea";
            $this->util->info("{$message} <b>{$oldId}</b> (<em>{$oldTitle}</em>)");
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/", name="schedule_add", methods={"POST"}, options={"expose" = true})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function add(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $time = new DateTime($content->date, new DateTimeZone("America/Mexico_city"));
            $category = $this->scRep->find($content->category);
            $priority = $this->spRep->find($content->priority);
            $user = null;
            $extra = "";
            $client = $this->cRep->find($content->client);
            if ((int)$content->user != 0) {
                $user = $this->uRep->find($content->user);
                $extra = ". Se le ha asignado a <b>{$user->getName()}<b>";
            }
            if ($content->recurrent) {
                $task = (new ScheduleRecurrent())
                    ->setTitle($content->name)
                    ->setDetail($content->detail)
                    ->setCategory($category)
                    ->setPriority($priority)
                    ->setDone(false)
                    ->setAssigned($user)
                    ->setClient($client)
                    ->setType($content->recType)
                    ->setDays($content->recData)
                    ->created($this->security->getUser());
                $this->srRep->add($task);
            } else {
                $task = (new Schedule())
                    ->setTitle($content->name)
                    ->setDetail($content->detail)
                    ->setDate($time)
                    ->setCategory($category)
                    ->setPriority($priority)
                    ->setDone(false)
                    ->setAssigned($user)
                    ->setClient($client)
                    ->created($this->security->getUser());
                $this->rep->add($task);
            }
            $this->util->info("Se ha agregado la tarea <b>{$task->getId()}</b> (<em>{$task->getTitle()}</em>)" . $extra);
            return new Response("Se ha agregado la tarea");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }
}
