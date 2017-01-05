<?php

namespace AppBundle\Controller;

use AppBundle\RemoteStorage\Path;
use AppBundle\RemoteStorage\RemoteStorage;
use AppBundle\RemoteStorage\RemoteStorageInterface;
use AuthBucket\OAuth2\Security\Authentication\Token\AccessTokenToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class RemoteStorageController extends Controller
{
    /**
     * @Route("/api/storage/{path}", name="storage_get", requirements={"path": ".*"}, methods={"GET", "HEAD"})
     */
    public function getAction($path, Request $request)
    {
        return $this->getObject(new Path('/' . $path), $request, $this->get('security.token_storage')->getToken());
    }

    /**
     * @Route("/api/storage/{path}", name="storage_put", requirements={"path": ".*"}, methods={"PUT"})
     */
    public function putAction($path, Request $request)
    {
        return $this->putDocument(new Path('/' . $path), $request, $this->get('security.token_storage')->getToken());
    }

    /**
     * @Route("/api/storage/{path}", name="storage_delete", requirements={"path": ".*"}, methods={"DELETE"})
     */
    public function deleteAction($path, Request $request)
    {
        return $this->deleteObject(new Path('/' . $path), $request, $this->get('security.token_storage')->getToken());
    }

    /**
     * @Route("/api/storage/{path}", name="storage_options", requirements={"path": ".*"}, methods={"OPTIONS"})
     */
    public function optionsAction($path, Request $request)
    {
        // TODO for folders only GET, HEAD, OPTIONS

        $response = new Response();
        $response->headers->set('Access-Control-Allow-Methods', 'GET, PUT, DELETE, HEAD, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            'Authorization, Content-Length, Content-Type, Origin, X-Requested-With, If-Match, If-None-Match'
        );

        return $response;
    }

    private function getObject(Path $path, Request $request, AccessTokenToken $tokenInfo)
    {
        // allow requests to public files (GET|HEAD) without authentication
        if ($path->getIsPublic() && $path->getIsDocument()) {
            return $this->getDocument($path, $request, $tokenInfo);
        }

        // past this point we MUST be authenticated
        if (null === $tokenInfo) {
            throw new AccessDeniedHttpException('unauthorized');
        }

        if ($path->getIsFolder()) {
            return $this->getFolder($path, $request, $tokenInfo);
        }

        return $this->getDocument($path, $request, $tokenInfo);
    }

    private function putDocument(Path $path, Request $request, AccessTokenToken $tokenInfo)
    {
        if ($path->getUserId() !== $tokenInfo->getUsername()) {
            throw new AccessDeniedHttpException('path does not match authorized subject');
        }
        if (!$this->hasWriteScope($tokenInfo->getScope(), $path->getModuleName())) {
            throw new AccessDeniedHttpException('path does not match authorized scope');
        }

        $ifMatch = $this->stripQuotes($request->headers->get('If-Match'));
        $ifNoneMatch = $this->stripQuotes($request->headers->get('If-None-Match'));

        $documentVersion = $this->getRemoteStorage()->getVersion($path);
        if (null !== $ifMatch && !in_array($documentVersion, $ifMatch)) {
            throw new PreconditionFailedHttpException('version mismatch');
        }
        if (null !== $ifNoneMatch && in_array('*', $ifNoneMatch) && null !== $documentVersion) {
            throw new PreconditionFailedHttpException('document already exists');
        }

        $content = $this->getRemoteStorage()->putDocument(
            $path,
            $request->headers->get('Content-Type'),
            $request->getContent(),
            $ifMatch,
            $ifNoneMatch
        );

        $rsr = new Response();
        $rsr->headers->set('ETag', '"' . $this->getRemoteStorage()->getVersion($path) . '"');
        $rsr->setContent($content);

        return $rsr;
    }

    private function deleteObject(Path $path, Request $request, AccessTokenToken $tokenInfo)
    {
        if ($path->getUserId() !== $tokenInfo->getUsername()) {
            throw new AccessDeniedHttpException('path does not match authorized subject');
        }
        if (!$this->hasWriteScope($tokenInfo->getScope(), $path->getModuleName())) {
            throw new AccessDeniedHttpException('path does not match authorized scope');
        }

        // need to get the version before the delete
        $documentVersion = $this->getRemoteStorage()->getVersion($path);
        $ifMatch = $this->stripQuotes($request->headers->get('If-Match'));

        // if document does not exist, and we have If-Match header set we should
        // return a 412 instead of a 404
        if (null !== $ifMatch && !in_array($documentVersion, $ifMatch)) {
            throw new PreconditionFailedHttpException('version mismatch');
        }
        if (null === $documentVersion) {
            throw new PreconditionFailedHttpException(
                sprintf('document "%s" not found', $path->getPath())
            );
        }

        $document = $this->getRemoteStorage()->deleteDocument($path, $ifMatch);

        $response = new Response();
        $response->headers->set('ETag', '"' . $documentVersion . '"');
        $response->setContent($document);

        return $response;
    }

    private function getDocument(Path $path, Request $request, AccessTokenToken $tokenInfo)
    {
        if (null !== $tokenInfo) {
            if ($path->getUserId() !== $tokenInfo->getUsername()) {
                throw new AccessDeniedHttpException('path does not match authorized subject');
            }
            if (!$this->hasReadScope($tokenInfo->getScope(), $path->getModuleName())) {
                throw new AccessDeniedHttpException('path does not match authorized scope');
            }
        }

        $documentVersion = $this->getRemoteStorage()->getVersion($path);
        if (null === $documentVersion) {
            throw new NotFoundHttpException(
                sprintf('document "%s" not found', $path->getPath())
            );
        }

        $requestedVersion = $this->stripQuotes($request->headers->get('If-None-Match'));
        $documentContentType = $this->getRemoteStorage()->getContentType($path);
        if (null !== $requestedVersion && in_array($documentVersion, $requestedVersion)) {
            $response = new Response(null, 304);
            $response->headers->set('ETag', '"' . $documentVersion . '"');
            $response->headers->set('Content-Type', $documentContentType);

            return $response;
        }

        $response = new Response();
        $response->headers->set('ETag', '"' . $documentVersion . '"');
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Content-Type', $documentContentType);

        if ('GET' === $request->getMethod()) {
            // use body
            $response->setContent(
                file_get_contents(
                    $this->getRemoteStorage()->getDocument(
                        $path,
                        $requestedVersion
                    )
                )
            );
        }

        return $response;
    }

    public function getFolder(Path $path, Request $request, AccessTokenToken $tokenInfo)
    {
        if ($path->getUserId() !== $tokenInfo->getUsername()) {
            throw new AccessDeniedHttpException('path does not match authorized subject');
        }

        if (!$this->hasReadScope($tokenInfo->getScope(), $path->getModuleName())) {
            throw new AccessDeniedHttpException('path does not match authorized scope');
        }

        $folderVersion = $this->getRemoteStorage()->getVersion($path);
        if (null === $folderVersion) {
            // folder does not exist, so we just invent this
            // ETag that will be the same for all empty folders
            $folderVersion = 'e:404';
        }

        $requestedVersion = $this->stripQuotes($request->headers->get('If-None-Match'));
        if (null !== $requestedVersion && in_array($folderVersion, $requestedVersion)) {
            //return new RemoteStorageResponse($request, 304, $folderVersion);
            $response = new Response(null, 304);
            $response->headers->set('ETag', '"' . $folderVersion . '"');
            $response->headers->set('Content-Type', 'application/ld+json');

            return $response;
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/ld+json');
        $response->headers->set('ETag', '"' . $folderVersion . '"');

        if ('GET' === $request->getMethod()) {
            $response->setContent(
                $this->getRemoteStorage()->getFolder(
                    $path,
                    $this->stripQuotes($request->headers->get('If-None-Match'))
                )
            );
        }

        return $response;
    }

    private function hasReadScope($scope, $moduleName)
    {
        $validReadScopes = [
            '*:r',
            '*:rw',
            sprintf('%s:%s', $moduleName, 'r'),
            sprintf('%s:%s', $moduleName, 'rw'),
        ];

        return count(array_intersect($scope, $validReadScopes)) > 0;
    }

    private function hasWriteScope($scope, $moduleName)
    {
        $validWriteScopes = [
            '*:rw',
            sprintf('%s:%s', $moduleName, 'rw'),
        ];

        return count(array_intersect($scope, $validWriteScopes)) > 0;
    }

    /**
     * ETag/If-Match/If-None-Match are always quoted, this method removes
     * the quotes.
     */
    public function stripQuotes($versionHeader)
    {
        if (null === $versionHeader) {
            return;
        }

        $versions = [];
        if ('*' === $versionHeader) {
            return ['*'];
        }

        foreach (explode(',', $versionHeader) as $v) {
            $v = trim($v);
            $startQuote = strpos($v, '"');
            $endQuote = strrpos($v, '"');
            $length = strlen($v);
            if (0 !== $startQuote || $length - 1 !== $endQuote) {
                throw new BadRequestHttpException('version header must start and end with a double quote');
            }
            $versions[] = substr($v, 1, $length - 2);
        }

        return $versions;
    }

    /**
     * @return RemoteStorageInterface
     */
    private function getRemoteStorage()
    {
        return $this->get('app.remote_storage');
    }
}
