<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UserFixture implements FixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $factory = $this->container->get('security.encoder_factory');

        $user = new User();
        $encoder = $factory->getEncoder($user);

        $user->setUsername('admin');

        $password = $encoder->encodePassword('admin', $user->getSalt());
        $user->setPassword($password);

        $manager->persist($user);
        $manager->flush();
    }
}
