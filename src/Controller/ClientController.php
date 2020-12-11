<?php
/**
 * Controlador para la clientes
 */

namespace App\Controller;

use Antxony\Def\Contact\Level;
use Antxony\Def\Contact\Type;
use Antxony\Def\Editable\Editable;
use App\Entity\Client;
use App\Entity\ClientAddress;
use App\Entity\ClientExtra;
use App\Entity\Contact;
use App\Entity\ContactExtra;
use App\Entity\User;
use App\Repository\ClientCategoryRepository;
use App\Repository\ClientRepository;
use App\Repository\ClientExtraRepository;
use Antxony\Util;
use App\Repository\ClientAddressRepository;
use App\Repository\ContactExtraRepository;
use App\Repository\ContactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ClientController class
 * @Route("/client")
 * @author Antxony <dantonyofcarim@gmail.com>
 */
class ClientController extends AbstractController
{
    /**
     * Repositorio de usuarios
     *
     * @var ClientRepository
     */
    protected $cRep;

    /**
     * Repositorio de usuarios
     *
     * @var ClientCategoryRepository
     */
    protected $ccRep;

    /**
     * Repositorio de extras para usuarios
     *
     * @var ClientExtraRepository
     */
    protected $ceRep;

    /**
     * Repositorio de contactos
     *
     * @var ContactRepository
     */
    protected $coRep;

    /**
     * RRepositorio de extras para contactos
     *
     * @var ContactExtraRepository
     */
    protected $coeRep;

    /**
     * Repositorio de direcciones
     *
     * @var ClientAddressRepository
     */
    protected $caRep;

    /**
     * Usuario actual
     *
     * @var User
     */
    protected $actualUser;

    /**
     * herramientas útiles
     *
     * @var Util
     */
    protected $util;

    /**
     * Constructor
     *
     * @param ClientRepository $cRep
     * @param ClientCategoryRepository $ccRep
     * @param ClientExtraRepository $ceRep
     * @param ClientAddressRepository $caRep
     * @param ContactRepository $coRep
     * @param ContactExtraRepository $coeRep
     * @param Util $util
     * @param Security $security
     */
    public function __construct(
        ClientRepository $cRep,
        ClientCategoryRepository $ccRep,
        ClientExtraRepository $ceRep,
        ClientAddressRepository $caRep,
        ContactRepository $coRep,
        ContactExtraRepository $coeRep,
        Util $util,
        Security $security)
    {
        $this->util = $util;
        $this->cRep = $cRep;
        $this->ccRep = $ccRep;
        $this->ceRep = $ceRep;
        $this->coRep = $coRep;
        $this->coeRep = $coeRep;
        $this->caRep = $caRep;
        $this->actualUser = $security->getUser();
    }

    /**
     * Inicio
     * @Route("", name="client_index", methods={"GET"})
     * @IsGranted("ROLE_TEST")
     */
    public function index(): Response
    {
        return $this->render('view/client/index.html.twig', [
            'controller_name' => 'ClientController',
        ]);
    }

