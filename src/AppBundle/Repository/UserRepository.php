<?php

namespace AppBundle\Repository;

use AuthBucket\Bundle\OAuth2Bundle\Entity\AbstractEntityRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRepository extends AbstractEntityRepository implements UserLoaderInterface
{
    public function createUser()
    {
        $class = $this->getClassName();

        return new $class();
    }

    public function deleteUser(UserInterface $user)
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function reloadUser(UserInterface $user)
    {
        $this->getEntityManager()->refresh($user);
    }

    public function updateUser(UserInterface $user)
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function loadUserByUsername($username)
    {
        $user = $this->findOneBy(
            [
                'username' => $username,
            ]
        );
        if ($user === null) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        return $this->find($user->getId());
    }

    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }
}
