<?php

/**
 * Client Category controller
 */

namespace App\Controller;

use Antxony\Def\Editable\Editable;
use Antxony\Util;
use App\Entity\ClientCategory;
use App\Repository\ClientCategoryRepository;
use App\Repository\ClientRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class ClientCategoryController
 * @package App\Controller
 * @Route("/client_category")
 * @author Antxony <dantonyofcarim@gmail.com>
 */
class ClientCategoryController extends AbstractController
{

    protected ClientCategoryRepository $rep;

    protected ClientRepository $cRep;

    protected Util $util;

    public function __construct(ClientCategoryRepository $rep, ClientRepository $cRep, Util $util)
    {
        $this->rep = $rep;
        $this->cRep = $cRep;
        $this->util = $util;
    }

    /**
     * Category index
     * 
     * @Route("", name="client_category_index", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(): Response
    {
        return $this->render('view/client_category/index.html.twig', [
            'controller_name' => 'ClientCategoryController',
        ]);
    }

    /**
     * Get category list
     *
     * @Route("/list", name="client_category_list", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function list(Request  $request): Response
    {
        try {
            $params = json_decode(json_encode($request->query->all()));
            $result = $this->rep->getBy($params);
            $showed = ((isset($params->page)) ? $params->page * $this->util::PAGE_COUNT : $this->util::PAGE_COUNT);
            $categories = $result['paginator'];
            $maxPages = ceil($categories->count() / $this->util::PAGE_COUNT);
            return $this->render("view/client_category/tbody.html.twig", [
                'categories' => $categories,
                'maxPages' => $maxPages,
                'thisPage' => ((isset($params->page)) ? $params->page : 1),
                'showed' => (($showed > $categories->count()) ? $categories->count() : $showed),
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * add form
     *
     * @Route("/form", name="client_category_add_form", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function form(): Response
    {
        try {
            return $this->render("view/client_category/form.html.twig");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * change category form
     *
     * @Route("/changeForm/{id}", name="client_category_change_form", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function changeForm(int $id): Response
    {
        try {
            $client = $this->cRep->find($id);
            $categories = $this->rep->findAll();
            return $this->render("view/client_category/changeForm.html.twig", [
                'client' => $client,
                'categories' => $categories,
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Change client category
     *
     * @Route("/change", name="client_category_change", methods={"PUT", "PATCH"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function changeClientCategory(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $client = $this->cRep->find($content->clientId);
            $category = $this->rep->find($content->categoryId);
            if ($client == null)
                throw new Exception("No se pudo localizar al cliente");
            if ($category == null)
                throw new Exception("No se pudo localizar la categoría");
            $oldCat = $client->getCategory();
            $client->setCategory($category);
            $this->cRep->update();
            $message = "Se ha cambiado la categoría del cliente <b>{$client->getId()}</b> (<em>{$client->getName()}</em>)";
            if ($oldCat != null) {
                $message .= " de <b>{$oldCat->getName()}</b>";
            }
            $message .= " a <b>{$category->getName()}</b>";
            $this->util->info($message);
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * @Route("/color", name="client_category_color_edit", methods={"PUT", "PATCH"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function updateColor(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $category = $this->rep->find($content->id);
            if ($category == null) {
                throw new Exception("No se encontró la categoría");
            }
            $category->setColor($content->newColor);
            $message = "Se ha actualizado el color";
            $this->rep->update();
            $this->util->info($message . " de la categoria <b>{$category->getId()}</b> (<em>{$category->getName()}</em>)");
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Add category
     *
     * @Route("/", name="client_category_add", methods={"POST"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $category = (new ClientCategory())
                ->setName($content->name)
                ->setDescription($content->description)
                ->setColor($content->color);
            $this->rep->add($category);
            $message = "Se ha agregado la categoría <b>{$content->name}</b>";
            $this->util->info($message);
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Delete Category
     *
     * @Route("/{id}", name="client_category_delete", methods={"DELETE"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(int $id): Response
    {
        try {
            $category = $this->rep->find($id);
            $this->rep->delete($category);
            $message = "Se ha eliminado la categoría <b><em>{$category->getName()}</em></b>";
            $this->util->info($message);
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Show category
     *
     * @Route("/{id}", name="client_category_show", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     */
    public function show(int $id): Response
    {
        try {
            $category = $this->rep->find($id);
            return $this->render("view/client_category/show.html.twig", [
                'category' => $category
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Update category with x-editable
     *
     * @Route("/{id}", name="client_category_edit", methods={"PUT", "PATCH"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(int $id, Request $request): Response
    {
        try {
            parse_str($request->getContent(), $content);
            $editable = new Editable($content);
            $category = $this->rep->find($id);
            if ($category == null) {
                throw new Exception("No se pudo localizar la categoría");
            }
            $message = "se ha actualizado";
            if ($editable->name == "categoryName") {
                $category->setName($editable->value);
                $message .= " el nombre";
            }
            if ($editable->name == "categoryDescription") {
                $category->setDescription($editable->value);
                $message .= " la descripción";
            }
            $this->rep->update();
            $this->util->info($message . " para la categoría <b>{$category->getId()}</b> (<em>{$category->getName()}</em>)");
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }
}
