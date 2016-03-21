<?php

namespace BrauneDigital\ApiBaseBundle\Annotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 */
class ModuleAccess extends ConfigurationAnnotation
{
    protected $modules;

    public function __construct($options)
    {
        if (isset($options['value'])) {
            if(is_array($options['value'])) {
                $this->modules = $options['value'];
            }
            else {
                $this->modules = array($options['value']);
            }
            unset($options['value']);
        } else {
            $this->modules = array();
        }

    }

    public function getAliasName()
    {
        return 'module_access';
    }

    public function allowArray()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }
}