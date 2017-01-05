<?php

namespace AppBundle\RemoteStorage;

interface DocumentStorageInterface
{

    public function isDocument(Path $p);

    /**
     * Get the full absolute location of the document on the filesystem.
     */
    public function getDocumentPath(Path $p);

    public function getDocument(Path $p);

    /**
     * Store a new document.
     *
     * @returns an array of all created objects
     */
    public function putDocument(Path $p, $documentContent);

    /**
     * Delete a document and all empty parent directories if there are any.
     *
     * @param $p the path of a document to delete
     *
     * @returns an array of all deleted objects
     */
    public function deleteDocument(Path $p);

    public function isFolder(Path $p);

    public function getFolder(Path $p);

    public function getFolderSize(Path $p);

    public function isEmptyFolder(Path $p);

    public function deleteFolder(Path $p);
}
