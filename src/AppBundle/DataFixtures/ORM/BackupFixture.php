<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\OAuth\Client;
use AppBundle\Entity\OAuth\Scope;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class BackupFixture implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $model = new Client();
        $model->setClientId('rs-backup.5apps.com')
            ->setClientSecret('237ed57f212341123db6757afec3a41c')
            ->setRedirectUri('https://rs-backup.5apps.com/');
        $manager->persist($model);

        $model = new Scope();
        $model->setScope('test-data:rw');
        $manager->persist($model);

        $manager->flush();
    }
}
