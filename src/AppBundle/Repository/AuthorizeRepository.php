<?php

namespace AppBundle\Repository;

use AuthBucket\Bundle\OAuth2Bundle\Entity\AbstractEntityRepository;
use AuthBucket\OAuth2\Model\AuthorizeManagerInterface;

class AuthorizeRepository extends AbstractEntityRepository implements AuthorizeManagerInterface
{
}
