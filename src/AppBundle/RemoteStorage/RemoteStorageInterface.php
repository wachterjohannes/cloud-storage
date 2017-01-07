<?php

namespace AppBundle\RemoteStorage;

interface RemoteStorageInterface
{
    public function putDocument(Path $p, $contentType, $documentData, array $ifMatch = null, array $ifNoneMatch = null);

    public function deleteDocument(Path $p, array $ifMatch = null);

    public function getVersion(Path $p);

    public function getContentType(Path $p);

    public function getContentHash(Path $p);

    public function getDocument(Path $p, array $ifNoneMatch = null);

    public function getFolder(Path $p, array $ifNoneMatch = null);

    public function getFolderSize(Path $p);
}
