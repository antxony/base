<?php
/**
 * InfoLog repository
 */

namespace App\Repository;

use App\Entity\InfoLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Antxony\Util;
use Doctrine\Common\Collections\Criteria;

/**
 * InfoLogRepository class
 *
 * @method InfoLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method InfoLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method InfoLog[]    findAll()
 * @method InfoLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InfoLogRepository extends ServiceEntityRepository
{
    /**
     * constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InfoLog::class);
    }

    /**
     * conseguir por filtrado
     *
     * @param mixed $params
     * @return array
     * @throws QueryException
     */
    public function getBy($params)
    {
        $page = ((isset($params->page)) ? $params->page : 1);
        // Create our query
        $query = $this->createQueryBuilder('p')
            ->orderBy("p." . $params->ordercol, $params->orderorder);
        if(isset($params->user) && $params->user > 0){
            $userCriteria = new Criteria();
            $userCriteria->where(Criteria::expr()->eq("p.user", $params->user));
            $query->addCriteria($userCriteria);
        }
        if (isset($params->search) && $params->search != "") {
            $searchRoute = str_replace("_", "/", $params->search);
            $searchCriteria = new Criteria();
            $searchCriteria->where(Criteria::expr()->contains("p.clientip", $params->search));
            $searchCriteria->orWhere(Criteria::expr()->contains("p.route", $searchRoute));
            $searchCriteria->orWhere(Criteria::expr()->contains("p.message", $params->search));
            $query->addCriteria($searchCriteria);
        }
        if (isset($params->method) && $params->method != "") {
            $methodCriteria = new Criteria();
            $methodCriteria->where(Criteria::expr()->eq("p.method", strtoupper($params->method)));
            $query->addCriteria($methodCriteria);
        }
        $query->getQuery();

        $paginator = new Paginator($query);
        $paginator->getQuery()
            ->setFirstResult(Util::PAGE_COUNT * ($page - 1)) // Offset
            ->setMaxResults(Util::PAGE_COUNT); // Limit

        return array('paginator' => $paginator, 'query' => $query);
    }

    // /**
    //  * @return InfoLog[] Returns an array of InfoLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InfoLog
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
