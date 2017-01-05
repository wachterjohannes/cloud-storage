<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WebfingerController extends Controller
{
    /**
     * @Route("/.well-known/webfinger", name="webfinger")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function webfingerAction(Request $request)
    {
        $resource = $request->get('resource');
        if (null === $resource) {
            throw new \Exception('resource parameter missing');
        }

        if (0 !== strpos($resource, 'acct:')) {
            throw new \Exception('unsupported resource type');
        }

        $userAddress = substr($resource, 5);
        $atPos = strpos($userAddress, '@');
        if (false === $atPos) {
            throw new \Exception('invalid user address');
        }

        $user = substr($userAddress, 0, $atPos);
        $webFingerData = [
            'links' => [
                [
                    'href' => sprintf('%sapi/storage/%s', 'http://cloud-storage.dev/', $user),
                    'properties' => [
                        'http://remotestorage.io/spec/version' => 'draft-dejong-remotestorage-05',
                        'http://remotestorage.io/spec/web-authoring' => null,
                        'http://tools.ietf.org/html/rfc6749#section-4.2' => sprintf(
                            '%sapi/oauth2/implicit?login_hint=%s',
                            'http://cloud-storage.dev/',
                            $user
                        ),
                        'http://tools.ietf.org/html/rfc6750#section-2.3' => 'true',
                        'http://tools.ietf.org/html/rfc7233' => 'dev' !== $this->getParameter('kernel.environment')
                            ? 'GET' : null,
                    ],
                    'rel' => 'http://tools.ietf.org/id/draft-dejong-remotestorage',
                ],
                // legacy -03 WebFinger response
                [
                    'href' => sprintf('%s%s', 'http://cloud-storage.dev/', $user),
                    'properties' => [
                        'http://remotestorage.io/spec/version' => 'draft-dejong-remotestorage-03',
                        'http://tools.ietf.org/html/rfc2616#section-14.16' => 'dev' !== $this->getParameter(
                            'kernel.environment'
                        ) ? 'GET' : false,
                        'http://tools.ietf.org/html/rfc6749#section-4.2' => sprintf(
                            '%saapi/oauth2/implicit?login_hint=%s',
                            'http://cloud-storage.dev/',
                            $user
                        ),
                        'http://tools.ietf.org/html/rfc6750#section-2.3' => true,
                    ],
                    'rel' => 'remotestorage',
                ],
            ],
        ];

        $response = new JsonResponse($webFingerData, 200);
        $response->headers->set('Content-Type', 'application/jrd+json');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }
}
