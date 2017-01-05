<?php

namespace AppBundle\RemoteStorage;

interface MetadataStorageInterface
{

    public function getVersion(Path $p);

    public function getContentType(Path $p);

    public function updateFolder(Path $p);

    /**
     * We have a very weird version update method by including a sequence number
     * that makes it easy for tests to see if there is correct behavior, a sequence
     * number is not enough though as deleting a file would reset the sequence number and
     * thus make it possible to have files with different content to have the same
     * sequence number in the same location, but in order to check if all versions
     * are updated up to the root we have to do this this way...
     */
    public function updateDocument(Path $p, $contentType, $contentLength);

    public function deleteNode(Path $p);
}
