<?php

namespace Mapbender\CoreBundle\Form\EventListener;

use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Mapbender\CoreBundle\Element\Type\PrintClientTemplateAdminType;
use Mapbender\CoreBundle\Element\Type\PrintClientQualityAdminType;

/**
 * 
 */
class PrintClientSubscriber implements EventSubscriberInterface
{

    /**
     * A FormFactoryInterface 's Factory
     * 
     * @var \Symfony\Component\Form\FormFactoryInterface 
     */
    private $factory;
    
    /**
     * The application
     * 
     * @var application
     */
    private $application;

    /**
     * Creates a subscriber
     * 
     * @param \Symfony\Component\Form\FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory, $application)
    {
        $this->factory = $factory;
        $this->application = $application;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_BIND => 'preBind');
    }

    /**
     * Checkt form fields by PRE_BIND DataEvent
     * 
     * @param DataEvent $event
     */
    public function preBind(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if(null === $data)
        {
            return;
        }
        if(key_exists("scales", $data) && is_string($data["scales"]))
        {
            $data["scales"] = preg_split("/\s?,\s?/", $data["scales"]);
            $event->setData($data);
        }
        
        if(key_exists("templates", $data) )
        {
            $form->add($this->factory->createNamed(
                                'templates', "collection", null,
                                array(
                            'property_path' => '[templates]',
                            'type' => new PrintClientTemplateAdminType(),
                            'options' => array(
                                ))));
        }
        if(key_exists("quality_levels", $data) )
        {
            $form->add($this->factory->createNamed(
                                'quality_levels', "collection", null,
                                array(
                            'property_path' => '[quality_levels]',
                            'type' => new PrintClientQualityAdminType(),
                            'options' => array(
                                ))));
        }

    }

    /**
     * Checkt form fields by PRE_SET_DATA DataEvent
     * 
     * @param DataEvent $event
     */
    public function preSetData(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if(null === $data)
        {
            return;
        }

        if(key_exists("scales", $data) && is_array($data["scales"]))
        {
            $data["scales"] = implode(",", $data["scales"]);
            $event->setData($data);
        }
        
        if(key_exists("templates", $data) )
        {
            $form->add($this->factory->createNamed(
                                'templates', "collection", null,
                                array(
                            'property_path' => '[templates]',
                            'type' => new PrintClientTemplateAdminType(),
                            'options' => array(
                                ))));
        }
        
        if(key_exists("quality_levels", $data) )
        {
            $form->add($this->factory->createNamed(
                                'quality_levels', "collection", null,
                                array(
                            'property_path' => '[quality_levels]',
                            'type' => new PrintClientQualityAdminType(),
                            'options' => array(
                                ))));
        }
        
    }

}