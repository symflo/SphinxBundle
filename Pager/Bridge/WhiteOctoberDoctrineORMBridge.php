<?php

namespace Highco\SphinxBundle\Pager\Bridge;

use Highco\SphinxBundle\Pager\Bridge\PagerFantaAdapter\SphinxAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\ORM\EntityManager;

use Highco\SphinxBundle\Pager\InterfaceSphinxPager;
use Highco\SphinxBundle\Pager\AbstractSphinxPager;

/**
 * WhiteOctoberDoctrineORMBridge
 *
 * @package HighcoSphinBundle
 * @version 0.1
 * @author Stephane PY <py.stephane1(at)gmail.com>
 */
class WhiteOctoberDoctrineORMBridge extends AbstractSphinxPager implements InterfaceSphinxPager
{
    protected $em;
    protected $repository_class;
    protected $pk_column = "id";
    protected $results;
    protected $query = null;

    /**
     * __construct
     *
     * @param mixed $em
     * @return void
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * setRepositoryClass
     *
     * @param mixed $repositoryClass
     * @return void
     */
    public function setRepositoryClass($repositoryClass)
    {
        $this->repository_class = $repositoryClass;
    }

    /**
     * setPkColumn
     *
     * @param mixed $pkColumn
     * @return void
     */
    public function setPkColumn($pkColumn)
    {
        $this->pk_column = $pkColumn;
    }

    /**
     * setSphinxResults
     *
     * @param mixed $results
     * @return void
     */
    public function setSphinxResults($results)
    {
        $this->results = $results;
    }

    /**
     * getPager
     *
     * @return void
     */
    public function getPager()
    {
        if(is_null($this->repository_class))
        {
            throw new \RuntimeException('You should define a repository class on '.__CLASS__);
        }

        if(is_null($this->results))
        {
            throw new \RuntimeException('You should define sphinx results on '.__CLASS__);
        }

        $pks = $this->_extractPksFromResults();

        $results = array();
        if(false === empty($pks))
        {
            $qb = $this->getQuery();

            $qb = $qb->where($qb->expr()->in('r.'.$this->pk_column, $pks))
                //->addOrderBy('FIELD(r.id,...)', 'ASC')
                ->getQuery()
                ;
            //@todo watching on doctrine FIELD extension ... we cannot use it natively . . . .

            $unordoredResults = $qb->getResult();

            foreach($pks as $pk)
            {
                if(isset($unordoredResults[$pk]))
                {
                    $results[$pk] = $unordoredResults[$pk];
                }
            }
        }

        $adapter = $this->container->get('highco.sphinx.pager.white_october.doctrine_orm.adapter');
        $adapter->setArray($results);

        $adapter->setNbResults(isset($this->results['total_found']) ? $this->results['total_found'] : 0);

        return new Pagerfanta($adapter);
    }

    /**
     * getDefaultQuery
     *
     * @return string query
     */
    public function getDefaultQuery()
    {
        return $this->em
                ->createQueryBuilder()
                ->select('r')
                ->from($this->repository_class, sprintf('r INDEX BY r.%s', $this->pk_column));
    }

    /**
     * setDefaultQuery
     *
     * @param query
     * @return void
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * setDefaultQuery
     *
     * @param query
     * @return void
     */
    public function getQuery()
    {
        if ($this->query == null)
        {
            return $this->getDefaultQuery();
        }else{
            return $this->query;
        }
    }
}
