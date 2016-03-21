<?php

namespace BrauneDigital\ApiBaseBundle\View;

use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;

class ExceptionWrapperHandler implements ExceptionWrapperHandlerInterface
{
    /** converts exception to array
     * @param array $data
     * @return array
     */
    public function wrap($data)
    {
        /** @var \Symfony\Component\Debug\Exception\FlattenException $exception */
        $errors = array();
        if(array_key_exists("exception", $data)) {
            $exception = $data['exception'];
            $className = $exception->getClass();

            $pos = strrpos($className, '\\');
            if($pos !== false) {
                $className =  substr($className, $pos+1);
            }

            $errors = array(
                array(
                    'exception_class' => $className,
                    'status_text' => $data['status_text'],
                    'message' => $data['message']
                )
            );
        } else {
            $formErrors = array();
            foreach ($data['errors']->getErrors(true, true) as $formError) {
                $formErrors[] = array(
                    'path' => $formError->getCause()->getPropertyPath(),
                    'message' => $formError->getMessage()
                );
            }
            foreach($formErrors as $formError) {
                $errors[] = array(
                    'status_text' => $data['message'],
                    'message' => '@'.$formError['path'].': '.$formError['message']
                );
            }
        }

        $newException = array(
            'success' => false,
            'errors' => $errors,
            'payload' => array()
        );

        return $newException;
    }
}