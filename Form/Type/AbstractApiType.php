<?php

namespace BrauneDigital\ApiBaseBundle\Form\Type;
use BrauneDigital\ApiBaseBundle\Form\ApiRequestHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbstractApiType extends AbstractType {

    private static $apiRequestHandler;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setRequestHandler($this->getRequestHandler());
    }


    protected function getRequestHandler() {

        if(self::$apiRequestHandler == null) {
            self::$apiRequestHandler = new ApiRequestHandler();
        }
        return self::$apiRequestHandler;
    }
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('csrf_protection', false);
    }
}