<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\OAuth\Client;
use AppBundle\Entity\OAuth\Scope;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class DemoFixture implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $model = new Client();
        $model->setClientId('http://cloud-storage.dev')
            ->setClientSecret('237ed57f218b41d07db6757afec3a41c')
            ->setRedirectUri('http://cloud-storage.dev/myfavoritedrinks/index.html');
        $manager->persist($model);

        $model = new Scope();
        $model->setScope('myfavoritedrinks:rw');
        $manager->persist($model);

        $manager->flush();
    }
}
