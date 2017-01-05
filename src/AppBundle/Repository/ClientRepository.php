<?php

namespace AppBundle\Repository;

use AuthBucket\Bundle\OAuth2Bundle\Entity\AbstractEntityRepository;
use AuthBucket\OAuth2\Model\ClientManagerInterface;

class ClientRepository extends AbstractEntityRepository implements ClientManagerInterface
{
}
