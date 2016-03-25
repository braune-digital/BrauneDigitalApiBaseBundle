<?php

namespace BrauneDigital\ApiBaseBundle\Form;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\Util\ServerParams;
use Symfony\Component\HttpFoundation\Request;

//borrowed from Symfony\Component\Form\Extension\HttpFoundationRequestHandler
class ApiRequestHandler implements RequestHandlerInterface
{
    /**
     * @var ServerParams
     */
    private $serverParams;

    /**
     * {@inheritdoc}
     */
    public function __construct(ServerParams $serverParams = null)
    {
        $this->serverParams = $serverParams ?: new ServerParams();
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(FormInterface $form, $request = null)
    {
        if (!$request instanceof Request) {
            throw new UnexpectedTypeException($request, 'Symfony\Component\HttpFoundation\Request');
        }

        $name = $form->getName();
        $method = $form->getConfig()->getMethod();
        //do not distinguish between post put and patch
        if ($method !== $request->getMethod() && (!in_array($method, array('POST', 'PUT', 'PATCH')) || !in_array( $request->getMethod(), array('POST', 'PUT', 'PATCH')))) {
            return;
        }

        // For request methods that must not have a request body we fetch data
        // from the query string. Otherwise we look for data in the request body.
        if ('GET' === $method || 'HEAD' === $method || 'TRACE' === $method) {
            if ('' === $name) {
                $data = $request->query->all();
            } else {
                // Don't submit GET requests if the form's name does not exist
                // in the request
                if (!$request->query->has($name)) {
                    return;
                }

                $data = $request->query->get($name);
            }
        } else {
            // Mark the form with an error if the uploaded size was too large
            // This is done here and not in FormValidator because $_POST is
            // empty when that error occurs. Hence the form is never submitted.
            $contentLength = $this->serverParams->getContentLength();
            $maxContentLength = $this->serverParams->getPostMaxSize();

            if (!empty($maxContentLength) && $contentLength > $maxContentLength) {
                // Submit the form, but don't clear the default values
                $form->submit(null, false);

                $form->addError(new FormError(
                    $form->getConfig()->getOption('post_max_size_message'),
                    null,
                    array('{{ max }}' => $this->serverParams->getNormalizedIniPostMaxSize())
                ));

                return;
            }

            if ($name !== '' && ($request->request->has($name) || $request->files->has($name))) {
                $default = $form->getConfig()->getCompound() ? array() : null;
                $params = $request->request->get($name, $default);
                $files = $request->files->get($name, $default);
            } else {
                //try to bind the form to data if the form was not found
                $params = $request->request->all();
                $files = $request->files->all();

            }

            if (is_array($params) && is_array($files)) {
                $data = array_replace_recursive($params, $files);
            } else {
                $data = $params ?: $files;
            }
        }

        // Don't auto-submit the form unless at least one field is present.
        if ('' === $name && count(array_intersect_key($data, $form->all())) <= 0) {
            return;
        }

        $form->submit($data, 'PATCH' !== $method);
    }
}