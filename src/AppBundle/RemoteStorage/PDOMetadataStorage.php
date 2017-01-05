<?php

namespace AppBundle\RemoteStorage;

use AppBundle\RemoteStorage\Exception\MetadataStorageException;

class PDOMetadataStorage implements MetadataStorageInterface
{
    /** @var \PDO */
    private $db;

    /** @var string */
    private $prefix;

    public function __construct(\PDO $db, $prefix = '')
    {
        $this->db = $db;
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->prefix = $prefix;
    }

    public function getVersion(Path $p)
    {
        $md = $this->getMetadata($p);

        return null !== $md ? $md['version'] : null;
    }

    public function getContentType(Path $p)
    {
        $md = $this->getMetadata($p);

        return null !== $md ? $md['content_type'] : null;
    }

    public function updateFolder(Path $p)
    {
        if (!$p->getIsFolder()) {
            throw new MetadataStorageException('not a folder');
        }

        return $this->updateDocument($p, null);
    }

    /**
     * We have a very weird version update method by including a sequence number
     * that makes it easy for tests to see if there is correct behavior, a sequence
     * number is not enough though as deleting a file would reset the sequence number and
     * thus make it possible to have files with different content to have the same
     * sequence number in the same location, but in order to check if all versions
     * are updated up to the root we have to do this this way...
     */
    public function updateDocument(Path $p, $contentType, $contentLength)
    {
        $currentVersion = $this->getVersion($p);
        if (null === $currentVersion) {
            $newVersion = '1:' . uniqid();
            $stmt = $this->db->prepare(
                sprintf(
                    'INSERT INTO %s (path, content_type, version) VALUES(:path, :content_type, :version)',
                    $this->prefix . 'md'
                )
            );
        } else {
            $explodedData = explode(':', $currentVersion);
            $newVersion = sprintf('%d:%s', $explodedData[0] + 1, uniqid());
            $stmt = $this->db->prepare(
                sprintf(
                    'UPDATE %s SET version = :version, content_type = :content_type WHERE path = :path',
                    $this->prefix . 'md'
                )
            );
        }

        $stmt->bindValue(':path', $p->getPath(), \PDO::PARAM_STR);
        $stmt->bindValue(':content_type', $contentType, \PDO::PARAM_STR);
        $stmt->bindValue(':version', $newVersion, \PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new MetadataStorageException('unable to update node');
        }
    }

    public function deleteNode(Path $p)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'DELETE FROM %s WHERE path = :path',
                $this->prefix . 'md'
            )
        );
        $stmt->bindValue(':path', $p->getPath(), \PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new MetadataStorageException('unable to delete node');
        }
    }

    public static function createTableQueries($prefix)
    {
        $query = [];
        $query[] = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                path VARCHAR(255) NOT NULL,
                content_type VARCHAR(255) DEFAULT NULL,
                version VARCHAR(255) NOT NULL,
                UNIQUE (path)
            )',
            $prefix . 'md'
        );

        return $query;
    }

    public function initDatabase()
    {
        $queries = self::createTableQueries($this->prefix);
        foreach ($queries as $q) {
            $this->db->query($q);
        }

        $tables = ['md'];
        foreach ($tables as $t) {
            // make sure the tables are empty
            $this->db->query(
                sprintf(
                    'DELETE FROM %s',
                    $this->prefix . $t
                )
            );
        }
    }

    /**
     * Get the version of the path which can be either a folder or document.
     *
     * @param $path The full path to the folder or document
     *
     * @returns the version of the path, or null if path does not exist
     */
    private function getMetadata(Path $p)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT version, content_type FROM %s WHERE path = :path',
                $this->prefix . 'md'
            )
        );
        $stmt->bindValue(':path', $p->getPath(), \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (false !== $result) {
            return $result;
        }

        return;
    }
}
