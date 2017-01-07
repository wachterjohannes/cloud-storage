<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\OAuth\AccessToken;
use AppBundle\Entity\OAuth\Client;
use AppBundle\Entity\OAuth\Scope;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class FilesFixture implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $model = new Client();
        $model->setClientId('cloud-storage-app')
            ->setClientSecret('237ed512318b41d07db6757afec3a41c')
            ->setRedirectUri('http://cloud-storage.dev/myfavoritedrinks/index.html');
        $manager->persist($model);

        $model = new Scope();
        $model->setScope('files:rw');
        $manager->persist($model);


        $model = new AccessToken();
        $model->setAccessToken('ba2e8d1f54ed3e3d96935796576f1a06')
            ->setTokenType('bearer')
            ->setClientId('cloud-storage-app')
            ->setUsername('admin')
            ->setExpires(new \DateTime('+1 year'))
            ->setScope(['files:rw']);
        $manager->persist($model);

        $manager->flush();
    }
}
