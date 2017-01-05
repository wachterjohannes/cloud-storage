<?php

namespace AppBundle\Entity\OAuth;

use AuthBucket\Bundle\OAuth2Bundle\Entity\AccessToken as AbstractAccessToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="app_access_tokens")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AccessTokenRepository")
 */
class AccessToken extends AbstractAccessToken
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
