<?php

namespace AppBundle\RemoteStorage;

use AppBundle\RemoteStorage\Exception\RemoteStorageException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class RemoteStorage implements RemoteStorageInterface
{
    /**
     * @var MetadataStorageInterface
     */
    private $md;

    /**
     * @var DocumentStorageInterface
     */
    private $d;

    public function __construct(MetadataStorageInterface $md, DocumentStorageInterface $d)
    {
        $this->md = $md;
        $this->d = $d;
    }

    public function putDocument(Path $p, $contentType, $documentData, array $ifMatch = null, array $ifNoneMatch = null)
    {
        if (null !== $ifMatch && !in_array($this->md->getVersion($p), $ifMatch)) {
            throw new PreconditionFailedHttpException('version mismatch');
        }

        if (null !== $ifNoneMatch && in_array('*', $ifNoneMatch) && null !== $this->md->getVersion($p)) {
            throw new PreconditionFailedHttpException('document already exists');
        }

        $updatedEntities = $this->d->putDocument($p, $documentData);
        $this->md->updateDocument($p, $contentType, strlen($documentData));
        foreach ($updatedEntities as $u) {
            $this->md->updateFolder(new Path($u));
        }
    }

    public function deleteDocument(Path $p, array $ifMatch = null)
    {
        if (null !== $ifMatch && !in_array($this->md->getVersion($p), $ifMatch)) {
            throw new PreconditionFailedHttpException('version mismatch');
        }
        $deletedEntities = $this->d->deleteDocument($p);
        foreach ($deletedEntities as $d) {
            $this->md->deleteNode(new Path($d));
        }

        // increment the version from the folder containing the last deleted
        // folder and up to the user root, we cannot conveniently do this from
        // the MetadataStorage class :(
        foreach ($p->getFolderTreeToUserRoot() as $i) {
            if (null !== $this->md->getVersion(new Path($i))) {
                $this->md->updateFolder(new Path($i));
            }
        }
    }

    public function getVersion(Path $p)
    {
        return $this->md->getVersion($p);
    }

    public function getContentType(Path $p)
    {
        return $this->md->getContentType($p);
    }

    public function getDocument(Path $p, array $ifNoneMatch = null)
    {
        if (null !== $ifNoneMatch && in_array($this->md->getVersion($p), $ifNoneMatch)) {
            throw new RemoteStorageException('document not modified');
        }

        return $this->d->getDocumentPath($p);
    }

    public function getFolder(Path $p, array $ifNoneMatch = null)
    {
        if (null !== $ifNoneMatch && in_array($this->md->getVersion($p), $ifNoneMatch)) {
            throw new RemoteStorageException('folder not modified');
        }

        $f = [
            '@context' => 'http://remotestorage.io/spec/folder-description',
            'items' => $this->d->getFolder($p),
        ];
        foreach ($f['items'] as $name => $meta) {
            $f['items'][$name]['ETag'] = $this->md->getVersion(new Path($p->getFolderPath().$name));

            // if item is a folder we don't want Content-Type
            if (strrpos($name, '/') !== strlen($name) - 1) {
                $f['items'][$name]['Content-Type'] = $this->md->getContentType(new Path($p->getFolderPath().$name));
            }
        }

        return json_encode($f, JSON_FORCE_OBJECT);
    }

    public function getFolderSize(Path $p)
    {
        return self::sizeToHuman($this->d->getFolderSize($p));
    }

    public static function sizeToHuman($byteSize)
    {
        $kB = 1024;
        $MB = $kB * 1024;
        $GB = $MB * 1024;

        if ($byteSize > $GB) {
            return sprintf('%0.2fGB', $byteSize / $GB);
        }
        if ($byteSize > $MB) {
            return sprintf('%0.2fMB', $byteSize / $MB);
        }

        return sprintf('%0.0fkB', $byteSize / $kB);
    }
}
