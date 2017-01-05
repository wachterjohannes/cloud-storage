<?php

namespace AppBundle\Repository;

use AuthBucket\Bundle\OAuth2Bundle\Entity\AbstractEntityRepository;
use AuthBucket\OAuth2\Model\AccessTokenManagerInterface;

class AccessTokenRepository extends AbstractEntityRepository implements AccessTokenManagerInterface
{
}
