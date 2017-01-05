<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\OAuth\Client;
use AppBundle\Entity\OAuth\Scope;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class BrowserFixture implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $model = new Client();
        $model->setClientId('https://remotestorage-browser.5apps.com')
            ->setClientSecret('237ed57f212341d07db6757afec3a41c')
            ->setRedirectUri('https://remotestorage-browser.5apps.com');
        $manager->persist($model);

        $model = new Scope();
        $model->setScope('*:rw');
        $manager->persist($model);

        $manager->flush();
    }
}
