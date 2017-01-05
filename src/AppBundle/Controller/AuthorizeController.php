<?php

namespace AppBundle\Controller;

use AppBundle\Entity\OAuth\Authorize;
use AuthBucket\OAuth2\Exception\InvalidScopeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AuthorizeController extends Controller
{
    /**
     * @Route("/api/oauth2/implicit", name="authorize_implicit_grant")
     */
    public function implicitGrantAction(Request $request)
    {
        // TODO create client implicit?

        try {
            $response = $this->get('authbucket_oauth2.oauth2_controller')->authorizeAction($request);
        } catch (InvalidScopeException $exception) {
            $message = unserialize($exception->getMessage());
            if ($message['error_description'] !== 'The requested scope is invalid.') {
                throw $exception;
            }

            $response = $this->implicitGrant($request);
        }

        // TODO is this a good idea?
        if ($response instanceof RedirectResponse) {
            $response->setTargetUrl(str_replace('?', '#', $response->getTargetUrl()));
        }

        return $response;
    }

    private function implicitGrant(Request $request)
    {
        $clientId = $request->query->get('client_id');
        $scopes = preg_split('/\s+/', $request->query->get('scope', ''));
        $username = $this->getUser()->getUsername();

        if ($request->getMethod() === 'POST') {
            $modelManagerFactory = $this->get('authbucket_oauth2.model_manager.factory');
            $authorizeManager = $modelManagerFactory->getModelManager('authorize');

            // Update existing authorization if possible, else create new.
            /** @var Authorize $authorize */
            $authorize = $authorizeManager->readModelOneBy(
                [
                    'clientId' => $clientId,
                    'username' => $username,
                ]
            );
            if ($authorize === null) {
                $class = $authorizeManager->getClassName();
                $authorize = new $class();
                $authorize->setClientId($clientId)->setUsername($username)->setScope((array)$scopes);
                $authorizeManager->createModel($authorize);
            } else {
                $authorize->setClientId($clientId)->setUsername($username)->setScope(
                    array_merge((array)$authorize->getScope(), $scopes)
                );
                $authorizeManager->updateModel($authorize);
            }

            // Back to this path, with original GET parameters.
            return $this->redirect($request->getRequestUri());
        }

        return $this->render(
            'authorize/implicit-grant.html.twig',
            [
                'client_id' => $clientId,
                'username' => $username,
                'scopes' => $scopes,
            ]
        );
    }
}
