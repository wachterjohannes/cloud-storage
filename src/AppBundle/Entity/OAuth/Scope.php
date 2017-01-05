<?php

namespace AppBundle\Entity\OAuth;

use AuthBucket\Bundle\OAuth2Bundle\Entity\Scope as AbstractScope;
use Doctrine\ORM\Mapping as ORM;

/**
 * Scope.
 *
 * @ORM\Table(name="app_scopes")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ScopeRepository")
 */
class Scope extends AbstractScope
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
