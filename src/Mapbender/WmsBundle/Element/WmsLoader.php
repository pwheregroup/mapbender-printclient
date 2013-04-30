<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;

/**
 * WmsLoader
 * 
 * @author Karim Malhas
 * @author Paul Schmidt
 */
class WmsLoader extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "WmsLoader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "WMS Loader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("wms", "loader");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "",
            "target" => null,
            "autoOpen" => false,
            "defaulformat" => "png",
            "defaultinfoformat" => "html"
//            "toc" => ""
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbWmsloader';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.wmsloader.js'
            ),
            'css' => array(
            )
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmsBundle\Element\Type\WmsLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                        ->render('MapbenderWmsBundle:Element:wmsloader.html.twig',
                                 array(
                            'id' => $this->getId(),
                            "title" => $this->getTitle(),
                            'configuration' => $this->getConfiguration()));
    }

}