    /**
     * Conseguir todos los usuarios
     * @Route("/list", name="client_list", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param Request $request
     * @return Response
     */
    public function indexA(Request $request): Response
    {
        try {
            $params = json_decode(json_encode($request->query->all()));
            $result = $this->cRep->getBy($params);
            $showed = ((isset($params->page)) ? $params->page * $this->util::PAGE_COUNT : $this->util::PAGE_COUNT);
            $clients = $result['paginator'];
            $maxPages = ceil($clients->count() / $this->util::PAGE_COUNT);
            return $this->render("view/client/tbody.html.twig", [
                'clients' => $clients,
                'maxPages' => $maxPages,
                'thisPage' => ((isset($params->page)) ? $params->page : 1),
                'showed' => (($showed > $clients->count()) ? $clients->count() : $showed),
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Cargar formulario
     * @Route("/form", name="client_form", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @return Response
     */
    public function form(): Response
    {
        try {
            $categories = $this->ccRep->findAll();
            return $this->render("view/client/form.html.twig", [
                'id' => 'clientForm',
                'contact' => false,
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                                  Dirección                                 */
    /* -------------------------------------------------------------------------- */

    /**
     * Cargar formulario de dirección
     * @Route("/address/form/{id}", name="client_address_form", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @return Response
     */
    public function addressForm(int $id): Response
    {
        try {
            return $this->render("view/client/addressForm.html.twig", [
                'clientId' => $id
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Agregar dirección a cliente
     * @Route("/address", name="client_address_add", methods={"POST"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param Request $request
     * @return Response
     */
    public function addAddress(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $client = $this->cRep->find($content->id);
            $address = (new ClientAddress)
                ->setClient($client)
                ->setStreet($content->street)
                ->setExtNum($content->extnum)
                ->setIntNum($content->intnum)
                ->setCity($content->city)
                ->setState($content->state)
                ->setPostal($content->postal)
                ->setCountry($content->country);
            $client->updated($this->actualUser);
            $this->caRep->add($address);
            $this->cRep->update();
            $this->util->info("Se ha agregado la dirección al cliente <b>{$content->id}</b>(<em>{$client->getName()}</em>)");
            return new Response("Se ha agregado la dirección al cliente");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Actualizar dirección con x-editable
     * @Route("/address/{id}", name="client_address_update", methods={"PUT", "PATCH"})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function updateAddress(int $id, Request $request): Response
    {
        try {
            parse_str($request->getContent(), $content);
            $editable = new Editable($content);
            $message = "Se ha actualizado";
            $address = $this->caRep->find($id);
            if ($editable->name == "addressStreet") {
                $address->setStreet($editable->value);
                $message .= " la calle";
            }
            if ($editable->name == "addressExtNum") {
                $address->setExtNum($editable->value);
                $message .= " el número exterior";
            }
            if ($editable->name == "addressIntNum") {
                $address->setIntNum($editable->value);
                $message .= " el número interior";
            }
            if ($editable->name == "addressCity") {
                $address->setCity($editable->value);
                $message .= " la ciudad";
            }
            if ($editable->name == "addressState") {
                $address->setState($editable->value);
                $message .= " el estado";
            }
            if ($editable->name == "addressPostal") {
                $address->setPostal($editable->value);
                $message .= " el código postal";
            }
            if ($editable->name == "addressCountry") {
                $address->setCountry($editable->value);
                $message .= " el país";
            }
            $address->getClient()->updated($this->actualUser);
            $this->caRep->update();
            $this->cRep->update();
            $this->util->info($message . " de la dirección del cliente <b>{$address->getClient()->getId()}</b>(<em>{$address->getClient()->getName()}</em>)");
            return new Response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                                      .                                     */
    /* -------------------------------------------------------------------------- */

    /* -------------------------------------------------------------------------- */
    /*                               extras cliente                               */
    /* -------------------------------------------------------------------------- */

    /**
     * Agregar teléfono
     * @Route("/extras/{id}", name="client_extra_add", methods={"POST"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function addExtra(int $id, Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $type = (($content->type == Type::EMAIL) ? 'correo' : 'teléfono');
            $client = $this->cRep->find($id);
            $extra = (new ClientExtra)
                ->setClient($client)
                ->setType($content->type)
                ->setLevel($content->level)
                ->setValue($content->value);
            $client->updated($this->actualUser);
            $this->ceRep->add($extra);
            $this->cRep->update();
            $this->util->info("Se ha agregado un nuevo ${type}(<em>{$content->value}</em>) al cliente <b>{$id}</b>(<em>{$client->getName()}</em>)");
            return new Response("Se ha agregado un nuevo ${type} al cliente");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Eliminar dato extra
     * @Route("/extras/{id}", name="client_extra_delete", methods={"DELETE"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @return Response
     */
    public function deleteExtra(int $id): Response
    {
        try {
            $extra = $this->ceRep->find($id);
            $value = $extra->getValue();
            $this->ceRep->delete($extra);
            $extra->getClient()->updated($this->actualUser);
            $this->util->info("Se ha eliminado el dato extra <b><em>{$value}</em></b> del cliente <b>{$extra->getClient()->getId()}</b>(<em>{$extra->getClient()->getName()}</em>)");
            return new Response("Se ha eliminado");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                                      .                                     */
    /* -------------------------------------------------------------------------- */

    /* -------------------------------------------------------------------------- */
    /*                                  Contactos                                 */
    /* -------------------------------------------------------------------------- */

    /**
     * formulario de contacto (Es el mismo del cliente)
     * @Route("/contact/form", name="client_contact_form", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @return Response
     */
    public function addContactForm(): Response
    {
        try {
            return $this->render("view/client/form.html.twig", [
                'id' => 'clientContactForm',
                'contact' => true
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Agregar contacto
     * @Route("/contact/{id}", name="client_contact_add", methods={"POST"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function addContact(int $id, Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $contact = (new Contact)
                ->setClient($this->cRep->find($id))
                ->setName($content->name)
                ->setRole($content->role)
                ->created($this->actualUser)
                ->setSuspended(false);
            //Agregaos su correo
            $contactExtra = (new ContactExtra)
                ->setContact($contact)
                ->setType(Type::EMAIL)
                ->setLevel(Level::WORK) //Irrelevante
                ->setValue($content->email);
            //.
            //Agregamos su teléfono
            $contactExtra2 = (new ContactExtra)
                ->setContact($contact)
                ->setType(Type::PHONE)
                /**
                 * Tipo de teléfono
                 * WORK = 1
                 * MOBILE = 2
                 * HOME = 3
                 */
                ->setLevel($content->phone->type)
                ->setValue($content->phone->phone);
            //.
            $this->cRep->find($id)->updated($this->actualUser);
            $this->coRep->add($contact);
            $this->coeRep->add($contactExtra);
            $this->coeRep->add($contactExtra2);
            $this->cRep->update();
            $this->util->info("Se ha agregado al contacto <b>{$contact->getId()}</b>(<em>{$contact->getName()}</em>) para el cliente <b>{$id}</b>(<em>{$contact->getClient()->getName()}</em>)");
            return new Response("Contacto agregado");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Mostrar contacto
     * @Route("/contact/{id}", name="client_contact_show", methods={"GET"}, options={"expose" = true})
     *
     * @param integer $id
     * @return Response
     */
    public function showContact(int $id): Response
    {
        try {
            $contact = $this->coRep->find($id);
            return $this->render("view/client/contactShow.html.twig", [
                'contact' => $contact,
                'TYPE_MAIL' => Type::EMAIL,
                'TYPE_PHONE' => Type::PHONE,
                'LEVEL_WORK' => Level::WORK,
                'LEVEL_MOBILE' => Level::MOBILE,
                'LEVEL_HOME' => Level::HOME,
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Actualizar contacto con x-editable
     * @Route("/conatct/{id}", name="client_contact_update", methods={"PUT", "PATCH"})
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function updateContact(int $id, Request $request): Response
    {
        try {
            parse_str($request->getContent(), $content);
            $editable = new Editable($content);
            $contact = $this->coRep->find($id);
            $message = "se ha actualizado";
            if ($editable->name == "contactName") {
                $contact->setName($editable->value);
                $message .= " el nombre";
            }
            if ($editable->name == "contactRole") {
                $contact->setRole($editable->value);
                $message .= " el parentesco/referencia";
            }
            if ($editable->name == "contactEmail") {
                $contactExtra = $this->coeRep->find($editable->pk);
                $contactExtra->setValue($editable->value);
                $message .= " el correo";
            }
            if ($editable->name == "contactPhone") {
                $contactExtra = $this->coeRep->find($editable->pk);
                $contactExtra->setValue($editable->value);
                $message .= " un teléfono";
            }
            if ($editable->name == "contactPhoneLevel") {
                $contactExtra = $this->coeRep->find($editable->pk);
                $contactExtra->setLevel($editable->value);
                $message .= " un teléfono";
            }
            $contact->updated($this->actualUser);
            $this->coRep->update();
            $this->coeRep->update();
            $this->util->info($message . " del contacto <b>{$id}</b>(<em>{$contact->getName()}</em>) del cliente <b>{$contact->getClient()->getId()}</b>(<em>{$contact->getClient()->getName()}</em>)");
            return new response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Eliminar cliente
     * @Route("/contact/{id}", name="client_contact_delete", methods={"DELETE"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     *
     * @param integer $id
     * @return Response
     */
    public function deleteContact(int $id): Response
    {
        try {
            $contact = ($this->coRep->find($id))
                ->setSuspended(true)
                ->updated($this->actualUser);
            $this->cRep->find($contact->getClient())->updated($this->actualUser);
            $this->coRep->update();
            $this->util->info("Se ha suspendido el contacto <b>{$id}</b>(<em>{$contact->getClient()->getName()}</em>)");
            return new Response("Contacto eliminado");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                                      .                                     */
    /* -------------------------------------------------------------------------- */
    /* -------------------------------------------------------------------------- */
    /*                              extras contacto                               */
    /* -------------------------------------------------------------------------- */

    /**
     * Agregar teléfono
     * @Route("/contacto/extras/{id}", name="client_contact_extra_add", methods={"POST"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function addContactExtra(int $id, Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $type = (($content->type == 1) ? 'correo' : 'teléfono');
            $extra = (new ContactExtra)
                ->setContact($this->coRep->find($id))
                ->setType($content->type)
                ->setLevel($content->level)
                ->setValue($content->value);
            $this->coRep->find($id)->updated($this->actualUser);
            $this->coeRep->add($extra);
            $this->coRep->update();
            $this->util->info("Se ha agregado un nuevo {$type}(<em>{$content->value}</em>) al contacto <b>{$id}</b>(<em>{$extra->getContact()->getName()}</em>)");
            return new Response("Se ha agregado un nuevo ${type} al contacto");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Eliminar dato extra
     * @Route("/contacto/extras/{id}", name="client_contact_extra_delete", methods={"DELETE"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @return Response
     */
    public function deleteContactExtra(int $id): Response
    {
        try {
            $extra = $this->coeRep->find($id);
            $this->coeRep->delete($extra);
            $value = $extra->getValue();
            $extra->getContact()->updated($this->actualUser);
            $this->coRep->update();
            $this->util->info("Se ha eliminado el dato <b>$value</b> del contacto <b>{$extra->getContact()->getId()}</b>(<em>{$extra->getContact()->getName()}</em>)");
            return new Response("Se ha eliminado");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /* -------------------------------------------------------------------------- */
    /*                                      .                                     */
    /* -------------------------------------------------------------------------- */

    /**
     * Agregar cliente
     * @Route("", name="client_add", methods={"POST"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     *
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        try {
            $content = json_decode($request->getContent());
            $category = $this->ccRep->find($content->category);
            // Damos de alta al cliente
            $client = (new Client)
                ->setName($content->name)
                ->setSuspended(false)
                ->setCategory($category)
                ->created($this->actualUser);
            //.
            //Agregaos su correo
            $clientExtra = (new ClientExtra)
                ->setClient($client)
                ->setType(Type::EMAIL)
                ->setLevel(Level::WORK) //irrelevante
                ->setValue($content->email);
            //.
            //Agregamos su teléfono
            $clientExtra2 = (new ClientExtra)
                ->setClient($client)
                ->setType(Type::PHONE)
                /**
                 * Tipo de teléfono
                 * WORK = 1
                 * MOBILE = 2
                 * HOME = 3
                 */
                ->setLevel($content->phone->type)
                ->setValue($content->phone->phone);
            //.
            $this->cRep->add($client);
            $this->ceRep->add($clientExtra);
            $this->ceRep->add($clientExtra2);
            $this->util->info("Se ha agregado al cliente <b>{$client->getId()}</b>(<em>{$client->getName()}</em>)");
            return new Response("Cliente agregado");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Eliminar cliente
     * @Route("/{id}", name="client_delete", methods={"DELETE"}, options={"expose" = true})
     * @IsGranted("ROLE_ADMIN")
     *
     * @param integer $id
     * @return Response
     */
    public function delete(int $id): Response
    {
        try {
            $client = $this->cRep->find($id);
            $client
                ->setSuspended(true)
                ->updated($this->actualUser);
            $this->cRep->update();
            $this->util->info("Se ha suspendido el cliente <b>{$id}</b>(<em>{$client->getName()}</em>)");
            return new Response("Cliente eliminado");
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Mostrar cliente
     * @Route("/{id}", name="client_show", methods={"GET"}, options={"expose" = true})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @return Response
     */
    public function show(int $id): Response
    {
        try {
            $client = $this->cRep->find($id);
            return $this->render("view/client/show.html.twig", [
                'client' => $client,
                'TYPE_MAIL' => Type::EMAIL,
                'TYPE_PHONE' => Type::PHONE,
                'LEVEL_WORK' => Level::WORK,
                'LEVEL_MOBILE' => Level::MOBILE,
                'LEVEL_HOME' => Level::HOME,
            ]);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }

    /**
     * Actualizar Cliente (Con x-editable)
     * @Route("/{id}", name="client_update", methods={"PUT", "PATCH"})
     * @IsGranted("ROLE_TEST")
     *
     * @param integer $id
     * @param Request $request
     * @return Response
     */
    public function update(int $id, Request $request): Response
    {
        try {
            parse_str($request->getContent(), $content);
            $editable = new Editable($content);
            $client = $this->cRep->find($id);
            $message = "se ha actualizado";
            if ($editable->name == "clientName") {
                $client->setName($editable->value);
                $message .= " el nombre";
            }
            if ($editable->name == "clientEmail") {
                $clientExtra = $this->ceRep->find($editable->pk);
                $clientExtra->setValue($editable->value);
                $message .= " el correo";
            }
            if ($editable->name == "clientPhone") {
                $clientExtra = $this->ceRep->find($editable->pk);
                $clientExtra->setValue($editable->value);
                $message .= " un teléfono";
            }
            if ($editable->name == "clientPhoneLevel") {
                $clientExtra = $this->ceRep->find($editable->pk);
                $clientExtra->setLevel($editable->value);
                $message .= " un teléfono";
            }
            $client->updated($this->actualUser);
            $this->cRep->update();
            $this->ceRep->update();
            $this->util->info($message . " del cliente <b>{$id}</b>(<em>{$client->getName()}</em>)");
            return new response($message);
        } catch (Exception $e) {
            return $this->util->errorResponse($e);
        }
    }
}
