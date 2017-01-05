<?php

namespace AppBundle\RemoteStorage;

use AppBundle\Entity\RemoteStorage\Document;
use AppBundle\RemoteStorage\Exception\MetadataStorageException;
use AppBundle\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMetadataStorage implements MetadataStorageInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DocumentRepository
     */
    private $repository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Document::class);
    }

    public function getVersion(Path $p)
    {
        /** @var Document $document */
        $document = $this->repository->find($p->getPath());
        if (!$document) {
            return null;
        }

        return $document->getVersion();
    }

    public function getContentType(Path $p)
    {
        if ($p->getIsFolder()) {
            return null;
        }

        /** @var Document $document */
        $document = $this->repository->find($p->getPath());
        if (!$document || $document->isFolder()) {
            return null;
        }

        return $document->getContentType();
    }

    public function updateFolder(Path $p)
    {
        if (!$p->getIsFolder()) {
            throw new MetadataStorageException('not a folder');
        }

        return $this->updateDocument($p, null, null);
    }

    public function updateDocument(Path $p, $contentType, $contentLength)
    {
        /** @var Document $document */
        $document = $this->repository->find($p->getPath());
        if (null === $document) {
            $document = new Document($p->getPath(), $p->getIsFolder());
            $document->setVersion($newVersion = '1:' . uniqid());
            $this->entityManager->persist($document);
        } else {
            $currentVersion = $document->getVersion();
            $explodedData = explode(':', $currentVersion);
            $document->setVersion(sprintf('%d:%s', $explodedData[0] + 1, uniqid()));
        }

        $document->setContentType($contentType);
        $document->setContentLength($contentLength);

        $this->entityManager->flush();
    }

    public function deleteNode(Path $p)
    {
        /** @var Document $document */
        $document = $this->repository->find($p->getPath());
        $this->entityManager->remove($document);

        $this->entityManager->flush();
    }
}
