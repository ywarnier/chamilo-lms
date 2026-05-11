<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Traits\Repository\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tree\RepositoryUtils;
use Gedmo\Tree\RepositoryUtilsInterface;
use Gedmo\Tree\TreeListener;

trait TreeRepositoryTrait
{
    /**
     * Tree listener on event manager.
     *
     * @var TreeListener
     */
    protected $listener;

    /**
     * Repository utils.
     *
     * @var RepositoryUtils
     */
    protected $repoUtils;

    /**
     * @return EntityManagerInterface
     */
    abstract protected function getEntityManager();

    /**
     * @return ClassMetadata
     */
    abstract protected function getClassMetadata();

    /**
     * This method should be called in your repository __construct().
     * Example:
     *
     * class MyTreeRepository extends EntityRepository
     * {
     *     use NestedTreeRepository; // or ClosureTreeRepository, or MaterializedPathRepository.
     *
     *     public function __construct(EntityManager $em, ClassMetadata $class)
     *     {
     *         parent::__construct($em, $class);
     *
     *         $this->initializeTreeRepository($em, $class);
     *     }
     *
     *     // ...
     * }
     */
    public function initializeTreeRepository(EntityManagerInterface $em, ClassMetadata $class): void
    {
        $treeListener = null;
        foreach ($em->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TreeListener) {
                    $treeListener = $listener;

                    break;
                }
            }
            if ($treeListener) {
                break;
            }
        }

        if (null === $treeListener) {
            throw new InvalidMappingException('Tree listener was not found on your entity manager, it must be hooked into the event manager');
        }

        $this->listener = $treeListener;
        if (!$this->validate()) {
            throw new InvalidMappingException('This repository cannot be used for tree type: '.$treeListener->getStrategy($em, $class->name)->getName());
        }

        $this->repoUtils = new RepositoryUtils($this->getEntityManager(), $this->getClassMetadata(), $this->listener, $this);
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->getEntityManager()->createQueryBuilder();
    }

    /**
     * Sets the RepositoryUtilsInterface instance.
     */
    public function setRepoUtils(RepositoryUtilsInterface $repoUtils)
    {
        $this->repoUtils = $repoUtils;

        return $this;
    }

    /**
     * Returns the RepositoryUtilsInterface instance.
     *
     * @return RepositoryUtilsInterface|null
     */
    public function getRepoUtils()
    {
        return $this->repoUtils;
    }

    public function childCount($node = null, $direct = false)
    {
        $meta = $this->getClassMetadata();

        if (\is_object($node)) {
            if (!$node instanceof $meta->name) {
                throw new InvalidArgumentException('Node is not related to this repository');
            }

            $wrapped = new EntityWrapper($node, $this->getEntityManager());

            if (!$wrapped->hasValidIdentifier()) {
                throw new InvalidArgumentException('Node is not managed by UnitOfWork');
            }
        }

        $qb = $this->getChildrenQueryBuilder($node, $direct);

        // We need to remove the ORDER BY DQL part since some vendors could throw an error
        // in count queries
        $dqlParts = $qb->getDQLParts();

        // We need to check first if there's an ORDER BY DQL part, because resetDQLPart doesn't
        // check if its internal array has an "orderby" index
        if (isset($dqlParts['orderBy'])) {
            $qb->resetDQLPart('orderBy');
        }

        $aliases = $qb->getRootAliases();
        $alias = $aliases[0];

        $qb->select('COUNT('.$alias.')');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @see RepositoryUtilsInterface::childrenHierarchy
     *
     * @param null|mixed $node
     * @param mixed      $direct
     * @param mixed      $includeNode
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->repoUtils->childrenHierarchy($node, $direct, $options, $includeNode);
    }

    /**
     * @see RepositoryUtilsInterface::buildTree
     */
    public function buildTree(array $nodes, array $options = [])
    {
        return $this->repoUtils->buildTree($nodes, $options);
    }

    /**
     * @see RepositoryUtilsInterface::buildTreeArray
     */
    public function buildTreeArray(array $nodes)
    {
        return $this->repoUtils->buildTreeArray($nodes);
    }

    /**
     * @see RepositoryUtilsInterface::setChildrenIndex
     *
     * @param mixed $childrenIndex
     */
    public function setChildrenIndex($childrenIndex): void
    {
        $this->repoUtils->setChildrenIndex($childrenIndex);
    }

    /**
     * @see RepositoryUtilsInterface::getChildrenIndex
     */
    public function getChildrenIndex()
    {
        return $this->repoUtils->getChildrenIndex();
    }

    /**
     * Checks if current repository is right
     * for currently used tree strategy.
     *
     * @return bool
     */
    abstract protected function validate();

    /**
     * Get all root nodes query builder.
     *
     * @param string - Sort by field
     * @param string - Sort direction ("asc" or "desc")
     * @param null|mixed $sortByField
     * @param mixed      $direction
     *
     * @return QueryBuilder - QueryBuilder object
     */
    abstract public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc');

    /**
     * Get all root nodes query.
     *
     * @param string - Sort by field
     * @param string - Sort direction ("asc" or "desc")
     * @param null|mixed $sortByField
     * @param mixed      $direction
     *
     * @return Query - Query object
     */
    abstract public function getRootNodesQuery($sortByField = null, $direction = 'asc');

    /**
     * Returns a QueryBuilder configured to return an array of nodes suitable for buildTree method.
     *
     * @param object $node        - Root node
     * @param bool   $direct      - Obtain direct children?
     * @param array  $options     - Options
     * @param bool   $includeNode - Include node in results?
     *
     * @return QueryBuilder - QueryBuilder object
     */
    abstract public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Returns a Query configured to return an array of nodes suitable for buildTree method.
     *
     * @param object $node        - Root node
     * @param bool   $direct      - Obtain direct children?
     * @param array  $options     - Options
     * @param bool   $includeNode - Include node in results?
     *
     * @return Query - Query object
     */
    abstract public function getNodesHierarchyQuery($node = null, $direct = false, array $options = [], $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a QueryBuilder object.
     *
     * @param object $node        - if null, all tree nodes will be taken
     * @param bool   $direct      - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction   - sort direction : "ASC" or "DESC"
     * @param bool   $includeNode - Include the root node in results?
     *
     * @return QueryBuilder - QueryBuilder object
     */
    abstract public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);

    /**
     * Get list of children followed by given $node. This returns a Query.
     *
     * @param object $node        - if null, all tree nodes will be taken
     * @param bool   $direct      - true to take only direct children
     * @param string $sortByField - field name to sort by
     * @param string $direction   - sort direction : "ASC" or "DESC"
     * @param bool   $includeNode - Include the root node in results?
     *
     * @return Query - Query object
     */
    abstract public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false);
}
