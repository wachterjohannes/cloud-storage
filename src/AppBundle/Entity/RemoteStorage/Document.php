<?php

namespace AppBundle\Entity\RemoteStorage;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="app_documents")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DocumentRepository")
 */
class Document
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $path;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $isFolder;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $version;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $contentType;

    /**
     * @var string
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $contentLength;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $contentHash;

    /**
     * @param string $path
     * @param bool $isFolder
     */
    public function __construct($path, $isFolder = false)
    {
        $this->path = $path;
        $this->isFolder = $isFolder;
    }

    /**
     * Returns path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns isFolder.
     *
     * @return bool
     */
    public function isFolder(): bool
    {
        return $this->isFolder;
    }

    /**
     * Returns version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set version.
     *
     * @param string $version
     *
     * @return Document
     */
    public function setVersion(string $version): Document
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Returns contentType.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Set contentType.
     *
     * @param string $contentType
     *
     * @return Document
     */
    public function setContentType(string $contentType = null): Document
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Returns contentLength.
     *
     * @return string
     */
    public function getContentLength(): string
    {
        return $this->contentLength;
    }

    /**
     * Set contentLength.
     *
     * @param string $contentLength
     *
     * @return Document
     */
    public function setContentLength(string $contentLength = null): Document
    {
        $this->contentLength = $contentLength;

        return $this;
    }

    /**
     * Returns contentHash.
     *
     * @return string
     */
    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    /**
     * Set contentHash.
     *
     * @param string $contentHash
     *
     * @return Document
     */
    public function setContentHash(string $contentHash = null): Document
    {
        $this->contentHash = $contentHash;

        return $this;
    }
}
